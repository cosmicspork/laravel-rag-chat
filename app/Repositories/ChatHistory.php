<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use App\Dtos\ChatMessageDto;

final class ChatHistory
{
    protected const SESSION_KEY = 'chat.messages';

    /**
     * Retrieve all chat messages from the session.
     *
     * @return Collection<ChatMessageDto>
     */
    public function all(): Collection
    {
        // Pull the array of arrays, cast into DTOs
        $data = Session::get(self::SESSION_KEY, []);
        return collect($data)
            ->map(fn (array $item) => ChatMessageDto::fromArray($item));
    }

    /**
     * Retrieve a specific chat message by ID.
     *
     * @param string $id
     * @return ChatMessageDto|null
     */
    public function get(string $id): ?ChatMessageDto
    {
        return $this->all()
            ->first(fn (ChatMessageDto $message) => $message->id === $id);
    }

    /**
     * Add a new chat message to the session.
     *
     * @param ChatMessageDto $message
     * @return void
     */
    public function add(ChatMessageDto $message): void
    {
        // Push the new message (cast to array) onto the session array
        Session::push(self::SESSION_KEY, $message->toArray());
    }

    /**
     * Remove a chat message from the session by ID.
     *
     * @param string $id
     * @return void
     */
    public function remove(string $id): void
    {
        $filtered = $this->all()
            ->reject(fn (ChatMessageDto $message) => $message->id === $id)
            ->map(fn (ChatMessageDto $message) => $message->toArray())
            ->values()
            ->all();

        Session::put(self::SESSION_KEY, $filtered);
    }

    /**
     * Clear all chat messages from the session.
     *
     * @return void
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
