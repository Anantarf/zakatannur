@php
    $quickReplies = [
        ['label' => 'Total uang', 'message' => 'Berapa total uang yang terkumpul?'],
        ['label' => 'Total beras', 'message' => 'Berapa total beras yang terkumpul?'],
        ['label' => 'Total jiwa', 'message' => 'Berapa total jiwa zakat fitrah?'],
        ['label' => 'Update terakhir', 'message' => 'Kapan data terakhir diperbarui?'],
        ['label' => 'Lihat grafik', 'action' => 'tab', 'target' => 'grafik'],
        ['label' => 'Cara bayar', 'message' => 'Bagaimana cara membayar zakat?'],
    ];

    $messageIcon = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" class="h-[54%] w-[54%]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 18.5A7.5 7.5 0 1 1 12 21H7l-3 2 1.5-4.5Z" />
        </svg>
    SVG;
@endphp

<div
    data-chatbot-widget
    x-data="chatbotWidget({ endpoint: '{{ url('/api/chatbot/message') }}', quickReplies: {{ json_encode($quickReplies) }} })"
    x-cloak
    class="fixed bottom-4 right-4 z-50 sm:bottom-6 sm:right-6"
>
    <button
        type="button"
        @click="toggleChat()"
        class="zakky-fab relative flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition-all duration-300 hover:bg-brand-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-95"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chat"
    >
        <span class="flex items-center justify-center">
            {!! $messageIcon !!}
        </span>
        <span
            x-show="unreadBadge"
            x-cloak
            class="absolute -top-1 -right-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-md"
            x-text="unreadBadge"
        ></span>
    </button>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-6 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-6 scale-95 opacity-0"
        @keydown.escape.window="closeChat()"
        class="flex w-[calc(100vw-1.5rem)] max-w-[24rem] origin-bottom-right flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_60px_-20px_rgba(15,23,42,0.25)] ring-1 ring-slate-200/70 sm:w-96"
        style="height: min(500px, 78vh); max-height: 78vh;"
        role="dialog"
        aria-label="Chat dengan Zakky"
    >
        <div class="z-10 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center space-x-3 min-w-0 flex-1">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
                    {!! $messageIcon !!}
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-slate-900">Zakky</h3>
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
                class="flex h-8 w-8 items-center justify-center rounded text-slate-500 transition-all hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                aria-label="Tutup chat"
                title="Tutup"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-ref="chatContainer" class="flex flex-1 flex-col space-y-4 overflow-y-auto bg-white p-4">
            <!-- Welcome Message -->
            <div class="flex items-start animate-fade-in gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white flex-shrink-0">
                    {!! $messageIcon !!}
                </span>
                <div class="flex flex-col items-start min-w-0">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm leading-6 text-slate-800 break-words">Assalamu'alaikum. Saya <span class="font-semibold text-brand-700">Zakky</span>. Saya bisa bantu cek data zakat atau jawab pertanyaan Anda.</div>
                    <span class="mt-2 text-xs text-slate-400" x-text="formatTime(welcomeAt)"></span>
                </div>
            </div>

            <!-- Quick Replies -->
            <div x-show="quickReplies.length > 0 &amp;&amp; messages.length === 0" class="animate-fade-in" style="animation-delay: 100ms;">
                <div class="grid grid-cols-2 gap-1.5">
                    <template x-for="(chip, i) in quickReplies" :key="`chip-${i}`">
                        <button
                            type="button"
                            @click="useQuickReply(chip)"
                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 font-medium transition-all hover:border-brand-300 hover:bg-brand-50 active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                            :title="chip.label"
                        >
                            <span class="block truncate" x-text="chip.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <template x-for="(message, index) in messages" :key="`${message.role}-${index}`">
                <div class="flex w-full animate-fade-in" :class="message.role === 'user' ? 'justify-end' : 'justify-start items-start'" style="animation-duration: 300ms;">
                    <template x-if="message.role === 'bot'">
                        <span class="mr-3 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white flex-shrink-0">
                            {!! $messageIcon !!}
                        </span>
                    </template>

                    <div class="flex max-w-[75%] flex-col group" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                        <div
                            class="px-3 py-2 text-sm leading-6 break-words whitespace-pre-wrap"
                            :class="message.role === 'user'
                                ? 'rounded-lg rounded-tr-none bg-brand-600 text-white'
                                : (message.isError
                                    ? 'rounded-lg rounded-tl-none border border-amber-200 bg-amber-50 text-amber-900'
                                    : 'rounded-lg rounded-tl-none border border-slate-200 bg-white text-slate-800')"
                            x-text="message.content"
                        ></div>
                        <div class="mt-1 flex items-center gap-2 px-1 text-xs text-slate-400">
                            <span x-text="formatTime(message.createdAt)" class="flex-shrink-0"></span>
                            <template x-if="message.citations && message.citations.length > 0">
                                <span class="truncate text-xs" x-text="'Sumber: ' + message.citations[0].label"></span>
                            </template>
                            <template x-if="message.role === 'bot' &amp;&amp; !message.isError">
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText(message.content)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-2 py-0.5 text-xs font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded"
                                    title="Salin pesan"
                                    aria-label="Salin pesan"
                                >
                                    Salin
                                </button>
                            </template>
                            <template x-if="message.isError &amp;&amp; message.isRetryable">
                                <button
                                    type="button"
                                    @click="retryLastMessage()"
                                    class="ml-auto inline-flex items-center gap-1 rounded border border-amber-300 bg-white px-2 py-0.5 text-xs font-semibold text-amber-700 transition-all hover:bg-amber-100 active:scale-95"
                                    title="Coba ulangi pesan"
                                >
                                    Coba lagi
                                </button>
                            </template>
                        </div>
                        <template x-if="message.actions && message.actions.length > 0">
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="(action, actionIndex) in message.actions" :key="`action-${index}-${actionIndex}`">
                                    <button
                                        type="button"
                                        x-show="action.type === 'open_tab' || action.type === 'suggested_reply'"
                                        @click="executeAction(action)"
                                        class="rounded border border-brand-200 bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 transition-all hover:border-brand-300 hover:bg-brand-100 active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                                        :title="action.label || (action.target === 'grafik' ? 'Lihat Grafik' : 'Buka Ringkasan')"
                                        x-text="action.label || (action.target === 'grafik' ? 'Lihat Grafik' : 'Buka Ringkasan')"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="isTyping" class="flex items-start animate-fade-in gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white flex-shrink-0">
                    {!! $messageIcon !!}
                </span>
                <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2">
                    <span class="zakky-dot zakky-dot-1 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="zakky-dot zakky-dot-2 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="zakky-dot zakky-dot-3 h-2 w-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 bg-white p-3">
            <form @submit.prevent="sendMessage" class="relative flex items-center gap-2">
                <input
                    type="text"
                    x-model="input"
                    maxlength="500"
                    class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition-all placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent disabled:opacity-60 disabled:cursor-not-allowed"
                    placeholder="Tanya Zakky..."
                    :disabled="isTyping"
                    autocomplete="off"
                >
                <button
                    type="submit"
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white transition-all hover:bg-brand-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.488 5.951 1.488a1 1 0 001.169-1.409l-7-14z" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="messages = []; input = ''"
                    x-show="messages.length > 0"
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all active:scale-95 flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
                    aria-label="Hapus riwayat chat"
                    title="Hapus riwayat chat"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 px-2 text-xs text-slate-400">
                <span class="flex-1">AI dapat keliru. Verifikasi informasi penting.</span>
                <span class="font-sans tabular-nums flex-shrink-0" x-text="`${input.length}/500`" :class="input.length > 450 ? 'text-amber-600 font-semibold' : ''"></span>
            </div>
        </div>
    </div>
</div>
