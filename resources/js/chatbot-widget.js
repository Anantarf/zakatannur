const DEFAULT_ERROR_MESSAGE = 'Maaf, terjadi kesalahan. Silakan coba lagi.';
const NETWORK_ERROR_MESSAGE = 'Gangguan jaringan. Periksa koneksi internet Anda.';
const COPY_SUCCESS_MESSAGE = 'Tersalin!';
const FEEDBACK_SUCCESS_MESSAGE = 'Terima kasih atas feedback!';

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
        showTooltip: false,
        input: '',
        messages: [],
        isTyping: false,
        isOnline: true,
        welcomeAt: nowIso(),
        lastError: null,
        unreadBadge: 0,
        lastSeenMessageCount: 0,
        conversationContext: {},
        sessionId: null,
        messageCount: 0,
        activityInterval: null,

        get isInputEmpty() {
            return this.input.trim() === '';
        },

        autoResize() {
            if (!this.$refs.chatInput) return;
            this.$refs.chatInput.style.height = 'auto';
            const scrollHeight = this.$refs.chatInput.scrollHeight;
            this.$refs.chatInput.style.height = Math.min(scrollHeight, 120) + 'px';
            this.$refs.chatInput.style.overflowY = scrollHeight >= 120 ? 'auto' : 'hidden';
        },

        handleKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        },

        playPopSound() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                
                if (!window.zakkyAudioCtx) {
                    window.zakkyAudioCtx = new AudioContext();
                }
                const ctx = window.zakkyAudioCtx;
                if (ctx.state === 'suspended') ctx.resume();

                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(600, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + 0.1);

                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.1, ctx.currentTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.1);
            } catch (e) {}
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

        parseMarkdown(text) {
            if (!text) return '';
            
            // 1. Escape HTML first to prevent XSS
            let div = document.createElement('div');
            div.textContent = text;
            let html = div.innerHTML;

            // 2. Parse Bold (**text**)
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            
            // 3. Parse Italic (*text*)
            html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            
            // 4. Parse Links [text](url)
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-brand-600 underline hover:text-brand-800">$1</a>');
            
            // 5. Parse Lists (Unordered and Ordered)
            const lines = html.split('\n');
            let inList = false;
            let listType = null; // 'ul' or 'ol'
            let parsedLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                const ulMatch = line.match(/^(\s*)(?:-|\*|•)\s+(.+)$/);
                const olMatch = line.match(/^(\s*)\d+\.\s+(.+)$/);
                
                if (ulMatch || olMatch) {
                    const currentType = ulMatch ? 'ul' : 'ol';
                    const content = ulMatch ? ulMatch[2] : olMatch[2];
                    
                    if (!inList || listType !== currentType) {
                        if (inList) {
                            parsedLines.push(`</${listType}>`);
                        }
                        const listClass = currentType === 'ul' ? 'list-disc' : 'list-decimal';
                        parsedLines.push(`<${currentType} class="${listClass} pl-5 my-1 space-y-1">`);
                        inList = true;
                        listType = currentType;
                    }
                    parsedLines.push(`<li>${content}</li>`);
                } else {
                    if (inList) {
                        parsedLines.push(`</${listType}>`);
                        inList = false;
                        listType = null;
                    }
                    if (line.trim() !== '') {
                        parsedLines.push(`<p class="mb-2 last:mb-0">${line}</p>`);
                    }
                }
            }
            if (inList) {
                parsedLines.push(`</${listType}>`);
            }
            
            return parsedLines.join('');
        },

        formatMessage(content, role) {
            if (!content) return '';
            
            if (role === 'user') {
                // User messages are strictly escaped text
                let div = document.createElement('div');
                div.textContent = content;
                return div.innerHTML;
            }
            
            // Bot messages get Markdown parsing
            let html = this.parseMarkdown(content);
            
            // Wrap 'Zakky' with extra bold green span
            html = html.replace(/\b(Zakky)\b/gi, '<span class="font-extrabold text-brand-700">$1</span>');
            
            return html;
        },

        init() {
            this.checkInactivity();
            this.generateOrLoadSessionId();
            this.loadHistory();
            this.lastSeenMessageCount = this.messages.length;

            if (this.messages.length === 0) {
                this.resetToWelcome();
            }

            setTimeout(() => {
                if (!this.isOpen) {
                    this.showTooltip = true;
                }
            }, 3000);

            this.$watch('isOpen', (open) => {
                if (open) {
                    this.unreadBadge = 0;
                    this.lastSeenMessageCount = this.messages.length;
                    this.$nextTick(() => this.scrollToBottom(false));
                    this.$nextTick(() => {
                        const input = document.querySelector('[data-chatbot-widget] textarea');
                        if (input && window.matchMedia('(pointer: fine)').matches) input.focus();
                    });
                }
            });
            this.$watch('messages', (next) => {
                this.$nextTick(() => this.scrollToBottom(true));
                this.saveHistory();
                if (this.isOpen) {
                    this.lastSeenMessageCount = next.length;
                } else if (next.length > this.lastSeenMessageCount) {
                    const newBubbles = next.length - this.lastSeenMessageCount;
                    this.unreadBadge = Math.min(9, (this.unreadBadge || 0) + newBubbles);
                    this.lastSeenMessageCount = next.length;
                }
            });

            this.activityInterval = setInterval(() => {
                this.checkInactivity();
            }, 60000);
        },

        updateActivity() {
            localStorage.setItem('zakky_last_activity', Date.now().toString());
        },

        checkInactivity() {
            const lastActivity = localStorage.getItem('zakky_last_activity');
            if (lastActivity && Date.now() - parseInt(lastActivity, 10) > 10 * 60 * 1000) {
                this.clearHistory();
                this.resetToWelcome();
            }
        },

        resetToWelcome() {
            this.messages = [{
                role: 'bot',
                content: "Assalamu'alaikum. Saya Zakky. Saya bisa bantu cek data zakat atau jawab pertanyaan Anda.",
                createdAt: nowIso(),
                isWelcome: true,
            }];
            this.quickReplies = [
                { label: 'Hitung zakat fitrah', message: 'Zakat fitrah 4 orang berapa?' },
                { label: 'Cara bayar zakat', message: 'Bagaimana cara membayar zakat?' },
                { label: 'Apa itu zakat mal?', message: 'Apa itu zakat mal?' },
            ];
            this.messageCount = 0;
            localStorage.setItem('zakky_message_count_' + this.sessionId, '0');
            this.updateActivity();
        },

        generateOrLoadSessionId() {
            try {
                const storedId = localStorage.getItem('zakky_session_id');
                if (storedId) {
                    this.sessionId = storedId;
                } else {
                    this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    localStorage.setItem('zakky_session_id', this.sessionId);
                }
            } catch (e) {
                this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
        },

        loadHistory() {
            try {
                const key = 'zakky_history_' + (this.sessionId || 'default');
                const saved = localStorage.getItem(key);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        this.messages = parsed;
                    }
                }
                const count = localStorage.getItem('zakky_message_count_' + this.sessionId);
                if (count) {
                    this.messageCount = parseInt(count, 10);
                    if (this.messageCount >= 50) {
                        this.clearHistory();
                        this.resetToWelcome();
                        return;
                    }
                }
            } catch (e) {
                console.warn('Failed to load chat history:', e);
            }
        },

        saveHistory() {
            try {
                const key = 'zakky_history_' + (this.sessionId || 'default');
                const limited = this.messages.slice(-50).filter(m => !m.isWelcome);
                localStorage.setItem(key, JSON.stringify(limited));
            } catch (e) {
                console.warn('Failed to save chat history:', e);
            }
        },

        clearHistory() {
            try {
                const key = 'zakky_history_' + (this.sessionId || 'default');
                localStorage.removeItem(key);
                localStorage.removeItem('zakky_message_count_' + this.sessionId);
                localStorage.removeItem('zakky_session_id');
                this.sessionId = null;
                this.generateOrLoadSessionId();
                this.showTooltip = true;
            } catch (e) {
                console.warn('Failed to clear chat history:', e);
            }
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.dismissTooltip();
                this.$nextTick(() => {
                    const input = document.querySelector('[data-chatbot-widget] textarea');
                    if (input && window.matchMedia('(pointer: fine)').matches) input.focus();
                });
            }
        },

        closeChat() {
            this.isOpen = false;
        },

        dismissTooltip() {
            this.showTooltip = false;
        },

        scrollToBottom(smooth = true) {
            if (!this.$refs.chatContainer) {
                return;
            }
            this.$refs.chatContainer.scrollTo({
                top: this.$refs.chatContainer.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
        },

        scrollToMessage(index) {
            this.$nextTick(() => {
                const message = this.$refs.chatContainer?.querySelector(`[data-message][data-index="${index}"]`);
                if (message) {
                    message.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        async sendMessage() {
            if (this.isTyping || this.isInputEmpty) {
                return;
            }

            if (this.messageCount >= 50) {
                this.messages.push({
                    role: 'bot',
                    content: 'Anda telah mencapai batas 50 pesan untuk sesi ini. Silakan muat ulang halaman (Refresh) untuk memulai sesi percakapan baru dengan Zakky.',
                    isError: true,
                    isRetryable: false,
                    createdAt: nowIso(),
                });
                this.input = '';
                this.$nextTick(() => this.scrollToBottom());
                return;
            }

            this.updateActivity();
            this.messageCount++;
            localStorage.setItem('zakky_message_count_' + this.sessionId, this.messageCount.toString());

            const userMessage = this.input.trim();

            // Validate message length
            if (userMessage.length < 2) {
                return;
            }

            // Check for local actions first
            const localAction = this.resolveLocalAction(userMessage);
            this.messages.push({ role: 'user', content: userMessage, createdAt: nowIso() });
            this.input = '';
            this.$nextTick(() => {
                if (this.$refs.chatInput) {
                    this.$refs.chatInput.style.height = 'auto';
                    this.$refs.chatInput.style.overflowY = 'hidden';
                }
            });

            if (localAction) {
                this.openTab(localAction);
                this.$nextTick(() => this.scrollToBottom());
                return;
            }

            this.isTyping = true;
            this.lastError = null;
            this.$nextTick(() => this.scrollToBottom());

            // Try streaming first, fallback to regular message
            const streamEndpoint = this.endpoint.replace('/message', '/stream');
            const useStreaming = await this.tryStreaming(userMessage, streamEndpoint);

            if (useStreaming) {
                this.isTyping = false;
                this.$nextTick(() => this.scrollToBottom());
                return;
            }

            // Fallback to regular message
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
                        session_id: this.sessionId,
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
                    this.playPopSound();
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
            if (action?.type === 'open_url' && action?.url) {
                window.open(action.url, '_blank', 'noopener,noreferrer');
                return;
            }

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

        async tryStreaming(userMessage, streamEndpoint) {
            try {
                const response = await fetch(streamEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({
                        message: userMessage,
                        context: this.conversationContext,
                        session_id: this.sessionId,
                    }),
                });

                if (!response.ok) {
                    return false;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let botMessage = {
                    role: 'bot',
                    content: '',
                    citations: [],
                    createdAt: nowIso(),
                };

                let msgIndex = -1;
                let firstChunkPlayed = false;
                let scrolledToMessage = false;

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');

                    for (let i = 0; i < lines.length - 1; i++) {
                        const line = lines[i].trim();
                        if (line.startsWith('data: ')) {
                            const dataString = line.slice(6).trim();
                            
                            // Push the bot message on the first valid event
                            if (!firstChunkPlayed && (dataString === '[DONE]' || dataString !== '')) {
                                this.messages.push(botMessage);
                                msgIndex = this.messages.length - 1;
                                this.playPopSound();
                                firstChunkPlayed = true;
                                this.$nextTick(() => this.scrollToBottom(true));
                            }

                            if (dataString === '[DONE]') {
                                this.messages[msgIndex].content = this.messages[msgIndex].content
                                    .replace(/\[SUGGEST:\s*.*?\]/gi, '').trim();
                                continue;
                            }

                            const data = JSON.parse(dataString);
                            if (data.chunk) {
                                this.messages[msgIndex].content += data.chunk;
                                // Scroll to bottom continuously as new text comes in
                                if (!scrolledToMessage) {
                                    scrolledToMessage = true;
                                }
                                this.$nextTick(() => {
                                    this.scrollToBottom(false);
                                });
                            } else if (data.actions) {
                                this.messages[msgIndex].actions = data.actions;
                            } else if (data.error) {
                                this.messages[msgIndex].isError = true;
                                this.messages[msgIndex].isRetryable = data.retryable;
                                this.messages[msgIndex].content = data.error;
                                break;
                            }
                        }
                    }

                    buffer = lines[lines.length - 1];
                }

                this.isOnline = true;
                return true;
            } catch (error) {
                console.warn('Streaming failed, will fallback to regular message', error);
                // Remove the incomplete bot message on error
                if (this.messages[this.messages.length - 1]?.role === 'bot' && !this.messages[this.messages.length - 1]?.content) {
                    this.messages.pop();
                }
                return false;
            }
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

        copyMessage(content) {
            navigator.clipboard.writeText(content).then(() => {
                this.showToast(COPY_SUCCESS_MESSAGE, 'success');
            });
        },

        sendFeedback(messageIndex, rating) {
            const message = this.messages[messageIndex];
            if (!message || message.role !== 'bot') return;

            message.feedback = rating;
            this.showToast(FEEDBACK_SUCCESS_MESSAGE, 'success');

            // Log feedback (could be sent to backend later)
            fetch(this.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message.content,
                    feedback: rating,
                    type: 'feedback',
                    session_id: this.sessionId,
                }),
            }).catch(() => {});
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-6 right-6 px-4 py-2 rounded text-sm font-medium text-white ${
                type === 'success' ? 'bg-green-600' : 'bg-slate-600'
            } shadow-lg animate-fade-in`;
            toast.textContent = message;
            toast.style.zIndex = '9999';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        },
    }));
});
