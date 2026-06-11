const DEFAULT_ERROR_MESSAGE = 'Maaf, terjadi kesalahan saat menghubungi server.';
const NETWORK_ERROR_MESSAGE = 'Maaf, terjadi gangguan jaringan.';

const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
});

const nowIso = () => new Date().toISOString();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chatbotWidget', ({ endpoint, quickReplies = [] }) => ({
        endpoint,
        quickReplies,
        isOpen: false,
        input: '',
        messages: [],
        isTyping: false,
        isOnline: true,
        welcomeAt: nowIso(),
        lastError: null,
        unreadBadge: 0,
        lastSeenMessageCount: 0,

        get isInputEmpty() {
            return this.input.trim() === '';
        },

        formatTime(iso) {
            if (!iso) {
                return '';
            }
            const date = new Date(iso);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            try {
                return timeFormatter.format(date);
            } catch (_) {
                return '';
            }
        },

        init() {
            this.lastSeenMessageCount = this.messages.length;
            this.$watch('isOpen', (open) => {
                if (open) {
                    this.unreadBadge = 0;
                    this.lastSeenMessageCount = this.messages.length;
                    this.$nextTick(() => this.scrollToBottom());
                }
            });
            this.$watch('messages', (next) => {
                if (this.isOpen) {
                    this.lastSeenMessageCount = next.length;
                } else if (next.length > this.lastSeenMessageCount) {
                    const newBubbles = next.length - this.lastSeenMessageCount;
                    this.unreadBadge = Math.min(9, (this.unreadBadge || 0) + newBubbles);
                    this.lastSeenMessageCount = next.length;
                }
            });
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
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
            this.messages.push({ role: 'user', content: userMessage, createdAt: nowIso() });
            this.input = '';
            this.isTyping = true;
            this.lastError = null;
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
                    this.messages.push({ role: 'bot', content: payload.data.reply, createdAt: nowIso() });
                    this.isOnline = true;
                    return;
                }

                const message = payload?.message || DEFAULT_ERROR_MESSAGE;
                const isRetryable = payload?.retryable === true;
                this.messages.push({
                    role: 'bot',
                    content: message,
                    isError: true,
                    isRetryable,
                    createdAt: nowIso(),
                });
                this.lastError = isRetryable ? message : null;
                this.isOnline = isRetryable ? false : this.isOnline;
            } catch (error) {
                this.messages.push({
                    role: 'bot',
                    content: NETWORK_ERROR_MESSAGE,
                    isError: true,
                    isRetryable: true,
                    createdAt: nowIso(),
                });
                this.lastError = NETWORK_ERROR_MESSAGE;
                this.isOnline = false;
            } finally {
                this.isTyping = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        useQuickReply(text) {
            if (this.isTyping) {
                return;
            }
            this.input = text;
            this.sendMessage();
        },

        retryLastMessage() {
            if (this.isTyping) {
                return;
            }

            for (let i = this.messages.length - 1; i >= 0; i--) {
                if (this.messages[i].role === 'user') {
                    const lastUser = this.messages[i].content;
                    this.messages.splice(i);
                    this.input = lastUser;
                    return this.sendMessage();
                }
            }
        },

        async parseResponse(response) {
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                return null;
            }
            try {
                return await response.json();
            } catch (_) {
                return null;
            }
        },
    }));
});
