<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ChatActionEnum;

class ChatProxyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
             // Ensures valid action is provided
            'action' => 'required|in:' . implode(',', ChatActionEnum::getValues()),
            // Validates message only if action is completion
            'message' => 'required_if:action,' . ChatActionEnum::Completion->value . '|string',
            // Validates message id only if action is regeneration
            'message_id' => 'required_if:action,' . ChatActionEnum::Regeneration->value . '|string',
        ];
    }
}
