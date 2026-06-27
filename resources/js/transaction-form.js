// Fuzzy matching with typo tolerance
function levenshteinDistance(a, b) {
    const aa = a.toLowerCase();
    const bb = b.toLowerCase();
    const matrix = [];

    for (let i = 0; i <= bb.length; i++) {
        matrix[i] = [i];
    }
    for (let j = 0; j <= aa.length; j++) {
        matrix[0][j] = j;
    }

    for (let i = 1; i <= bb.length; i++) {
        for (let j = 1; j <= aa.length; j++) {
            const cost = aa[j - 1] === bb[i - 1] ? 0 : 1;
            matrix[i][j] = Math.min(
                matrix[i][j - 1] + 1,
                matrix[i - 1][j] + 1,
                matrix[i - 1][j - 1] + cost
            );
        }
    }
    return matrix[bb.length][aa.length];
}

function fuzzyMatch(query, candidates) {
    if (!query || query.length === 0) return [];

    const queryLower = query.toLowerCase();
    const matches = [];

    for (const candidate of candidates) {
        const candidateLower = candidate.toLowerCase();

        // Exact substring match (prioritize)
        if (candidateLower.includes(queryLower)) {
            const position = candidateLower.indexOf(queryLower);
            matches.push({ value: candidate, score: 1000 - position });
            continue;
        }

        // Typo tolerance for queries >= 3 chars
        if (query.length >= 3) {
            const distance = levenshteinDistance(query, candidate);
            if (distance <= 2) {
                matches.push({ value: candidate, score: 100 - distance });
            }
        }
    }

    matches.sort((a, b) => {
        if (a.score !== b.score) return b.score - a.score;
        return a.value.localeCompare(b.value);
    });

    return matches.map(m => m.value);
}

const parseTransactionFormConfig = () => {
    const element = document.getElementById('transaction-form-config');

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error('Failed to parse transaction form config', error);
        return null;
    }
};

const transactionFormConfig = parseTransactionFormConfig();

if (transactionFormConfig) {
    const cloneValue = (value) => JSON.parse(JSON.stringify(value));

    window.zakatForm = () => ({
        isEdit: transactionFormConfig.isEdit,
        pembayar_name: transactionFormConfig.pembayarName,
        pembayar_address: transactionFormConfig.pembayarAddress,
        pembayar_phone: transactionFormConfig.pembayarPhone,
        shift: transactionFormConfig.shift,
        submitting: false,
        formNotice: '',
        is_transfer_global: false,
        show_tf_modal: false,
        showUnsavedModal: false,
        pendingNavigation: null,
        initialSnapshot: null,
        lastPembayarName: transactionFormConfig.pembayarName,
        hasInputStarted: false,
        fitrahBase: transactionFormConfig.fitrahBase,
        fidyahBase: transactionFormConfig.fidyahBase,
        suggestions: [],
        showSuggestions: false,
        activeIndex: -1,
        searchTimeout: null,
        autocompleteCache: {},
        persons: cloneValue(transactionFormConfig.initialPersons),
        standards: {
            fitrahUang: transactionFormConfig.fitrahBase,
            fidyahUang: transactionFormConfig.fidyahBase,
            fidyahBeras: transactionFormConfig.fidyahBeras,
            beras: transactionFormConfig.berasPerJiwa,
        },
        getSnapshot() {
            return JSON.stringify({
                p: this.pembayar_name,
                a: this.pembayar_address,
                ph: this.pembayar_phone,
                s: this.shift,
                tg: this.is_transfer_global,
                txs: this.txs,
            });
        },
        clearFormNotice() {
            this.formNotice = '';
        },
        showFormNotice(message, focusTarget = null) {
            this.formNotice = message;

            this.$nextTick(() => {
                document.getElementById('transaction-form-notice')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                if (focusTarget?.reportValidity) {
                    focusTarget.reportValidity();
                } else if (focusTarget?.focus) {
                    focusTarget.focus();
                }
            });
        },
        get hasChanged() {
            if (!this.isEdit) {
                return true;
            }

            return this.initialSnapshot !== this.getSnapshot();
        },
        getEffectiveNominal(person, category) {
            const zakat = person.zakat[category];
            if (zakat.is_custom) {
                return zakat.nominal || '0';
            }

            if (category === 'fitrah') {
                return (this.fitrahBase * 1).toLocaleString('id-ID');
            }
            if (category === 'fidyah') {
                return (this.fidyahBase * (person.hari || 0)).toLocaleString('id-ID');
            }

            return zakat.nominal || '0';
        },
        openTfModal() {
            const inputs = document.querySelectorAll('.muzakki-name-input');
            let firstInvalid = null;

            this.persons.forEach((person, index) => {
                if (person.name.trim() === '' && inputs[index] && !firstInvalid) {
                    firstInvalid = inputs[index];
                }
            });

            if (firstInvalid) {
                firstInvalid.reportValidity();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            this.show_tf_modal = true;
        },
        async handleInput() {
            this.syncFirstName();

            if (this.pembayar_name.length < 1) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }

            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                // Load cache if not loaded
                if (Object.keys(this.autocompleteCache).length === 0) {
                    fetch('/api/autocomplete/data')
                        .then(r => r.json())
                        .then(data => {
                            this.autocompleteCache = data;
                            this.filterSuggestions();
                        })
                        .catch(err => console.error('Autocomplete cache load failed:', err));
                    return;
                }

                this.filterSuggestions();
            }, 200);
        },
        filterSuggestions() {
            const candidates = this.autocompleteCache.pembayar_name || [];
            const matches = fuzzyMatch(this.pembayar_name, candidates);
            this.suggestions = matches.slice(0, 10).map(name => ({ name, address: '', phone: '' }));
            this.showSuggestions = this.suggestions.length > 0;
            this.activeIndex = -1;
        },
        selectSuggestion(suggestion) {
            this.pembayar_name = suggestion.name;
            this.pembayar_address = suggestion.address || '';
            this.pembayar_phone = suggestion.phone || '';
            this.showSuggestions = false;
            this.syncFirstName();
        },
        selectNext() {
            if (this.suggestions.length > 0) {
                this.activeIndex = (this.activeIndex + 1) % this.suggestions.length;
            }
        },
        selectPrev() {
            if (this.suggestions.length > 0) {
                this.activeIndex = (this.activeIndex - 1 + this.suggestions.length) % this.suggestions.length;
            }
        },
        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.suggestions.length) {
                this.selectSuggestion(this.suggestions[this.activeIndex]);
            }
        },
        jsHighlight(text, query) {
            if (!query || query.trim() === '') {
                return this.escapeHtml(text);
            }

            const escapedText = this.escapeHtml(text);
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            return escapedText.replace(regex, '<mark class="bg-yellow-200 font-bold text-gray-900 rounded-px px-0.5">$1</mark>');
        },
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        formatCurrency(value) {
            if (!value) {
                return '';
            }

            const sanitized = String(value).replace(/[^0-9]/g, '');
            if (sanitized === '') {
                return '';
            }

            return new Intl.NumberFormat('id-ID').format(parseInt(sanitized, 10));
        },
        formatBeras(value) {
            if (!value) {
                return '';
            }

            let sanitized = String(value).replace(/,/g, '.');
            sanitized = sanitized.replace(/[^0-9.]/g, '');

            const parts = sanitized.split('.');
            if (parts.length > 2) {
                sanitized = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts.length > 1 && parts[1].length > 2) {
                sanitized = parts[0] + '.' + parts[1].substring(0, 2);
            }

            return sanitized;
        },
        parseNum(value, isBeras = false) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            let sanitized = String(value).trim().replace(/[Rp\s]/gi, '').replace(/kg/gi, '');

            if (isBeras) {
                sanitized = sanitized.replace(',', '.');
            } else {
                sanitized = sanitized.replace(/\./g, '').replace(',', '.');
            }

            const parsed = parseFloat(sanitized);
            return Number.isNaN(parsed) ? null : parsed;
        },
        isBelowStandard(person, category) {
            const zakat = person.zakat[category];
            if (!zakat.active) {
                return false;
            }

            const isBeras = zakat.metode === 'beras';
            const value = this.parseNum(zakat.nominal, isBeras);

            if (category === 'mal' || category === 'infaq') {
                return value === null || value <= 0;
            }

            if (!zakat.is_custom) {
                return false;
            }

            if (value === null || value <= 0) {
                return true;
            }

            return false;
        },
        handleTfGlobalChange() {
            if (this.is_transfer_global) {
                const inputs = document.querySelectorAll('.muzakki-name-input');
                let firstInvalid = null;

                this.persons.forEach((person, index) => {
                    if (person.name.trim() === '' && inputs[index] && !firstInvalid) {
                        firstInvalid = inputs[index];
                    }
                });

                if (firstInvalid) {
                    this.is_transfer_global = false;
                    firstInvalid.reportValidity();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                this.show_tf_modal = true;
                this.persons.forEach((person) => {
                    Object.values(person.zakat).forEach((zakat) => {
                        if (zakat.active && zakat.metode === 'uang') {
                            zakat.is_transfer = true;
                        }
                    });
                });
            } else {
                this.persons.forEach((person) => {
                    Object.values(person.zakat).forEach((zakat) => {
                        zakat.is_transfer = false;
                    });
                });
            }
        },
        init() {
            if (this.persons.length > 0) {
                this.persons.forEach((person) => {
                    if (person.zakat) {
                        Object.values(person.zakat).forEach((zakat) => {
                            if (zakat.is_transfer) {
                                this.is_transfer_global = true;
                            }
                        });
                    }
                });
            }

            const oldItems = cloneValue(transactionFormConfig.oldItems);
            if (oldItems.length > 0) {
                this.persons = [];
                const personMap = {};

                oldItems.forEach((item, index) => {
                    const name = item.muzakki_name;
                    if (!personMap[name]) {
                        personMap[name] = {
                            id: index + 1000,
                            name,
                            zakat: {
                                fitrah: { active: false, metode: 'uang', is_custom: false, is_transfer: false, nominal: '' },
                                fidyah: { active: false, metode: 'uang', is_custom: false, is_transfer: false, hari: '', nominal: '' },
                                mal: { active: false, metode: 'uang', is_transfer: false, nominal: '' },
                                infaq: { active: false, metode: 'uang', is_transfer: false, nominal: '' },
                            },
                        };
                        this.persons.push(personMap[name]);
                    }

                    const category = item.category;
                    if (category) {
                        const zakat = personMap[name].zakat[category];
                        zakat.active = true;
                        zakat.metode = item.metode || 'uang';
                        zakat.id = item.id || null;
                        zakat.is_transfer = !!item.is_transfer;
                        if (zakat.is_transfer) {
                            this.is_transfer_global = true;
                        }
                        if (category === 'fidyah') {
                            zakat.hari = item.hari || '';
                        }
                        const rawNominal = item.metode === 'beras' ? (item.jumlah_beras_kg || '') : (item.nominal_uang || '');
                        zakat.nominal = item.metode === 'beras' ? this.formatBeras(String(rawNominal)) : this.formatCurrency(String(rawNominal));
                        if (category === 'fitrah' || category === 'fidyah') {
                            zakat.is_custom = !!item.is_custom;
                        }
                    }
                });
            }

            this.$nextTick(() => {
                this.initialSnapshot = this.getSnapshot();
            });

            // Pre-load autocomplete cache
            fetch('/api/autocomplete/data')
                .then(r => r.json())
                .then(data => {
                    this.autocompleteCache = data;
                })
                .catch(err => console.error('Autocomplete cache pre-load failed:', err));

            window.onpageshow = () => {
                this.submitting = false;
                this.$nextTick(() => {
                    this.initialSnapshot = this.getSnapshot();
                });
            };

            // Track when user starts input
            document.addEventListener('input', (e) => {
                const input = e.target.closest('input[name], textarea[name], select[name]');
                if (!input) return;

                const value = input.value?.trim() || '';
                if (value.length > 0) {
                    this.hasInputStarted = true;
                } else {
                    // Check if any other form field has value
                    const formInputs = document.querySelectorAll('form input[name], form textarea[name], form select[name]');
                    const hasAnyValue = Array.from(formInputs).some(f => (f.value?.trim() || '').length > 0);
                    this.hasInputStarted = hasAnyValue;
                }
            });

            // Intercept navigation links to show custom unsaved modal
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (!link || this.submitting || !this.hasInputStarted) return;

                // Allow same-page navigation (hash links)
                const href = link.getAttribute('href');
                if (href.startsWith('#') || href === window.location.pathname) return;

                e.preventDefault();
                this.pendingNavigation = href;
                this.showUnsavedModal = true;
            }, true);
        },

        discardChanges() {
            const nav = this.pendingNavigation;
            this.showUnsavedModal = false;
            this.hasInputStarted = false;
            this.pendingNavigation = null;
            if (nav) {
                window.location.href = nav;
            }
        },
        syncFirstName() {
            if (this.persons.length > 0) {
                const firstPersonName = this.persons[0].name.trim();
                if (firstPersonName === '' || firstPersonName === this.lastPembayarName) {
                    this.persons[0].name = this.pembayar_name;
                }
            }

            this.lastPembayarName = this.pembayar_name;
        },
        addPerson() {
            this.persons.push({
                id: Date.now(),
                name: '',
                zakat: {
                    fitrah: { active: true, metode: 'uang', is_custom: false, is_transfer: false, nominal: '' },
                    fidyah: { active: false, metode: 'uang', is_custom: false, is_transfer: false, hari: '', nominal: '' },
                    mal: { active: false, metode: 'uang', is_transfer: false, nominal: '' },
                    infaq: { active: false, metode: 'uang', is_transfer: false, nominal: '' },
                },
            });
        },
        checkDuplicateName(personId, name) {
            if (!name) return;
            const normalized = name.trim().toLowerCase();
            const isDuplicate = this.persons.some(
                p => p.id !== personId && p.name.trim().toLowerCase() === normalized
            );
            if (isDuplicate) {
                this.showFormNotice(`Nama "${name}" sudah ada di daftar. Pastikan tidak dobel!`);
            }
        },
        removePerson(index) {
            if (this.persons.length > 1) {
                this.persons.splice(index, 1);
            }
        },
        get txs() {
            const list = [];

            this.persons.forEach((person) => {
                const name = person.name || (person.id === 1 ? this.pembayar_name : '');
                if (!name) {
                    return;
                }

                ['fitrah', 'fidyah', 'mal', 'infaq'].forEach((category) => {
                    const zakat = person.zakat[category];
                    if (zakat.active) {
                        const isCustom = (category === 'fitrah' || category === 'fidyah') ? zakat.is_custom : true;
                        const value = this.parseNum(zakat.nominal, zakat.metode === 'beras');

                        list.push({
                            id: zakat.id || null,
                            muzakki_name: name,
                            category,
                            metode: zakat.metode,
                            jiwa: 1,
                            hari: category === 'fidyah' ? zakat.hari : null,
                            nominal_uang: zakat.metode !== 'beras' ? (isCustom ? value : null) : null,
                            jumlah_beras_kg: zakat.metode === 'beras' ? (isCustom ? value : null) : null,
                            is_custom: isCustom,
                            is_transfer: zakat.is_transfer || false,
                        });
                    }
                });
            });

            return list;
        },
        prepareSubmit(event) {
            this.clearFormNotice();

            if (this.txs.length === 0) {
                event.preventDefault();
                const firstNameInput = document.querySelector('.muzakki-name-input');
                this.showFormNotice('Pastikan minimal ada satu nama muzakki dan setidaknya satu jenis zakat yang dipilih sebelum transaksi disimpan.', firstNameInput);
                return;
            }

            let hasInvalidCustom = false;
            this.persons.forEach((person) => {
                ['fitrah', 'fidyah'].forEach((category) => {
                    if (this.isBelowStandard(person, category)) {
                        hasInvalidCustom = true;
                    }
                });

                ['mal', 'infaq'].forEach((category) => {
                    if (person.zakat[category].active && this.isBelowStandard(person, category)) {
                        hasInvalidCustom = true;
                    }
                });
            });

            if (hasInvalidCustom) {
                event.preventDefault();
                const invalidField = document.querySelector('.border-red-500 input');
                this.showFormNotice('Masih ada nominal atau takaran custom yang belum valid. Pastikan nilainya terisi dan lebih dari 0.', invalidField);
                return;
            }

            if (this.is_transfer_global) {
                const hasTransferItem = this.txs.some((tx) => tx.is_transfer);
                if (!hasTransferItem) {
                    event.preventDefault();
                    this.showFormNotice('Metode transfer sudah diaktifkan, tetapi item yang dibayar via transfer belum dipilih. Silakan tentukan item transfer terlebih dahulu.');
                    this.openTfModal();
                    return;
                }
            }

            this.submitting = true;
        },
    });
}
