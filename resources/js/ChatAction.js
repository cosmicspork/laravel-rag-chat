/**
 * Enum for chat actions.
 * This should mirror app/Enums/ChatActionEnum.php
 */
const ChatActionEnum = {
    Completion: 'completion',
    Regeneration: 'regeneration',
    
    getValues() {
        return Object.values(this);
    }
}

export default ChatActionEnum;
