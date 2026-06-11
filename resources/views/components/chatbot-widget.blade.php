@php
    $quickReplies = [
        ['label' => 'Apa itu zakat fitrah?', 'message' => 'Apa itu zakat fitrah?'],
        ['label' => 'Cara bayar zakat', 'message' => 'Bagaimana cara membayar zakat?'],
        ['label' => 'Nishab zakat mal', 'message' => 'Berapa nishab zakat mal?'],
        ['label' => 'Batas waktu zakat', 'message' => 'Kapan batas waktu membayar zakat?'],
    ];
@endphp

<div
    x-data="chatbotWidget({ endpoint: '{{ url('/api/chatbot/message') }}', quickReplies: {{ json_encode($quickReplies) }} })"
    x-cloak
    class="fixed bottom-4 right-4 z-50 sm:bottom-6 sm:right-6"
>
    <button
        type="button"
        @click="toggleChat()"
        class="zakky-fab group relative flex h-14 w-14 items-center justify-center rounded-full bg-white p-3 text-emerald-700 ring-1 ring-emerald-200/80 shadow-[0_18px_40px_-12px_rgba(4,120,87,0.45)] transition-transform duration-300 ease-out hover:scale-105 hover:shadow-[0_22px_48px_-12px_rgba(4,120,87,0.55)] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chatbot Zakky"
    >
        <span class="zakky-fab-pulse absolute inset-0 rounded-full bg-emerald-400/30" aria-hidden="true"></span>
        <x-zakat-avatar size="md" variant="light" class="relative z-10" />
        <span
            x-show="unreadBadge"
            x-cloak
            class="absolute -top-1 -right-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-sm"
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
        style="display: none; height: min(540px, 80vh); max-height: 80vh;"
        role="dialog"
        aria-label="Chat dengan Zakky"
    >
        <div class="z-10 flex items-center justify-between bg-gradient-to-r from-emerald-600 via-emerald-600 to-emerald-700 p-4 text-white shadow-md">
            <div class="flex items-center space-x-3 min-w-0">
                <x-zakat-avatar size="md" variant="light" class="shadow-inner" />
                <div class="min-w-0">
                    <h3 class="text-base font-bold leading-tight">Zakky</h3>
                    <p class="flex items-center text-[11px] text-emerald-50">
                        <span
                            class="mr-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-300"
                            :class="isOnline ? 'animate-pulse' : 'opacity-50'"
                        ></span>
                        <span x-text="isOnline ? 'Online · siap membantu' : 'AI Asisten Zakat An-Nur'"></span>
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="closeChat()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/90 transition-colors hover:bg-white/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                aria-label="Tutup chatbot"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-ref="chatContainer" class="flex flex-1 flex-col space-y-4 overflow-y-auto bg-slate-50/70 p-4">
            <div class="flex items-start">
                <x-zakat-avatar size="sm" variant="solid" class="mr-2" />
                <div class="flex max-w-[85%] flex-col items-start">
                    <div class="rounded-2xl rounded-tl-none border border-slate-200/80 bg-white p-3 text-sm leading-relaxed text-slate-800 shadow-sm break-words whitespace-pre-wrap">
                        Assalamu'alaikum! Saya <span class="font-semibold text-emerald-700">Zakky</span>, asisten virtual Zakat An-Nur. Ada yang bisa saya bantu terkait zakat, cara pembayaran, nishab, atau operasional masjid?
                    </div>
                    <span class="mt-1 px-1 text-[10px] text-slate-400" x-text="formatTime(welcomeAt)"></span>
                </div>
            </div>

            <div x-show="quickReplies.length > 0 &amp;&amp; messages.length === 0" class="flex flex-wrap gap-1.5 pl-10">
                <template x-for="(chip, i) in quickReplies" :key="`chip-${i}`">
                    <button
                        type="button"
                        @click="useQuickReply(chip.message)"
                        class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-emerald-700 shadow-sm transition-colors hover:bg-emerald-50 hover:border-emerald-300"
                        x-text="chip.label"
                    ></button>
                </template>
            </div>

            <template x-for="(message, index) in messages" :key="`${message.role}-${index}`">
                <div class="flex w-full" :class="message.role === 'user' ? 'justify-end' : 'justify-start items-start'">
                    <template x-if="message.role === 'bot'">
                        <x-zakat-avatar size="sm" variant="solid" class="mr-2" />
                    </template>

                    <div class="flex max-w-[85%] flex-col" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                        <div
                            class="p-3 text-sm leading-relaxed shadow-sm break-words whitespace-pre-wrap"
                            :class="message.role === 'user'
                                ? 'rounded-2xl rounded-tr-none bg-emerald-600 text-white'
                                : (message.isError
                                    ? 'rounded-2xl rounded-tl-none border border-amber-200 bg-amber-50 text-amber-900'
                                    : 'rounded-2xl rounded-tl-none border border-slate-200/80 bg-white text-slate-800')"
                            x-text="message.content"
                        ></div>
                        <div class="mt-1 flex items-center gap-1.5 px-1 text-[10px] text-slate-400">
                            <span x-text="formatTime(message.createdAt)"></span>
                            <template x-if="message.role === 'user' &amp;&amp; !message.isError">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
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
                    </div>
                </div>
            </template>

            <div x-show="isTyping" class="flex items-start">
                <x-zakat-avatar size="sm" variant="solid" class="mr-2" />
                <div class="flex items-center gap-1 rounded-2xl rounded-tl-none border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
                    <span class="zakky-dot h-1.5 w-1.5 rounded-full bg-slate-400" style="animation-delay: 0ms"></span>
                    <span class="zakky-dot h-1.5 w-1.5 rounded-full bg-slate-400" style="animation-delay: 180ms"></span>
                    <span class="zakky-dot h-1.5 w-1.5 rounded-full bg-slate-400" style="animation-delay: 360ms"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 bg-white p-3">
            <form @submit.prevent="sendMessage" class="relative flex items-center">
                <input
                    type="text"
                    x-model="input"
                    maxlength="500"
                    class="w-full rounded-full border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-sm text-slate-800 transition-colors placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-60"
                    placeholder="Tulis pesan untuk Zakky..."
                    :disabled="isTyping"
                >
                <button
                    type="submit"
                    class="absolute right-1 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white transition-all hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 px-2">
                <span class="text-[10px] text-slate-400">Powered by Zakky</span>
                <span class="hidden sm:inline text-[10px] italic text-slate-400">Jawaban AI dapat mengandung kesalahan, verifikasi untuk keputusan penting</span>
                <span class="font-sans tabular-nums text-[10px] text-slate-400" x-text="`${input.length}/500`"></span>
            </div>
        </div>
    </div>
</div>
