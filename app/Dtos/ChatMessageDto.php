<?php

declare(strict_types=1);

namespace App\Dtos;

use Yethee\Tiktoken\EncoderProvider;
use App\Enums\ChatRoleEnum;

/**
 * Data Transfer Object for chat messages.
 * If modifying this, ensure to update the JavaScript counterpart.
 */
final class ChatMessageDto
{
    public function __construct(
        public readonly string $id,
        public readonly ChatRoleEnum $role,
        public readonly string $content,
        public int $tokens = 0,
    ) {
        // Ensure role is valid
        if (!in_array($this->role->value, ChatRoleEnum::getValues())) {
            throw new \InvalidArgumentException('Invalid role.');
        }

        // Ensure content is not empty
        if (empty($this->content)) {
            throw new \InvalidArgumentException('Content cannot be empty.');
        }

        // Ensure tokens are non-negative
        if ($this->tokens < 0) {
            throw new \InvalidArgumentException('Tokens cannot be negative.');
        }

        // Calculate and set the tokens if not provided
        if ($this->tokens === 0) {
            $this->tokens = $this->countTokens();
        }
    }

    /**
     * For creating brand-new messages
     *
     * @param ChatRoleEnum $role
     * @param string $content
     * @return self
    */
    public static function create(ChatRoleEnum $role, string $content): self
    {
        $id = uniqid('chatmsg_');
        return new self($id, $role, $content);
    }

    /**
     * Count tokens for a message.
     *
     * @return int
     */
    public function countTokens(): int
    {
        $provider = new EncoderProvider();
        $encoder = $provider->getForModel((string) config('chat.completion.model'));
        return count($encoder->encode($this->content));
    }

    /**
     * Convert to raw array (e.g. for request payload).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'      => $this->id,
            'role'    => $this->role->value,
            'content' => $this->content,
            'tokens'  => $this->tokens,
        ];
    }

    /**
     * Create from raw array (e.g. from request payload).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            ChatRoleEnum::from($data['role']),
            $data['content'],
            $data['tokens'],
        );
    }
}
