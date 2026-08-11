const DEFAULT_ERROR_MESSAGE = 'Maaf, terjadi kesalahan. Silakan coba lagi.';
const NETWORK_ERROR_MESSAGE = 'Gangguan jaringan. Periksa koneksi internet Anda.';
const COPY_SUCCESS_MESSAGE = 'Tersalin!';
const FEEDBACK_SUCCESS_MESSAGE = 'Terima kasih atas feedback!';
// Backend's own HTTP timeout to OpenAI tops out around ~25-33s (see OpenAiChatbotProvider's
// timeout+retry config) - this must stay above that or we'd abort requests the backend was
// still going to finish successfully. Without this, a stalled connection (proxy/hosting
// swallowing the SSE stream mid-response) left `reader.read()` awaiting forever with no way
// to resolve, keeping isTyping stuck true and the send button permanently disabled.
const REQUEST_TIMEOUT_MS = 40000;

const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
});

const nowIso = () => new Date().toISOString();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chatbotWidget', ({ endpoint, embedded = false }) => ({
        endpoint,
        embedded,
        isOpen: embedded,
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
        width: 400,
        height: 600,
        isResizing: false,
        resizeDir: '',
        startX: 0,
        startY: 0,
        startWidth: 0,
        startHeight: 0,

        get resizeStyle() {
            if (this.embedded) return '';
            if (!window.matchMedia('(min-width: 640px)').matches) return '';
            return `width: ${this.width}px; height: ${this.height}px; max-height: 85vh;`;
        },

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
            
            // 4. Parse Links [text](url) - only http(s) allowed as the href scheme. Without this
            // check a bot reply containing [text](javascript:...) would render as a real clickable
            // link that runs attacker JS on click when the user clicks it (text was HTML-escaped in
            // step 1, so quote/tag injection is blocked, but the URL scheme itself was not).
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, label, url) => {
                return /^https?:\/\//i.test(url)
                    ? `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-brand-600 underline hover:text-brand-800">${label}</a>`
                    : label;
            });
            
            // 5. Parse Lists (Unordered and Ordered)
            const lines = html.split('\n');
            let inList = false;
            let listType = null; // 'ul' or 'ol'
            let parsedLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];

                // [[HASIL]]...[[/HASIL]] (from a computed zakat mal figure, see
                // ChatbotSentinelParser) wraps its lines in a distinct result card
                // instead of the plain paragraph styling below.
                let closeResultCardAfter = false;
                if (line.includes('[[HASIL]]')) {
                    line = line.replace('[[HASIL]]', '');
                    parsedLines.push('<div class="zakat-result-card">');
                }
                if (line.includes('[[/HASIL]]')) {
                    line = line.replace('[[/HASIL]]', '');
                    closeResultCardAfter = true;
                }

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

                if (closeResultCardAfter) {
                    if (inList) {
                        parsedLines.push(`</${listType}>`);
                        inList = false;
                        listType = null;
                    }
                    parsedLines.push('</div>');
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
            if (!this.embedded && window.matchMedia('(min-width: 640px)').matches) {
                const savedWidth = localStorage.getItem('zakky_chat_width');
                const savedHeight = localStorage.getItem('zakky_chat_height');
                if (savedWidth) this.width = parseInt(savedWidth, 10);
                if (savedHeight) this.height = parseInt(savedHeight, 10);
            }
            this.checkInactivity();
            this.generateOrLoadSessionId();
            this.loadHistory();
            this.lastSeenMessageCount = this.messages.length;

            if (this.messages.length === 0) {
                this.resetToWelcome();
            }

            if (!this.embedded) {
                setTimeout(() => {
                    if (!this.isOpen) {
                        this.showTooltip = true;
                    }
                }, 3000);
            }

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
                content: "Assalamu'alaikum. Saya Zakky. Ceritakan kasus zakat Anda, nanti saya bantu arahkan langkahnya.",
                createdAt: nowIso(),
                isWelcome: true,
            }];
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

            this.messages.push({ role: 'user', content: userMessage, createdAt: nowIso() });
            this.input = '';
            this.$nextTick(() => {
                if (this.$refs.chatInput) {
                    this.$refs.chatInput.style.height = 'auto';
                    this.$refs.chatInput.style.overflowY = 'hidden';
                }
            });

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
            const controller = new AbortController();
            const watchdog = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
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
                    signal: controller.signal,
                });

                const payload = await this.parseResponse(response);

                if (response.ok && payload?.status === 'success' && payload?.data?.reply) {
                    const data = payload.data;
                    this.messages.push({
                        role: 'bot',
                        content: data.reply,
                        source: data.source,
                        citations: data.citations || [],
                        createdAt: nowIso(),
                    });
                    this.conversationContext = this.sanitizeContext(data.context || {});
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
                clearTimeout(watchdog);
                this.isTyping = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        sanitizeContext(context) {
            const allowedKeys = ['last_intent', 'last_source', 'topic', 'mode'];
            return allowedKeys.reduce((next, key) => {
                if (typeof context[key] === 'string' && context[key].length <= 80) {
                    next[key] = context[key];
                }
                return next;
            }, {});
        },

        async tryStreaming(userMessage, streamEndpoint) {
            // Idle timeout, not a total-duration cap: reset on every chunk received, so a long
            // but actively-streaming answer (a detailed multi-paragraph reply) isn't aborted
            // mid-response just for taking longer than REQUEST_TIMEOUT_MS in total - only a genuine
            // stall (no bytes for REQUEST_TIMEOUT_MS) trips it.
            const controller = new AbortController();
            let watchdog = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
            const resetWatchdog = () => {
                clearTimeout(watchdog);
                watchdog = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
            };

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
                    signal: controller.signal,
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
                let streamErrorWasRetryable = false;

                streamLoop: while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    resetWatchdog();

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
                                this.$nextTick(() => {
                                    this.scrollToBottom(false);
                                });
                            } else if (data.context) {
                                // Lets the backend know next turn whether we're mid AI-conversation
                                // (see ChatbotOrchestrator::getQuickResponse) instead of the fast-path
                                // keyword matcher hijacking a reply like "kambingnya 40 ekor".
                                this.conversationContext = this.sanitizeContext(data.context);
                            } else if (data.error) {
                                this.messages[msgIndex].isError = true;
                                this.messages[msgIndex].isRetryable = data.retryable;
                                this.messages[msgIndex].content = data.error;
                                // An in-band error already ended the bot's turn - stop reading
                                // instead of waiting on a stream the backend may not close, and
                                // report success so sendMessage doesn't also fire a duplicate
                                // non-streaming fallback on top of the error already shown.
                                streamErrorWasRetryable = data.retryable === true;
                                break streamLoop;
                            }
                        }
                    }

                    buffer = lines[lines.length - 1];
                }

                this.isOnline = !streamErrorWasRetryable;
                return true;
            } catch (error) {
                console.warn('Streaming failed, will fallback to regular message', error);
                // Remove the incomplete bot message on error
                if (this.messages[this.messages.length - 1]?.role === 'bot' && !this.messages[this.messages.length - 1]?.content) {
                    this.messages.pop();
                }
                return false;
            } finally {
                clearTimeout(watchdog);
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

        startResize(e, direction) {
            if (this.embedded || !window.matchMedia('(min-width: 640px)').matches) return;
            
            this.isResizing = true;
            this.resizeDir = direction;
            this.startX = e.clientX;
            this.startY = e.clientY;
            this.startWidth = this.width;
            this.startHeight = this.height;

            this._mouseMoveHandler = (event) => this.doResize(event);
            this._mouseUpHandler = () => this.stopResize();

            document.addEventListener('mousemove', this._mouseMoveHandler);
            document.addEventListener('mouseup', this._mouseUpHandler);
            
            document.body.style.userSelect = 'none';
        },

        doResize(e) {
            if (!this.isResizing) return;

            const deltaX = e.clientX - this.startX;
            const deltaY = e.clientY - this.startY;

            if (this.resizeDir === 'left' || this.resizeDir === 'top-left') {
                const newWidth = this.startWidth - deltaX;
                const maxScreenWidth = window.innerWidth * 0.9;
                this.width = Math.min(Math.max(newWidth, 380), Math.min(800, maxScreenWidth));
            }

            if (this.resizeDir === 'top' || this.resizeDir === 'top-left') {
                const newHeight = this.startHeight - deltaY;
                const maxScreenHeight = window.innerHeight * 0.85;
                this.height = Math.min(Math.max(newHeight, 450), Math.min(800, maxScreenHeight));
            }
            
            this.$nextTick(() => this.scrollToBottom(false));
        },

        stopResize() {
            if (!this.isResizing) return;
            this.isResizing = false;

            document.removeEventListener('mousemove', this._mouseMoveHandler);
            document.removeEventListener('mouseup', this._mouseUpHandler);
            
            document.body.style.userSelect = '';

            localStorage.setItem('zakky_chat_width', this.width.toString());
            localStorage.setItem('zakky_chat_height', this.height.toString());
        },

        resetResize() {
            if (this.embedded || !window.matchMedia('(min-width: 640px)').matches) return;
            
            this.width = 400;
            this.height = 600;
            localStorage.removeItem('zakky_chat_width');
            localStorage.removeItem('zakky_chat_height');
            this.$nextTick(() => this.scrollToBottom(false));
        },
    }));
});
