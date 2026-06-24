@php
    $quickReplies = [
        ['label' => '💰 Total uang', 'message' => 'Berapa total uang yang terkumpul?', 'emoji' => '💰'],
        ['label' => '🌾 Total beras', 'message' => 'Berapa total beras yang terkumpul?', 'emoji' => '🌾'],
        ['label' => '👥 Total jiwa', 'message' => 'Berapa total jiwa zakat fitrah?', 'emoji' => '👥'],
        ['label' => '🔄 Update terakhir', 'message' => 'Kapan data terakhir diperbarui?', 'emoji' => '🔄'],
        ['label' => '📊 Lihat grafik', 'action' => 'tab', 'target' => 'grafik', 'emoji' => '📊'],
        ['label' => '❓ Cara bayar', 'message' => 'Bagaimana cara membayar zakat?', 'emoji' => '❓'],
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
        class="zakky-fab group relative flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white ring-1 ring-brand-300/50 shadow-[0_20px_45px_-12px_rgba(15,118,110,0.5)] transition-all duration-300 ease-out hover:scale-110 hover:shadow-[0_25px_55px_-12px_rgba(15,118,110,0.6)] focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 active:scale-95"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chatbot Zakky"
    >
        <span class="zakky-fab-pulse absolute inset-0 rounded-full bg-white/20 animate-pulse" aria-hidden="true"></span>
        <span class="relative z-10 flex items-center justify-center">
            {!! $messageIcon !!}
        </span>
        <span
            x-show="unreadBadge"
            x-cloak
            class="absolute -top-1 -right-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-red-600 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-md animate-bounce"
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
        <div class="z-10 flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-white to-brand-50 px-4 py-3 sm:py-4">
            <div class="flex items-center space-x-3 min-w-0 flex-1">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white ring-1 ring-brand-300/50 shadow-md">
                    {!! $messageIcon !!}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-[15px] sm:text-base font-bold leading-tight text-slate-950">Zakky 👋</h3>
                    </div>
                    <p class="flex items-center text-[11px] text-slate-500">
                        <span
                            class="mr-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="isOnline ? 'bg-green-500 animate-pulse' : 'bg-slate-400'"
                        ></span>
                        <span x-text="isOnline ? 'Online & siap bantu' : 'Offline saat ini'"></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1 flex-shrink-0">
                <button
                    type="button"
                    @click="closeChat()"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition-all hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1 active:scale-95"
                    aria-label="Tutup chat"
                    title="Tutup chat (Esc)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-ref="chatContainer" class="flex flex-1 flex-col space-y-3 overflow-y-auto bg-gradient-to-b from-slate-50/50 to-white p-4">
            <!-- Welcome Message -->
            <div class="flex items-start animate-fade-in">
                <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-md flex-shrink-0">
                    {!! $messageIcon !!}
                </span>
                <div class="flex max-w-[82%] flex-col items-start min-w-0">
                    <div class="rounded-2xl rounded-tl-none border border-brand-100 bg-gradient-to-br from-white to-brand-50 px-3.5 py-3 text-[13px] leading-6 text-slate-800 shadow-sm break-words whitespace-pre-wrap">
                        <span class="text-lg mr-1">👋</span> Assalamu'alaikum! Saya <span class="font-bold text-brand-700">Zakky</span>, asisten zakat Anda.
                        <div class="mt-2 text-[12px] text-slate-600">Saya bisa bantu cek data zakat atau jawab pertanyaan Anda.</div>
                    </div>
                    <span class="mt-1.5 px-1 text-[10px] text-slate-400" x-text="formatTime(welcomeAt)"></span>
                </div>
            </div>

            <!-- Quick Replies -->
            <div x-show="quickReplies.length > 0 &amp;&amp; messages.length === 0" class="pl-9 space-y-2 animate-fade-in" style="animation-delay: 100ms;">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Pertanyaan cepat:</div>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="(chip, i) in quickReplies" :key="`chip-${i}`">
                        <button
                            type="button"
                            @click="useQuickReply(chip)"
                            class="group relative overflow-hidden min-w-0 rounded-xl border border-brand-150 bg-white px-3 py-2.5 text-left text-[12px] font-semibold leading-tight text-brand-800 shadow-sm transition-all hover:bg-brand-50 hover:border-brand-300 hover:shadow-md active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1"
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
                        <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-md flex-shrink-0">
                            {!! $messageIcon !!}
                        </span>
                    </template>

                    <div class="flex max-w-[82%] flex-col group" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                        <div
                            class="px-3.5 py-2.5 text-[13px] leading-6 shadow-sm break-words whitespace-pre-wrap"
                            :class="message.role === 'user'
                                ? 'rounded-2xl rounded-tr-none bg-gradient-to-br from-brand-600 to-brand-700 text-white'
                                : (message.isError
                                    ? 'rounded-2xl rounded-tl-none border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100/50 text-amber-900 font-medium'
                                    : 'rounded-2xl rounded-tl-none border border-slate-200/80 bg-white text-slate-800')"
                            x-text="message.content"
                        ></div>
                        <div class="mt-1.5 flex items-center gap-2 px-1 text-[10px] text-slate-400">
                            <span x-text="formatTime(message.createdAt)" class="flex-shrink-0"></span>
                            <template x-if="message.citations && message.citations.length > 0">
                                <span class="truncate text-[10px]" x-text="'📌 ' + message.citations[0].label"></span>
                            </template>
                            <template x-if="message.role === 'user' &amp;&amp; !message.isError">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-brand-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </template>
                            <template x-if="message.role === 'bot' &amp;&amp; !message.isError">
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText(message.content)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100"
                                    title="Salin pesan"
                                    aria-label="Salin pesan"
                                >
                                    📋
                                </button>
                            </template>
                            <template x-if="message.isError &amp;&amp; message.isRetryable">
                                <button
                                    type="button"
                                    @click="retryLastMessage()"
                                    class="ml-auto inline-flex items-center gap-1 rounded-full border border-amber-300 bg-white px-2 py-0.5 text-[10px] font-semibold text-amber-700 transition-all hover:bg-amber-100 active:scale-95"
                                    title="Coba ulangi pesan"
                                >
                                    🔄 Coba lagi
                                </button>
                            </template>
                        </div>
                        <template x-if="message.actions && message.actions.length > 0">
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                <template x-for="(action, actionIndex) in message.actions" :key="`action-${index}-${actionIndex}`">
                                    <button
                                        type="button"
                                        x-show="action.type === 'open_tab' || action.type === 'suggested_reply'"
                                        @click="executeAction(action)"
                                        class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-[11px] font-semibold text-brand-800 transition-all hover:border-brand-300 hover:bg-brand-100 hover:shadow-sm active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1"
                                        :title="action.label || (action.target === 'grafik' ? 'Lihat Grafik' : 'Buka Ringkasan')"
                                        x-text="action.label || (action.target === 'grafik' ? '📊 Lihat Grafik' : '📄 Buka Ringkasan')"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="isTyping" class="flex items-start animate-fade-in">
                <span class="mr-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-md flex-shrink-0">
                    {!! $messageIcon !!}
                </span>
                <div class="flex items-center gap-1.5 rounded-2xl rounded-tl-none border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                    <span class="zakky-dot zakky-dot-1 h-2 w-2 rounded-full bg-brand-400 animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="zakky-dot zakky-dot-2 h-2 w-2 rounded-full bg-brand-400 animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="zakky-dot zakky-dot-3 h-2 w-2 rounded-full bg-brand-400 animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 bg-gradient-to-b from-white to-slate-50 p-3 sm:p-4">
            <form @submit.prevent="sendMessage" class="relative flex items-center gap-2">
                <input
                    type="text"
                    x-model="input"
                    maxlength="500"
                    class="flex-1 rounded-full border border-slate-200 bg-white py-3 pl-4 pr-4 text-sm text-slate-800 transition-all placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent disabled:opacity-60 disabled:cursor-not-allowed"
                    placeholder="Tanya Zakky..."
                    :disabled="isTyping"
                    autocomplete="off"
                >
                <button
                    type="submit"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-700 text-white transition-all hover:shadow-lg hover:to-brand-800 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-none flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                    title="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.488 5.951 1.488a1 1 0 001.169-1.409l-7-14z" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="messages = []; input = ''"
                    x-show="messages.length > 0"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all active:scale-95 flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
                    aria-label="Hapus riwayat chat"
                    title="Hapus riwayat chat"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 px-2 text-[10px] text-slate-400">
                <span class="flex-1">⚠️ AI dapat keliru. Verifikasi informasi penting.</span>
                <span class="font-sans tabular-nums flex-shrink-0" x-text="`${input.length}/500`" :class="input.length > 450 ? 'text-amber-600 font-semibold' : ''"></span>
            </div>
        </div>
    </div>
</div>
