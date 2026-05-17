/**
 * Enum for chat feedback.
 * This should mirror app/Enums/ChatFeedbackEnum.php
 */
const ChatFeedbackEnum = {
    Good: 'good',
    Bad: 'bad',

    getValues() {
        return Object.values(this);
    }
};

export default ChatFeedbackEnum;
