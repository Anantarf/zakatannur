const DEFAULT_ERROR_MESSAGE = 'Maaf, terjadi kesalahan saat menghubungi server.';
const NETWORK_ERROR_MESSAGE = 'Maaf, terjadi gangguan jaringan.';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chatbotWidget', ({ endpoint }) => ({
        endpoint,
        isOpen: false,
        input: '',
        messages: [],
        isTyping: false,

        get isInputEmpty() {
            return this.input.trim() === '';
        },

        toggleChat() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        closeChat() {
            this.isOpen = false;
        },

        scrollToBottom() {
            if (!this.$refs.chatContainer) {
                return;
            }

            this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
        },

        async sendMessage() {
            if (this.isTyping || this.isInputEmpty) {
                return;
            }

            const userMessage = this.input.trim();
            this.messages.push({ role: 'user', content: userMessage });
            this.input = '';
            this.isTyping = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: userMessage }),
                });

                const payload = await this.parseResponse(response);

                if (response.ok && payload?.status === 'success' && payload?.data?.reply) {
                    this.messages.push({ role: 'bot', content: payload.data.reply });
                    return;
                }

                this.messages.push({
                    role: 'bot',
                    content: payload?.message || DEFAULT_ERROR_MESSAGE,
                });
            } catch (error) {
                this.messages.push({ role: 'bot', content: NETWORK_ERROR_MESSAGE });
            } finally {
                this.isTyping = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        async parseResponse(response) {
            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                return null;
            }

            return response.json();
        },
    }));
});
