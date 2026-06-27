// Minimal Levenshtein distance for typo tolerance
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

// Fuzzy match with typo tolerance
export function fuzzyMatch(query, candidates) {
    if (!query || query.length === 0) return [];

    const queryLower = query.toLowerCase();
    const matches = [];

    for (const candidate of candidates) {
        const candidateLower = candidate.toLowerCase();

        // Exact substring match (prioritize this)
        if (candidateLower.includes(queryLower)) {
            const position = candidateLower.indexOf(queryLower);
            matches.push({
                value: candidate,
                score: 1000 - position, // Earlier match = higher score
            });
            continue;
        }

        // Typo tolerance: Levenshtein distance <= 2 for longer strings
        if (query.length >= 3) {
            const distance = levenshteinDistance(query, candidate);
            if (distance <= 2) {
                matches.push({
                    value: candidate,
                    score: 100 - distance,
                });
            }
        }
    }

    // Sort by score descending, then alphabetically
    matches.sort((a, b) => {
        if (a.score !== b.score) return b.score - a.score;
        return a.value.localeCompare(b.value);
    });

    return matches.map(m => m.value);
}

// Main Autocomplete object
window.AutocompleteMatcher = {
    fuzzyMatch,

    async loadCache(endpoint) {
        const cacheKey = 'zakat_autocomplete_cache';
        const cached = sessionStorage.getItem(cacheKey);

        if (cached) {
            return JSON.parse(cached);
        }

        try {
            const response = await fetch(endpoint);
            if (!response.ok) throw new Error('Failed to load autocomplete data');

            const data = await response.json();
            sessionStorage.setItem(cacheKey, JSON.stringify(data));
            return data;
        } catch (e) {
            console.error('Autocomplete cache load failed:', e);
            return {};
        }
    },

    attachInput(element, options = {}) {
        const {
            type = 'pembayar_name',
            minChars = 1,
            maxResults = 10,
            debounceMs = 200,
            onSelect = () => {},
        } = options;

        let debounceTimer;
        let cache = {};
        let activeIndex = -1;
        const dropdownId = `autocomplete-${element.id}`;

        // Create dropdown container
        const dropdown = document.createElement('ul');
        dropdown.id = dropdownId;
        dropdown.className = 'autocomplete-dropdown hidden';
        element.parentNode.insertBefore(dropdown, element.nextSibling);

        // Load cache on first focus
        element.addEventListener('focus', async () => {
            if (Object.keys(cache).length === 0) {
                cache = await window.AutocompleteMatcher.loadCache('/api/autocomplete/data');
            }
        });

        // Handle input
        element.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            activeIndex = -1;

            debounceTimer = setTimeout(() => {
                if (query.length < minChars) {
                    dropdown.classList.add('hidden');
                    return;
                }

                const candidates = cache[type] || [];
                const results = window.AutocompleteMatcher.fuzzyMatch(query, candidates).slice(0, maxResults);

                dropdown.innerHTML = '';
                if (results.length === 0) {
                    dropdown.classList.add('hidden');
                    return;
                }

                results.forEach((result) => {
                    const li = document.createElement('li');
                    li.textContent = result;
                    li.addEventListener('click', () => {
                        element.value = result;
                        dropdown.classList.add('hidden');
                        onSelect(result);
                    });
                    dropdown.appendChild(li);
                });

                dropdown.classList.remove('hidden');
            }, debounceMs);
        });

        // Close dropdown on blur
        element.addEventListener('blur', () => {
            setTimeout(() => dropdown.classList.add('hidden'), 100);
        });

        // Keyboard navigation
        element.addEventListener('keydown', (e) => {
            const items = dropdown.querySelectorAll('li');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
                updateActiveItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && activeIndex < items.length) {
                    items[activeIndex].click();
                }
                return;
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
                activeIndex = -1;
                return;
            }

            function updateActiveItem(items) {
                items.forEach((item, idx) => {
                    if (idx === activeIndex) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }
        });
    },
};
