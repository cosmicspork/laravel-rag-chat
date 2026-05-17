<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Dtos\ChatMessageDto;
use App\Enums\ChatActionEnum;
use App\Enums\ChatRoleEnum;
use App\Http\Requests\ChatProxyRequest;
use App\Jobs\InsertChatMessage;
use App\Repositories\ChatHistory;

class ChatProxyController extends Controller
{
    protected $chatHistory;

    public function __construct(ChatHistory $chatHistory)
    {
        $this->chatHistory = $chatHistory;
    }

    /**
     * Run a RAG search with caching.
     *
     * @param string $query
     * @return string Document contents
     */
    protected function ragSearch(string $query): string
    {
        // Generate the cache key
        $cacheKey = 'rag:' . md5($query);

        // Check for an existing cache entry
        if (Cache::has($cacheKey)) {
            Log::info('RAG cache hit', ['query' => $query]);
            return Cache::get($cacheKey);
        }

        // Perform the search
        $docs = Http::withHeaders(['api-key' => config('chat.rag.key')])
                ->timeout(config('chat.rag.timeout'))
                ->retry(config('chat.rag.retries'), config('chat.rag.retryDelay'))
                ->post(config('chat.rag.endpoint'), [
                    'search' => $query,
                    'top'    => config('chat.rag.top'),
                ])
                ->throw(function ($response, $e) {
                    Log::error('RAG search API call failed', ['error' => $e->getMessage(), 'response' => $response]);
                })
                ->json('value');

        // Build the context string
        $context = view('prompts::rag_context_wrapper', ['docs' => $docs])->render();

        // Cache the result
        Cache::put($cacheKey, $context, config('chat.rag.cacheTtl'));

        // Log the action
        Log::info('RAG search performed', ['query' => $query]);

        // Return the result
        return $context;
    }

    /**
     * Proxy a chat request to the chat completion service.
     *
     * @param ChatProxyRequest $request
     * @return JsonResponse
     */
    public function proxy(ChatProxyRequest $request): JsonResponse
    {
        // Get the authenticated user id or guest if auth is disabled
        $userId = Auth::check() ? Auth::id() : 'guest';

        // Get the validated action
        $action = ChatActionEnum::from($request->input('action'));
        // Get the validated message and message index if applicable
        $message = $request->input('message');
        $messageId = $request->input('message_id'); // Only applicable if action is regeneration

        // regeneration: Validate we have a previous message to regenerate
        if ($action === ChatActionEnum::Regeneration && ! $this->chatHistory->get($messageId)) {
            // TODO: figure out why message IDs are not matching
            return Controller::errorResponse("Message with id {$messageId} not found", Response::HTTP_NOT_FOUND);
        }

        // regeneration: Remove the message from the session by index
        if ($action === ChatActionEnum::Regeneration) {
            $this->chatHistory->remove($messageId);
        }

        // completion: Process the user message and search results
        if ($action === ChatActionEnum::Completion) {
            // Add the user message to the session messages
            $userMessage = ChatMessageDto::create(
                role: ChatRoleEnum::User,
                content: $message,
            );
            $this->chatHistory->add($userMessage);

            // Call the search service
            $searchResults = $this->ragSearch($userMessage->content);

            // Add the search results as a system message to the session messages
            $systemMessage = ChatMessageDto::create(
                role: ChatRoleEnum::System,
                content: $searchResults,
            );
            $this->chatHistory->add($systemMessage);
        }

        // Call the completion service
        $apiResponse = Http::withHeaders(['api-key' => config('chat.completion.key')])
            ->timeout(config('chat.completion.timeout'))
            ->retry(config('chat.completion.retries'), config('chat.completion.retryDelay'))
            ->post(config('chat.completion.endpoint'), [
                'model'       => config('chat.completion.model'),
                'messages'    => $this->chatHistory->all(),
                'temperature' => config('chat.completion.temperature'),
                'max_tokens'  => config('chat.completion.maxTokens'),
            ])
            ->throw(function ($response, $e) {
                Log::error('Chat completion API call failed', ['error'  => $e->getMessage(), 'response' => $response]);
            })
            ->json('choices.0.message.content');

        // regeneration: Append tag to regenerated messages and get the previous user message
        if ($action === ChatActionEnum::Regeneration) {
            $apiResponse = 'regenerated: ' . $apiResponse;
            if (!isset($userMessage)) { // This is a safeguard, the user message is not set in the regeneration action
                $userMessage = $this->chatHistory->all()->firstWhere('role', ChatRoleEnum::User);
            }
        }

        // Add the assistant message to the session messages
        $assistantMessage = ChatMessageDto::create(
            role: ChatRoleEnum::Assistant,
            content: $apiResponse,
        );
        $this->chatHistory->add($assistantMessage);

        // Dispatch the job to insert the chat message into the database if the user is authenticated
        if ($userId !== 'guest') {
            // The insert would fail if a valid user id is not provided because of the foreign key constraint
            InsertChatMessage::dispatch($userId, $userMessage, $assistantMessage);
        }

        // Log the action
        Log::info('Chat action performed', [
            'action' => $action->value,
            'user_id' => $userId,
        ]);

        // Return the response
        return Controller::successResponse($assistantMessage->toArray());
    }
}
