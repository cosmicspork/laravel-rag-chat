<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ChatFeedbackEnum;

class ChatFeedbackRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Validates message id is a string
            'message_id' => 'required|string',
            // Validates feedback type
            'feedback'      => 'required|in:' . implode(',', ChatFeedbackEnum::getValues()),
        ];
    }
}
