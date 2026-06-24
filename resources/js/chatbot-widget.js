const DEFAULT_ERROR_MESSAGE = 'Maaf, Zakky sedang bermasalah. 😢 Coba lagi dalam beberapa saat.';
const NETWORK_ERROR_MESSAGE = 'Gangguan jaringan terdeteksi. Periksa koneksi internet Anda.';

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
        conversationContext: {},

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
                    // Set focus to input for accessibility
                    this.$nextTick(() => {
                        const input = document.querySelector('[data-chatbot-widget] input[type="text"]');
                        if (input) input.focus();
                    });
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
            if (this.isOpen) {
                this.$nextTick(() => {
                    const input = document.querySelector('[data-chatbot-widget] input[type="text"]');
                    if (input) input.focus();
                });
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

            // Validate message length
            if (userMessage.length < 2) {
                return;
            }

            // Check for local actions first
            const localAction = this.resolveLocalAction(userMessage);
            this.messages.push({ role: 'user', content: userMessage, createdAt: nowIso() });
            this.input = '';

            if (localAction) {
                this.openTab(localAction);
                this.$nextTick(() => this.scrollToBottom());
                return;
            }

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
                    body: JSON.stringify({
                        message: userMessage,
                        context: this.conversationContext,
                    }),
                });

                const payload = await this.parseResponse(response);

                if (response.ok && payload?.status === 'success' && payload?.data?.reply) {
                    const data = payload.data;
                    this.messages.push({
                        role: 'bot',
                        content: data.reply,
                        source: data.source,
                        actions: data.actions || [],
                        citations: data.citations || [],
                        createdAt: nowIso(),
                    });
                    this.conversationContext = this.sanitizeContext(data.context || {});
                    this.executeActions(data.actions || []);
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

        useQuickReply(chip) {
            if (this.isTyping) {
                return;
            }

            if (chip?.action === 'tab' && chip?.target) {
                this.openTab(chip.target);
                return;
            }

            const text = typeof chip === 'string' ? chip : chip?.message;
            if (!text) {
                return;
            }

            this.input = text;
            this.sendMessage();
        },

        openTab(tab) {
            if (!['beranda', 'laporan', 'grafik'].includes(tab)) {
                return;
            }

            window.dispatchEvent(new CustomEvent('public-home:set-tab', {
                detail: { tab },
            }));
            this.closeChat();
        },

        executeActions(actions) {
            actions.forEach((action) => {
                if (action?.auto === true) {
                    this.executeAction(action);
                }
            });
        },

        executeAction(action) {
            if (action?.type === 'open_tab' && action?.target) {
                this.openTab(action.target);
                return;
            }

            if (action?.type === 'suggested_reply' && action?.message) {
                this.input = action.message;
                this.sendMessage();
            }
        },

        sanitizeContext(context) {
            const allowedKeys = ['last_intent', 'last_source', 'topic'];
            return allowedKeys.reduce((next, key) => {
                if (typeof context[key] === 'string' && context[key].length <= 80) {
                    next[key] = context[key];
                }
                return next;
            }, {});
        },

        resolveLocalAction(message) {
            const normalized = message.toLowerCase();
            if (normalized.includes('buka ringkasan') || normalized.includes('lihat ringkasan') || normalized.includes('buka laporan')) {
                return 'laporan';
            }
            if (normalized.includes('buka grafik') || normalized.includes('lihat grafik') || normalized.includes('buka chart')) {
                return 'grafik';
            }
            return null;
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
