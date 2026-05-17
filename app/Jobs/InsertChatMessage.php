<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Dtos\ChatMessageDto;
use App\Models\ChatMessage;

class InsertChatMessage implements ShouldQueue
{
    use Queueable;

    protected int $userId;
    protected ChatMessageDto $userMessage;
    protected ChatMessageDto $apiResponse;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, ChatMessageDto $userMessage, ChatMessageDto $apiResponse)
    {
        $this->userId = $userId;
        $this->userMessage = $userMessage;
        $this->apiResponse = $apiResponse;
    }

    /**
     * Execute the job.
     * 
     * @return void
     */
    public function handle(): void
    {
        ChatMessage::insert([
            'user_id' => $this->userId,
            'user_message' => $this->userMessage->content,
            'api_response' => $this->apiResponse->content,
        ]);
    }
}