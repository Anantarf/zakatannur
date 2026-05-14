<div x-data="chatbot()" class="fixed bottom-6 right-6 z-50 font-sans">
    
    <!-- Chat Button -->
    <button 
        @click="toggleChat()" 
        class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full p-4 shadow-lg transition-transform transform hover:scale-105 focus:outline-none flex items-center justify-center"
        :class="isOpen ? 'scale-0 opacity-0' : 'scale-100 opacity-100'"
        style="transition: all 0.3s ease-in-out;"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>

    <!-- Chat Window -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="bg-white rounded-2xl shadow-2xl w-80 sm:w-96 flex flex-col overflow-hidden border border-gray-100 origin-bottom-right"
        style="display: none; height: 500px; max-height: 80vh;"
    >
        <!-- Header -->
        <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shadow-md z-10">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-emerald-600 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight">AI Asisten Zakat</h3>
                    <p class="text-xs text-emerald-100 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-300 mr-1 animate-pulse"></span>
                        Online
                    </p>
                </div>
            </div>
            <button @click="toggleChat()" class="text-emerald-100 hover:text-white hover:bg-emerald-700 rounded-full p-1 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div x-ref="chatContainer" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col space-y-4">
            <!-- Welcome Message -->
            <div class="flex items-start">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-2 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="bg-white border border-gray-200 text-gray-800 p-3 rounded-2xl rounded-tl-none shadow-sm text-sm max-w-[85%]">
                    Assalamu'alaikum! Saya adalah asisten virtual Zakat An-Nur. Ada yang bisa saya bantu terkait perhitungan zakat atau informasi lainnya?
                </div>
            </div>

            <!-- Loop Messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex w-full" :class="msg.role === 'user' ? 'justify-end' : 'justify-start items-start'">
                    
                    <!-- AI Avatar -->
                    <template x-if="msg.role === 'bot'">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-2 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </template>

                    <!-- Message Bubble -->
                    <div 
                        class="p-3 shadow-sm text-sm max-w-[85%]" 
                        :class="msg.role === 'user' ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-tl-none'"
                        x-text="msg.content"
                    ></div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <div x-show="isTyping" class="flex items-start">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-2 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="bg-white border border-gray-200 text-gray-500 p-3 rounded-2xl rounded-tl-none shadow-sm flex items-center space-x-1">
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white border-t border-gray-100">
            <form @submit.prevent="sendMessage" class="relative flex items-center">
                <input 
                    type="text" 
                    x-model="input" 
                    maxlength="500"
                    class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-colors"
                    placeholder="Tulis pesan..."
                    :disabled="isTyping"
                >
                <button 
                    type="submit" 
                    class="absolute right-1 w-10 h-10 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isTyping || input.trim() === ''"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="flex justify-between items-center mt-2 px-2">
                <span class="text-[10px] text-gray-400">Didukung oleh AI Chatbot</span>
                <span class="text-[10px] text-gray-400 font-mono" x-text="input.length + '/500'"></span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatbot', () => ({
            isOpen: false,
            input: '',
            messages: [],
            isTyping: false,

            toggleChat() {
                this.isOpen = !this.isOpen;
                if(this.isOpen) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            },

            scrollToBottom() {
                if (this.$refs.chatContainer) {
                    this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
                }
            },

            async sendMessage() {
                if (this.input.trim() === '') return;

                const userMsg = this.input.trim();
                this.messages.push({ role: 'user', content: userMsg });
                this.input = '';
                this.isTyping = true;
                
                this.$nextTick(() => this.scrollToBottom());

                try {
                    const response = await fetch('/api/chatbot/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message: userMsg })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') {
                        this.messages.push({ role: 'bot', content: data.data.reply });
                    } else {
                        this.messages.push({ role: 'bot', content: "Maaf, terjadi kesalahan saat menghubungi server." });
                    }
                } catch (error) {
                    this.messages.push({ role: 'bot', content: "Maaf, terjadi gangguan jaringan." });
                } finally {
                    this.isTyping = false;
                    this.$nextTick(() => this.scrollToBottom());
                }
            }
        }));
    });
</script>
