<div
    x-data="chatbotWidget({ endpoint: '{{ url('/api/chatbot/message') }}' })"
    x-cloak
    class="fixed bottom-4 right-4 z-50 font-sans sm:bottom-6 sm:right-6"
>
    <button
        type="button"
        @click="toggleChat()"
        class="flex items-center justify-center rounded-full bg-white p-4 text-emerald-700 ring-1 ring-neutral-200 shadow-[0_18px_40px_-12px_rgba(15,23,42,0.45)] transition-all duration-300 hover:scale-105 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        :class="isOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
        aria-label="Buka chatbot"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-10 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-10 scale-95 opacity-0"
        @keydown.escape.window="closeChat()"
        class="flex w-[calc(100vw-1.5rem)] max-w-[24rem] origin-bottom-right flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl sm:w-96"
        style="display: none; height: min(500px, 78vh); max-height: 78vh;"
    >
        <div class="z-10 flex items-center justify-between bg-emerald-600 p-4 text-white shadow-md">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-emerald-600 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight">AI Asisten Zakat</h3>
                    <p class="flex items-center text-xs text-emerald-100">
                        <span class="mr-1 h-2 w-2 animate-pulse rounded-full bg-green-300"></span>
                        Online
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="closeChat()"
                class="rounded-full p-1 text-emerald-100 transition-colors hover:bg-emerald-700 hover:text-white"
                aria-label="Tutup chatbot"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-ref="chatContainer" class="flex flex-1 flex-col space-y-4 overflow-y-auto bg-gray-50 p-4">
            <div class="flex items-start">
                <div class="mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="max-w-[85%] rounded-2xl rounded-tl-none border border-gray-200 bg-white p-3 text-sm text-gray-800 shadow-sm">
                    Assalamu'alaikum! Saya adalah asisten virtual Zakat An-Nur. Ada yang bisa saya bantu terkait perhitungan zakat atau informasi lainnya?
                </div>
            </div>

            <template x-for="(message, index) in messages" :key="`${message.role}-${index}`">
                <div class="flex w-full" :class="message.role === 'user' ? 'justify-end' : 'justify-start items-start'">
                    <template x-if="message.role === 'bot'">
                        <div class="mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </template>

                    <div
                        class="max-w-[85%] p-3 text-sm shadow-sm"
                        :class="message.role === 'user'
                            ? 'rounded-2xl rounded-tr-none bg-emerald-600 text-white'
                            : 'rounded-2xl rounded-tl-none border border-gray-200 bg-white text-gray-800'"
                        x-text="message.content"
                    ></div>
                </div>
            </template>

            <div x-show="isTyping" class="flex items-start">
                <div class="mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="flex items-center space-x-1 rounded-2xl rounded-tl-none border border-gray-200 bg-white p-3 text-gray-500 shadow-sm">
                    <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0ms"></span>
                    <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 150ms"></span>
                    <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 bg-white p-3">
            <form @submit.prevent="sendMessage" class="relative flex items-center">
                <input
                    type="text"
                    x-model="input"
                    maxlength="500"
                    class="w-full rounded-full border border-gray-200 bg-gray-50 py-3 pl-4 pr-12 text-sm transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="Tulis pesan..."
                    :disabled="isTyping"
                >
                <button
                    type="submit"
                    class="absolute right-1 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isTyping || isInputEmpty"
                    aria-label="Kirim pesan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between px-2">
                <span class="text-[10px] text-gray-400">Didukung oleh AI Chatbot</span>
                <span class="font-sans tabular-nums text-[10px] text-gray-400" x-text="`${input.length}/500`"></span>
            </div>
        </div>
    </div>
</div>
