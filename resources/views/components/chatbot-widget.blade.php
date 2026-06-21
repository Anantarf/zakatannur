@php
    $quickReplies = [
        ['label' => 'Total uang', 'message' => 'Berapa total uang yang terkumpul?'],
        ['label' => 'Total beras', 'message' => 'Berapa total beras yang terkumpul?'],
        ['label' => 'Total jiwa', 'message' => 'Berapa total jiwa zakat fitrah?'],
        ['label' => 'Update terakhir', 'message' => 'Kapan data terakhir diperbarui?'],
        ['label' => 'Lihat grafik', 'action' => 'tab', 'target' => 'grafik'],
        ['label' => 'Cara bayar zakat', 'message' => 'Bagaimana cara membayar zakat?'],
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
        class="zakky-fab group relative flex h-14 w-14 items-center justify-center rounded-full bg-white p-3 text-brand-700 ring-1 ring-brand-200/80 shadow-[0_18px_40px_-12px_rgba(15,118,110,0.42)] transition-transform duration-300 ease-out hover:scale-105 hover:shadow-[0_22px_48px_-12px_rgba(15,118,110,0.5)] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chatbot Zakky"
    >
        <span class="zakky-fab-pulse absolute inset-0 rounded-full bg-brand-400/30" aria-hidden="true"></span>
        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-200/80">
            {!! $messageIcon !!}
        </span>
        <span
            x-show="unreadBadge"
            x-cloak
            class="absolute -top-1 -right-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-sm"
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
        <div class="z-10 flex items-center justify-between border-b border-slate-200/80 bg-white px-4 py-3 text-slate-900">
            <div class="flex items-center space-x-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                    {!! $messageIcon !!}
                </span>
                <div class="min-w-0">
                    <h3 class="text-[15px] font-bold leading-tight text-slate-950">Zakky</h3>
                    <p class="flex items-center text-[11px] text-slate-500">
                        <span
                            class="mr-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"
                            :class="isOnline ? 'animate-pulse' : 'opacity-50'"
                        ></span>
                        <span x-text="isOnline ? 'Online - siap membantu' : 'AI Asisten Zakat An-Nur'"></span>
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="closeChat()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                aria-label="Tutup chatbot"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-ref="chatContainer" class="flex flex-1 flex-col space-y-3 overflow-y-auto bg-slate-50/70 p-4">
            <div class="flex items-start">
                <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm">
                    {!! $messageIcon !!}
                </span>
                <div class="flex max-w-[82%] flex-col items-start">
                    <div class="rounded-2xl rounded-tl-none border border-slate-200/80 bg-white px-3 py-2.5 text-[13px] leading-6 text-slate-800 shadow-sm break-words whitespace-pre-wrap">
                        Assalamu'alaikum. Saya <span class="font-semibold text-brand-700">Zakky</span>. Saya bisa bantu arahkan ke data zakat atau jawab pertanyaan umum.
                    </div>
                    <span class="mt-1 px-1 text-[10px] text-slate-400" x-text="formatTime(welcomeAt)"></span>
                </div>
            </div>

            <div x-show="quickReplies.length > 0 &amp;&amp; messages.length === 0" class="grid grid-cols-2 gap-1.5 pl-9">
                <template x-for="(chip, i) in quickReplies" :key="`chip-${i}`">
                    <button
                        type="button"
                        @click="useQuickReply(chip)"
                        class="min-w-0 rounded-full border border-brand-200 bg-white px-2.5 py-1.5 text-center text-[11px] font-semibold leading-4 text-brand-700 shadow-sm transition-colors hover:bg-brand-50 hover:border-brand-300"
                        x-text="chip.label"
                    ></button>
                </template>
            </div>

            <template x-for="(message, index) in messages" :key="`${message.role}-${index}`">
                <div class="flex w-full" :class="message.role === 'user' ? 'justify-end' : 'justify-start items-start'">
                    <template x-if="message.role === 'bot'">
                        <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm">
                            {!! $messageIcon !!}
                        </span>
                    </template>

                    <div class="flex max-w-[82%] flex-col" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                        <div
                            class="px-3 py-2.5 text-[13px] leading-6 shadow-sm break-words whitespace-pre-wrap"
                            :class="message.role === 'user'
                                ? 'rounded-2xl rounded-tr-none bg-brand-600 text-white'
                                : (message.isError
                                    ? 'rounded-2xl rounded-tl-none border border-amber-200 bg-amber-50 text-amber-900'
                                    : 'rounded-2xl rounded-tl-none border border-slate-200/80 bg-white text-slate-800')"
                            x-text="message.content"
                        ></div>
                        <div class="mt-1 flex items-center gap-1.5 px-1 text-[10px] text-slate-400">
                            <span x-text="formatTime(message.createdAt)"></span>
                            <template x-if="message.citations && message.citations.length > 0">
                                <span class="truncate" x-text="'Sumber: ' + message.citations[0].label"></span>
                            </template>
                            <template x-if="message.role === 'user' &amp;&amp; !message.isError">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="message.isError &amp;&amp; message.isRetryable">
                                <button
                                    type="button"
                                    @click="retryLastMessage()"
                                    class="ml-1 inline-flex items-center gap-1 rounded-full border border-amber-300 bg-white px-2 py-0.5 text-[10px] font-semibold text-amber-700 transition-colors hover:bg-amber-100"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0114.93-3M20 14a8 8 0 01-14.93 3" />
                                    </svg>
                                    Coba lagi
                                </button>
                            </template>
                        </div>
                        <template x-if="message.actions && message.actions.length > 0">
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="(action, actionIndex) in message.actions" :key="`action-${index}-${actionIndex}`">
                                    <button
                                        type="button"
                                        x-show="action.type === 'open_tab' || action.type === 'suggested_reply'"
                                        @click="executeAction(action)"
                                        class="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-[11px] font-bold text-brand-800 transition-colors hover:border-brand-300 hover:bg-brand-100"
                                        x-text="action.label || (action.target === 'grafik' ? 'Lihat Grafik' : 'Buka Ringkasan')"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="isTyping" class="flex items-start">
                <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm">
                    {!! $messageIcon !!}
                </span>
                <div class="flex items-center gap-1 rounded-2xl rounded-tl-none border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
                    <span class="zakky-dot zakky-dot-1 h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    <span class="zakky-dot zakky-dot-2 h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    <span class="zakky-dot zakky-dot-3 h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 bg-white p-3">
            <form @submit.prevent="sendMessage" class="relative flex items-center">
                <input
                    type="text"
                    x-model="input"
                    maxlength="500"
                    class="w-full rounded-full border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-sm text-slate-800 transition-colors placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-60"
                    placeholder="Tulis pesan untuk Zakky..."
                    :disabled="isTyping"
                >
                <button
                    type="submit"
                    class="absolute right-1 flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white transition-all hover:bg-brand-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 px-2">
                <span class="min-w-0 truncate text-[10px] text-slate-400">AI dapat keliru. Verifikasi informasi penting.</span>
                <span class="font-sans tabular-nums text-[10px] text-slate-400" x-text="`${input.length}/500`"></span>
            </div>
        </div>
    </div>
</div>
