import { marked } from 'marked';
import DOMPurify from 'dompurify';
import md5 from 'md5';
import ChatActionEnum from './ChatAction';
import ChatFeedbackEnum from './ChatFeedback';
import ChatMessageDto from './ChatMessage';
import ChatRoleEnum from './ChatRole';

/**
 * ChatApp class to handle chat functionalities.
 */
class ChatApp {
    constructor() {
        // Cache DOM elements
        this.chatHistoryElement = document.getElementById('chat-history');
        this.chatFormElement = document.getElementById('chat-form');
        this.chatInputElement = document.getElementById('chat-input');
        this.sendMessageButtonElement = document.getElementById('send-message');
        this.clearChatButtonElement = document.getElementById('clear-chat');

        // Cache CSRF token and URLs
        this.csrfTokenValue = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.chatProxyUrl = document.querySelector('meta[name="chat-proxy-url"]').getAttribute('content');
        this.chatClearUrl = document.querySelector('meta[name="chat-clear-url"]').getAttribute('content');
        this.chatFeedbackUrl = document.querySelector('meta[name="chat-feedback-url"]').getAttribute('content');

        this.setupEventListeners();
    }

    /**
     * Setup event listeners for form submission, clear chat, and action icon clicks.
     */
    setupEventListeners() {
        this.chatFormElement.addEventListener('submit', (event) => this.handleFormSubmit(event));
        this.clearChatButtonElement.addEventListener('click', () => this.handleClearChat());

        // Re-process any existing raw text messages on page load
        document.addEventListener('DOMContentLoaded', () => this.processExistingMessages());
    }

    /**
     * Make a fetch call to a specified URL with optional method and body.
     * 
     * @param {string} url - The URL to fetch.
     * @param {string} method - The HTTP method (default is 'POST').
     * @param {Object|null} body - The request body to be sent (optional).
     * @returns {Promise<Object>} - The JSON response from the fetch call.
     */
    async fetchCall(url, method = 'POST', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfTokenValue
            }
        };
        if (body) {
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    }

    /**
     * Handles the form submission to send a user message.
     * 
     * @param {Event} event - The form submit event.
     */
    handleFormSubmit(event) {
        event.preventDefault();
        const userMessage = this.chatInputElement.value.trim();
        if (userMessage === '') {
            console.warn('Attempted to send an empty message.');
            return;
        }
        document.querySelectorAll('.action-icons').forEach(container => container.remove());
        this.appendMessageToHistory(
            new ChatMessageDto(
                `unknown_chatmsg_${md5(userMessage)}`, // This will be replaced by the backend with a real ID on the next page load
                ChatRoleEnum.User,
                userMessage,
                0 // Tokens will be calculated by the backend
            )
        );
        this.showLoadingSpinner();
        this.chatInputElement.value = '';

        // Call the fetch method
        this.fetchCall(this.chatProxyUrl, 'POST', { action: ChatActionEnum.Completion, message: userMessage })
            .then(data => {
                this.hideLoadingSpinner();
                this.appendMessageToHistory(ChatMessageDto.fromArray(data));
            })
            .catch(error => {
                this.hideLoadingSpinner();
                console.error('Fetch error:', error);
                this.appendMessageToHistory(
                    new ChatMessageDto(
                        `error_chatmsg_${md5(error.message)}`, // This will be destroyed on the next page load
                        ChatRoleEnum.Assistant, // Use the assistant role when displaying errors to the user
                        `Network error: ${error.message}`,
                        0
                    )
                );
            });
    }

    /**
     * Handles the clear chat button click to clear chat history.
     */
    handleClearChat() {
        this.fetchCall(this.chatClearUrl)
            .then(() => {
                this.chatHistoryElement.innerHTML = '';
            })
            .catch(clearError => {
                console.error('Clear chat error:', clearError);
                this.appendMessageToHistory(
                    new ChatMessageDto(
                        `error_chatmsg_${md5(clearError.message)}`, // This will be destroyed on the next page load
                        ChatRoleEnum.Assistant, // Use the assistant role when displaying errors to the user
                        `Network error: ${clearError.message}`,
                        0
                    )
                );
            });
    }

    /**
     * Handles clicks on action icons in the chat history.
     * 
     * @param {Event} event - The click event on the chat history.
     */
    handleActionIconClick(event) {
        // Determine the action type and message ID from the clicked button
        const clickedButton = event.target.closest('button.action-icon');
        const actionType = clickedButton.getAttribute('data-action-type');
        const messageId = clickedButton.getAttribute('data-message-id');
        if (!actionType || !messageId) {
            console.error(`Missing data attribute(s) for button: ${clickedButton}`);
            return;
        }

        // Find the corresponding content element for the message ID
        const contentElement = document.querySelector(`[data-content-id="${messageId}"]`);
        if (!contentElement) {
            console.error(`Content element not found for message ID: ${messageId}`);
            return;
        }

        // Perform the action based on the action type
        switch (actionType) {
            case 'copy':
                navigator.clipboard.writeText(contentElement.textContent)
                    .catch(copyError => console.error('Copy to clipboard failed:', copyError));
                break;
            case ChatFeedbackEnum.Good:
            case ChatFeedbackEnum.Bad:
                this.fetchCall(this.chatFeedbackUrl, 'POST', { message_id: messageId, feedback: actionType })
                    .catch(feedbackError => console.error('Feedback error:', feedbackError));
                break;
            case ChatActionEnum.Regeneration:
                clickedButton.disabled = true;
                this.chatHistoryElement.removeChild(contentElement.parentElement);
                this.showLoadingSpinner(); // Show spinner while fetching new message
                this.fetchCall(this.chatProxyUrl, 'POST', { action: ChatActionEnum.Regeneration, message_id: messageId })
                    .then(data => {
                        if (data.role === ChatRoleEnum.Assistant) {
                            this.hideLoadingSpinner();
                            this.appendMessageToHistory(
                                ChatMessageDto.fromArray(data)
                            );
                        } else {
                            console.error('Unexpected regeneration response:', data);
                        }
                    })
                    .catch(regenerationError => console.error('Regeneration error:', regenerationError))
                    .finally(() => {
                        clickedButton.disabled = false;
                    });
                break;
            default:
                console.warn(`Unhandled action type: ${actionType}`);
                break;
        }
    }

    /**
     * Processes existing messages for markdown rendering upon page load.
     */
    processExistingMessages() {
        document.querySelectorAll('#chat-history .text-sm').forEach(textElement => {
            const rawText = textElement.textContent;
            textElement.innerHTML = '';
            textElement.appendChild(this.processMarkdown(rawText));
        });
        const lastAssistantMessage = document.querySelector(`#chat-history [data-role="${ChatRoleEnum.Assistant}"]:last-child`);
        if (lastAssistantMessage) {
            lastAssistantMessage.appendChild(this.createActionIcons(lastAssistantMessage.getAttribute('data-message-id')));
        }
    }

    /**
     * Processes raw markdown text into a sanitized HTML container with Tailwind Typography.
     * 
     * @param {string} rawText - The raw markdown text.
     * @returns {HTMLElement} - The HTML element containing the processed markdown.
     */
    processMarkdown(rawText) {
        // Ensure rawText is a string 
        if (typeof rawText !== 'string') {
            console.error('Expected a string for rawText but received:', rawText);
            const container = document.createElement('div');
            container.textContent = 'Error processing message: Invalid message format.';
            return container;
        }
        // Create a container for the markdown content
        const markdownContainerElement = document.createElement('div');
        markdownContainerElement.classList.add('prose');
        markdownContainerElement.innerHTML = DOMPurify.sanitize(
            marked.parse(rawText.trim())
        );
        return markdownContainerElement;
    }

    /**
     * Appends a message to the chat history.
     * 
     * @param {ChatMessageDto} chatMessageDto - The chat message data transfer object.
     */
    appendMessageToHistory(chatMessageDto) {
        // Create the message wrapper
        const messageWrapperElement = document.createElement('div');
        messageWrapperElement.classList.add('mb-2');
        messageWrapperElement.setAttribute('data-role', chatMessageDto.role);
        messageWrapperElement.setAttribute('data-message-id', chatMessageDto.id);
        messageWrapperElement.setAttribute('data-tokens', chatMessageDto.tokens);

        // Create the message text container
        const messageTextContainer = document.createElement('div');
        messageTextContainer.classList.add('text-sm');
        if (chatMessageDto.role === ChatRoleEnum.User) {
            messageTextContainer.classList.add('text-right', 'text-blue-600');
        } else {
            messageTextContainer.classList.add('text-left', 'text-gray-800');
        }
        messageTextContainer.setAttribute('data-content-id', chatMessageDto.id);

        // Process and append the message content
        messageTextContainer.appendChild(this.processMarkdown(chatMessageDto.content));
        messageWrapperElement.appendChild(messageTextContainer);

        // Remove any existing action icons
        document.querySelectorAll('.action-icons').forEach(container => container.remove());

        // Append the new message and action icons if it's from the assistant
        this.chatHistoryElement.appendChild(messageWrapperElement);
        if (chatMessageDto.role === ChatRoleEnum.Assistant) {
            messageWrapperElement.appendChild(this.createActionIcons(chatMessageDto.id));
        }

        // Scroll to the bottom of the chat history
        this.chatHistoryElement.scrollTop = this.chatHistoryElement.scrollHeight;
    }

    /**
     * Displays a loading spinner and disables input and send button.
     */
    showLoadingSpinner() {
        if (document.getElementById('loading-spinner')) {
            return;
        }
        const spinnerWrapperElement = document.createElement('div');
        spinnerWrapperElement.id = 'loading-spinner';
        spinnerWrapperElement.classList.add('flex', 'items-center', 'mb-2');
        spinnerWrapperElement.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-gray-600 mr-2"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24"
             aria-label="Loading">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>`;
        this.chatHistoryElement.appendChild(spinnerWrapperElement);
        this.chatHistoryElement.scrollTop = this.chatHistoryElement.scrollHeight;

        this.chatInputElement.disabled = true;
        this.sendMessageButtonElement.disabled = true;
    }

    /**
     * Removes the loading spinner and re-enables input & send button.
     */
    hideLoadingSpinner() {
        const spinnerElement = document.getElementById('loading-spinner');
        if (spinnerElement) {
            spinnerElement.remove();
        }

        this.chatInputElement.disabled = false;
        this.sendMessageButtonElement.disabled = false;
        this.chatInputElement.focus();
    }

    /**
     * Creates action icons for assistant messages.
     * 
     * @param {string} messageId - The ID of the message associated with the icons.
     * @returns {HTMLElement} - The action icons container.
     */
    createActionIcons(messageId) {
        // Create action icons container for assistant messages
        const actionIconsContainer = document.createElement('div');
        actionIconsContainer.classList.add('action-icons', 'flex', 'justify-end', 'space-x-3', 'mt-1', 'text-gray-500');

        // Define action buttons
        const actionButtonData = [
            { actionType: 'copy', label: 'Copy response', icon: 'icon-copy' },
            { actionType: ChatFeedbackEnum.Good, label: 'Mark response as good', icon: 'icon-thumbs-up' },
            { actionType: ChatFeedbackEnum.Bad, label: 'Mark response as bad', icon: 'icon-thumbs-down' },
            { actionType: ChatActionEnum.Regeneration, label: 'Regenerate response', icon: 'icon-refresh' }
        ];

        // Append buttons to the action icons container and attach event listeners
        actionButtonData.forEach(({ actionType, label, icon }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.classList.add('action-icon', `hover:text-${actionType === ChatActionEnum.Regeneration ? 'blue' : actionType === ChatFeedbackEnum.Bad ? 'red' : 'green'}-600`);
            button.setAttribute('data-action-type', actionType);
            button.setAttribute('aria-label', label);
            button.setAttribute('data-message-id', messageId);
            button.innerHTML = `<svg class="h-5 w-5"><use xlink:href="#${icon}"/></svg>`;

            // Attach event listener directly to the button
            button.addEventListener('click', (event) => this.handleActionIconClick(event));

            actionIconsContainer.appendChild(button);
        });

        return actionIconsContainer;
    }
}

export default ChatApp;
