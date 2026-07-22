@props(['embedded' => false])

@php
    $messageIcon = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" class="h-[54%] w-[54%]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 18.5A7.5 7.5 0 1 1 12 21H7l-3 2 1.5-4.5Z" />
        </svg>
    SVG;

    $profileAvatar = '<img src="' . asset('images/zakky-new.webp') . '" alt="Avatar Zakky" class="h-full w-full object-cover scale-125 translate-y-1">';
@endphp

<style>
    /* Thin scrollbar for chatbot */
    .chat-scroll::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }
    .chat-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .chat-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }
    .chat-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
    /* Firefox */
    .chat-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    
    /* Typography & Markdown Styles overrides */
    [data-chatbot-widget] {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
    }
    [data-chatbot-widget] strong {
        font-weight: 700 !important;
        color: #0f172a; /* slate-900 for emphasis */
    }
    [data-chatbot-widget] .text-white strong {
        color: #ffffff; /* keep white for user bubble */
    }
    [data-chatbot-widget] em {
        font-style: italic !important;
    }
    [data-chatbot-widget] .chat-message-shell {
        min-width: 0;
    }
    [data-chatbot-widget] .chat-message-bubble {
        max-width: 100%;
        overflow-wrap: anywhere;
        text-wrap: pretty;
    }
    [data-chatbot-widget] .chat-message-bubble p {
        margin-bottom: 0.45rem;
    }
    [data-chatbot-widget] .chat-message-bubble p:last-child {
        margin-bottom: 0;
    }
</style>

<div
    data-chatbot-widget
    x-data="chatbotWidget({ endpoint: '{{ url('/api/chatbot/message') }}', embedded: {{ $embedded ? 'true' : 'false' }} })"
    x-cloak
    @if($embedded)
        class="flex h-full w-full flex-col items-end"
    @else
        class="fixed bottom-4 right-4 z-50 sm:bottom-6 sm:right-6 flex flex-col items-end"
    @endif
>
    @unless($embedded)
    <!-- Tooltip CTA -->
    <div
        x-show="showTooltip && !isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="relative z-40 mb-4 w-max max-w-[calc(100vw-2rem)] origin-bottom-right"
    >
        <div class="relative rounded-2xl bg-brand-600 px-4 py-3 text-white shadow-xl ring-1 ring-brand-700/50">
            <!-- Close Button -->
            <button
                type="button"
                @click.stop="dismissTooltip()"
                class="absolute right-2.5 top-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-700/50 text-brand-100 transition-colors hover:bg-brand-700 hover:text-white"
                aria-label="Tutup pesan"
                title="Tutup"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            
            <div class="flex items-start gap-3 pr-6">
                <!-- Avatar mini -->
                <div class="mt-0.5 h-10 w-10 shrink-0 overflow-hidden rounded-full border border-brand-400/50 bg-brand-500">
                    {!! $profileAvatar !!}
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight tracking-wide">Assalamu'alaikum!</p>
                    <p class="mt-1 text-xs text-brand-100 leading-relaxed">Ada pertanyaan seputar Zakat?<br>Tanya Zakky di sini 👇</p>
                </div>
            </div>
            
            <!-- Tail pointing to the button -->
            <div class="absolute -bottom-[7px] right-8 z-10 h-4 w-4 rotate-45 rounded-br-sm bg-brand-600 shadow-[2px_2px_2px_rgba(0,0,0,0.05)]"></div>
        </div>
    </div>

    <button
        type="button"
        @click="toggleChat()"
        class="zakky-fab relative flex h-16 w-16 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition-all duration-300 hover:bg-brand-700 hover:shadow-2xl hover:scale-110 hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-95 transform-gpu"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chat"
    >
        <span class="flex h-full w-full items-center justify-center overflow-hidden rounded-full">
            {!! $profileAvatar !!}
        </span>
        <span
            x-show="unreadBadge"
            x-cloak
            class="absolute -top-1 -right-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-md"
            x-text="unreadBadge"
        ></span>
    </button>
    @endunless

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-6 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-6 scale-95 opacity-0"
        @keydown.escape.window="closeChat()"
        @if($embedded)
            class="flex h-full w-full flex-col overflow-hidden bg-white"
        @else
            class="absolute bottom-0 right-0 z-50 flex w-[calc(100vw-2rem)] max-w-[400px] origin-bottom-right flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_60px_-20px_rgba(15,23,42,0.25)] ring-1 ring-slate-200/70 sm:w-[400px]"
            style="height: min(600px, 85vh); max-height: 85vh;"
        @endif
        role="dialog"
        aria-label="Chat dengan Zakky"
    >
        <div class="z-10 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center space-x-3 min-w-0 flex-1">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-600 text-white transform-gpu">
                    {!! $profileAvatar !!}
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-extrabold tracking-tight text-slate-900">Zakky</h3>
                    <p class="flex items-center text-xs text-slate-500">
                        <span
                            class="mr-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="isOnline ? 'bg-green-500 animate-pulse' : 'bg-slate-400'"
                        ></span>
                        <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="closeChat()"
                @if($embedded) style="display:none" @endif
                class="flex h-8 w-8 items-center justify-center rounded text-slate-500 transition-all hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                aria-label="Tutup chat"
                title="Tutup"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-ref="chatContainer" class="chat-scroll flex flex-1 flex-col space-y-3 overflow-y-auto overflow-x-hidden bg-white p-4">
            <template x-for="(message, index) in messages" :key="`${message.role}-${index}`">
                <div class="flex w-full animate-fade-in" data-message :data-index="index" :class="message.role === 'user' ? 'justify-end origin-bottom-right' : 'justify-start items-start origin-bottom-left'">
                    <template x-if="message.role === 'bot'">
                        <span class="mr-3 flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-600 text-white flex-shrink-0 transform-gpu">
                            {!! $profileAvatar !!}
                        </span>
                    </template>

                    <div class="chat-message-shell flex flex-col group" :class="message.role === 'user' ? 'max-w-[84%] items-end' : 'max-w-[calc(100%-2.75rem)] items-start'">
                        <div
                            class="chat-message-bubble px-4 py-3 text-[15px] leading-[1.62] shadow-sm"
                            style="word-break: break-word;"
                            :class="message.role === 'user'
                                ? 'whitespace-pre-wrap rounded-2xl rounded-tr-sm bg-brand-600 text-white'
                                : (message.isError
                                    ? 'rounded-2xl rounded-tl-sm border border-amber-200 bg-amber-50 text-amber-900'
                                    : 'rounded-2xl rounded-tl-sm border border-slate-200 bg-slate-50 text-slate-700')"
                            x-html="formatMessage(message.content, message.role)"
                        ></div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2 px-1 text-xs">
                            <span x-text="formatTime(message.createdAt)" class="text-slate-400 flex-shrink-0"></span>
                            <template x-if="message.citations && message.citations.length > 0">
                                <span class="truncate text-slate-400" x-text="'Acuan: ' + message.citations[0].label"></span>
                            </template>
                            <template x-if="message.role === 'bot' &amp;&amp; !message.isError">
                                <div class="flex gap-1 ml-auto">
                                    <button
                                        type="button"
                                        @click="copyMessage(message.content)"
                                        x-show="!message.feedback"
                                        class="opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity px-2 py-0.5 font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded"
                                        title="Salin pesan"
                                    >
                                        Salin
                                    </button>
                                    <button
                                        type="button"
                                        @click="sendFeedback(index, 'helpful')"
                                        x-show="!message.feedback"
                                        class="opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity px-1.5 text-slate-500 hover:text-green-600"
                                        title="Membantu"
                                        aria-label="Membantu"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 1.95-1.56l1.38-6A2 2 0 0 0 19.66 12H14V9Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="sendFeedback(index, 'unhelpful')"
                                        x-show="!message.feedback"
                                        class="opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity px-1.5 text-slate-500 hover:text-red-600"
                                        title="Tidak membantu"
                                        aria-label="Tidak membantu"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-1.95 1.56l-1.38 6A2 2 0 0 0 4.34 12H10v3Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 2h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3" />
                                        </svg>
                                    </button>
                                    <span x-show="message.feedback" class="inline-flex items-center gap-1 px-2 py-0.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                        </svg>
                                        <span>Terima kasih</span>
                                    </span>
                                </div>
                            </template>
                            <template x-if="message.isError &amp;&amp; message.isRetryable">
                                <button
                                    type="button"
                                    @click="retryLastMessage()"
                                    class="ml-auto inline-flex items-center gap-1 rounded border border-amber-300 bg-white px-2 py-0.5 font-semibold text-amber-700 transition-all hover:bg-amber-100 active:scale-95"
                                    title="Coba ulangi pesan"
                                >
                                    Coba lagi
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="isTyping && (!messages.length || messages[messages.length-1].role === 'user')" class="flex items-start animate-fade-in gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-600 text-white flex-shrink-0 transform-gpu">
                    {!! $profileAvatar !!}
                </span>
                <div class="flex items-center gap-1.5 rounded-2xl rounded-tl-sm border border-slate-200 bg-slate-50 px-3.5 py-2.5 shadow-sm">
                    <span class="zakky-dot zakky-dot-1 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="zakky-dot zakky-dot-2 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="zakky-dot zakky-dot-3 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 bg-white p-3">
            <form @submit.prevent="sendMessage" class="relative flex items-end gap-2">
                <textarea
                    x-ref="chatInput"
                    x-model="input"
                    @input="autoResize()"
                    @keydown="handleKeydown($event)"
                    maxlength="500"
                    rows="1"
                    class="chat-scroll flex-1 resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-[16px] text-slate-800 transition-all placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent disabled:opacity-60 disabled:cursor-not-allowed max-h-[120px] overflow-hidden"
                    style="min-height: 40px;"
                    placeholder="Tanya Zakky..."
                    :disabled="isTyping"
                ></textarea>
                <button
                    type="submit"
                    class="mb-0.5 flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white transition-all hover:bg-brand-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.488 5.951 1.488a1 1 0 001.169-1.409l-7-14z" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="input = ''; clearHistory(); resetToWelcome();"
                    x-show="messages.filter(m => !m.isWelcome).length > 0"
                    class="flex h-10 items-center justify-center rounded-xl px-3 text-xs font-medium text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all active:scale-95 flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
                    aria-label="Reset chat"
                    title="Reset percakapan"
                >
                    Reset
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 px-2 text-xs text-slate-400">
                <span class="flex-1">AI dapat keliru. Verifikasi informasi penting.</span>
                <span class="font-sans tabular-nums flex-shrink-0" x-text="`${input.length}/500`" :class="input.length > 450 ? 'text-amber-600 font-semibold' : ''"></span>
            </div>
        </div>
    </div>
</div>
