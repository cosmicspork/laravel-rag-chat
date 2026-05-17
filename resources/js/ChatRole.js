/**
 * Enum for chat roles.
 * This should mirror app/Enums/ChatRoleEnum.php
 */
const ChatRoleEnum = {
    System: 'system',
    User: 'user',
    Assistant: 'assistant',

    getValues() {
        return Object.values(this);
    },

    hasValue(value) {
        return this.getValues().includes(value);
    }
}

export default ChatRoleEnum;
