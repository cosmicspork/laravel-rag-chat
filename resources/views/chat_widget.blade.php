<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat Widget</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="chat-proxy-url" content="{{ route('chat.proxy') }}">
  <meta name="chat-clear-url" content="{{ route('chat.clear') }}">
  <meta name="chat-feedback-url" content="{{ route('chat.feedback') }}">
</head>
<body class="bg-gray-100">
  <div class="max-w-md mx-auto mt-10 bg-white shadow-lg rounded-lg p-6" role="chat">
    <section id="chat-history" class="h-80 overflow-y-auto border border-gray-200 rounded p-4 mb-4" aria-live="polite">
      @if(!empty($chat_history))
        @foreach($chat_history as $message)
          @if($message->role->value === 'user' || $message->role->value === 'assistant')
            <div
              class="mb-2 message-item"
              data-role="{{ $message->role->value }}"
              data-message-id="{{ $message->id }}"
              data-tokens="{{ $message->tokens }}"
            >
              <div
                class="text-sm {{ $message->role->value === 'user' ? 'text-right text-blue-600' : 'text-left text-gray-800' }}"
                data-content-id="{{ $message->id }}"
              >
                {!! nl2br(e($message->content)) !!}
              </div>
            </div>
          @endif
        @endforeach
      @endif
    </section>

    <form id="chat-form" class="space-y-4" aria-label="Chat Input">
      <div class="flex space-x-2">
        <input
          type="text"
          id="chat-input"
          placeholder="Type your message"
          required
          maxlength="500"
          class="flex-grow border border-gray-300 rounded p-2 focus:outline-none focus:ring focus:border-blue-300"
          aria-label="Message input"
        >
        <button
          type="submit"
          id="send-message"
          class="bg-blue-600 hover:bg-blue-700 text-white rounded px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
          aria-label="Send message"
        >
          Send
        </button>
      </div>
      <div class="flex justify-end">
        <button
          type="button"
          id="clear-chat"
          class="bg-red-600 hover:bg-red-700 text-white rounded px-4 py-2"
          aria-label="Clear chat history"
        >
          Clear Chat History
        </button>
      </div>
    </form>

    <p class="mt-4 text-center text-xs text-gray-500" role="note">
      You are chatting with an AI. Please verify the accuracy of the information provided.
    </p>
  </div>
  <svg style="display: none;">
    <symbol id="icon-copy" viewBox="0 0 24 24">
      <path fill-rule="evenodd" clip-rule="evenodd" d="M19.5 16.5L19.5 4.5L18.75 3.75H9L8.25 4.5L8.25 7.5L5.25 7.5L4.5 8.25V20.25L5.25 21H15L15.75 20.25V17.25H18.75L19.5 16.5ZM15.75 15.75L15.75 8.25L15 7.5L9.75 7.5V5.25L18 5.25V15.75H15.75ZM6 9L14.25 9L14.25 19.5L6 19.5L6 9Z" fill="#080341"/>
    </symbol>
    <symbol id="icon-thumbs-up" viewBox="0 0 24 24">
      <path d="M7 22V11M2 13V20C2 21.1046 2.89543 22 4 22H17.4262C18.907 22 20.1662 20.9197 20.3914 19.4562L21.4683 12.4562C21.7479 10.6389 20.3418 9 18.5032 9H15C14.4477 9 14 8.55228 14 8V4.46584C14 3.10399 12.896 2 11.5342 2C11.2093 2 10.915 2.1913 10.7831 2.48812L7.26394 10.4061C7.10344 10.7673 6.74532 11 6.35013 11H4C2.89543 11 2 11.8954 2 13Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </symbol>
    <symbol id="icon-thumbs-down" viewBox="0 0 24 24">
      <path d="M17.0001 2V13M22.0001 9.8V5.2C22.0001 4.07989 22.0001 3.51984 21.7821 3.09202C21.5903 2.71569 21.2844 2.40973 20.908 2.21799C20.4802 2 19.9202 2 18.8001 2H8.11806C6.65658 2 5.92584 2 5.33563 2.26743C4.81545 2.50314 4.37335 2.88242 4.06129 3.36072C3.70722 3.90339 3.59611 4.62564 3.37388 6.07012L2.8508 9.47012C2.5577 11.3753 2.41114 12.3279 2.69386 13.0691C2.94199 13.7197 3.4087 14.2637 4.01398 14.6079C4.70358 15 5.66739 15 7.59499 15H8.40005C8.96011 15 9.24013 15 9.45404 15.109C9.64221 15.2049 9.79519 15.3578 9.89106 15.546C10.0001 15.7599 10.0001 16.0399 10.0001 16.6V19.5342C10.0001 20.896 11.104 22 12.4659 22C12.7907 22 13.0851 21.8087 13.217 21.5119L16.5778 13.9502C16.7306 13.6062 16.807 13.4343 16.9278 13.3082C17.0346 13.1967 17.1658 13.1115 17.311 13.0592C17.4753 13 17.6635 13 18.0398 13H18.8001C19.9202 13 20.4802 13 20.908 12.782C21.2844 12.5903 21.5903 12.2843 21.7821 11.908C22.0001 11.4802 22.0001 10.9201 22.0001 9.8Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </symbol>
    <symbol id="icon-refresh" viewBox="0 0 24 24">
      <path d="M21 12C21 16.9706 16.9706 21 12 21C9.69494 21 7.59227 20.1334 6 18.7083L3 16M3 12C3 7.02944 7.02944 3 12 3C14.3051 3 16.4077 3.86656 18 5.29168L21 8M3 21V16M3 16H8M21 3V8M21 8H16" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </symbol>
  </svg>
</body>
</html>