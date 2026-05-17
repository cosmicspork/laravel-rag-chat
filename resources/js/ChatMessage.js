import ChatRoleEnum from "./ChatRole";

/**
 * Data Transfer Object for chat messages.
 * This should mirror app/Dtos/ChatMessageDto.php
 */
class ChatMessageDto {
    constructor(id, role, content, tokens = 0) {
        // Ensure role is valid
        if (!ChatRoleEnum.hasValue(role)) {
            throw new Error('Invalid role.');
        }

        // Ensure content is not empty
        if (!content) {
            throw new Error('Content cannot be empty.');
        }

        // Ensure tokens are non-negative
        if (tokens < 0) {
            throw new Error('Tokens cannot be negative.');
        }

        this.id = id;
        this.role = role;
        this.content = content;
        this.tokens = tokens;
    }

    toArray() {
        return {
            id: this.id,
            role: this.role,
            content: this.content,
            tokens: this.tokens
        };
    }

    static fromArray(data) {
        return new ChatMessageDto(
            data.id,
            data.role,
            data.content,
            data.tokens
        );
    }
}

export default ChatMessageDto;
