<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\ChatFeedbackEnum;
use App\Models\ChatFeedback;


class InsertChatFeedback implements ShouldQueue
{
    use Queueable;

    protected int $userId;
    protected int $chatMessageId;
    protected ChatFeedbackEnum $feedbackType;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, int $chatMessageId, ChatFeedbackEnum $feedbackType)
    {
        $this->userId = $userId;
        $this->chatMessageId = $chatMessageId;
        $this->feedbackType = $feedbackType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Insert the feedback into the database
        ChatFeedback::upsert(
            [   // Values to insert or update
                'user_id'         => $this->userId,
                'chat_message_id' => $this->chatMessageId,
                'feedback'        => $this->feedbackType,
            ],
            ['user_id', 'chat_message_id'], // Unique identifiers
            ['feedback'] // Column(s) to update if a record exists
        );
    }
}
