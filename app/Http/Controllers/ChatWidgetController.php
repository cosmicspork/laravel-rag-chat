<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Enums\ChatFeedbackEnum;
use App\Http\Requests\ChatFeedbackRequest;
use App\Jobs\InsertChatFeedback;
use App\Models\ChatMessage;
use App\Repositories\ChatHistory;

class ChatWidgetController extends Controller
{
    protected $chatHistory;

    public function __construct(ChatHistory $chatHistory)
    {
        $this->chatHistory = $chatHistory;
    }

    /**
     * Show the chat widget, initializing or capping session as needed.
     *
     * @return View
     */
    public function index(): View
    {
        return view('chat_widget', [
            'chat_history' => $this->chatHistory->all(),
        ]);
    }


    /**
     * Clear the chat history.
     *
     * @return JsonResponse
     */
    public function clearHistory(): JsonResponse
    {
        $this->chatHistory->clear();
        return Controller::successResponse('Chat history cleared');
    }

    /**
     * Process feedback for a specific assistant message.
     *
     * @param ChatFeedbackRequest $request
     * @return JsonResponse
     */
    public function feedback(ChatFeedbackRequest $request): JsonResponse
    {
        // Only authenticated users can provide feedback
        if (! Auth::check()) {
            return Controller::errorResponse('Unauthorized', 401);
        }

        // Get the message and feedback from the request
        $message = $this->chatHistory->get($request->input('message_id'));
        $feedback = ChatFeedbackEnum::from($request->input('feedback'));

        // Find the message in the database
        $chatMessageId = ChatMessage::where('user_id', Auth::id())
            ->whereLike('api_response', $message->content)
            ->value('id');
        if (! $chatMessageId) {
            return Controller::errorResponse('Message not found', 404);
        }

        // Dispatch a job to insert the feedback into the database
        InsertChatFeedback::dispatch(Auth::id(), $chatMessageId, $feedback);

        // Log the feedback
        Log::info('Feedback received', [
            'user_id'      => Auth::id(),
            'message_id'   => $chatMessageId,
            'feedback'     => $feedback->value,
        ]);

        // Return a success response
        return Controller::successResponse([
            'message' => 'Feedback received',
        ]);
    }
}
