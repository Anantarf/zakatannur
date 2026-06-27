# Hybrid Auto-complete Across All Search Bars

**Date:** 2026-06-27  
**Status:** Approved  
**Scope:** Implement fuzzy-matching auto-complete suggestions for all search/input fields across the application

## Overview

Add intelligent auto-complete to search bars and input fields (pembayar_name, penerima_name, category, transaction number, etc) using a hybrid caching approach: pre-load data on app startup, cache in sessionStorage, and perform fuzzy matching client-side for instant results.

## Problem Statement

Current search experience requires exact matching or full data load per query. Users want suggestions while typing to speed up data entry and discovery.

## Solution Approach: Hybrid Caching

### Architecture Decision

**Why Hybrid?**
- **Pre-load on startup:** Load unique names, categories, and other searchable fields once when page loads
- **Client-side fuzzy search:** Match user input against cached data (sub-10ms response)
- **sessionStorage persistence:** Reuse cache across page navigations within a session
- **Minimal server load:** No per-keystroke queries to backend

This balances:
- ✅ Zero latency for user (instant suggestions)
- ✅ Minimal server overhead
- ✅ Works even if backend is slow
- ❌ Limited to data that fits in browser memory (~1-5K records)
- ❌ Data is session-stale (refreshes on page reload)

### Data Sources & Scope

**Phase 1 - Core Fields:**
1. **Pembayar (Payer) Name** — unique names from ZakatTransaction
2. **Penerima (Recipient) Name** — unique names from ZakatTransaction  
3. **Category/Jenis Zakat** — fixed list from TransactionCategory or config
4. **Transaction Number (no_transaksi)** — recent/indexed transactions

**Phase 2 - Extended (if needed):**
- Phone numbers (pembayar_phone, penerima_phone)
- Addresses (pembayar_address, penerima_address)
- Bank accounts, mailing lists, etc

## Technical Design

### Component: `autocomplete.js` (New)

**Responsibilities:**
- Fuzzy matching algorithm (case-insensitive substring + Levenshtein distance tolerance)
- Cache management (load, store, invalidate in sessionStorage)
- Debounced input listener
- Suggestion rendering

**API:**
```javascript
// Initialize once per page
Autocomplete.init({
  cacheKey: 'zakat_autocomplete_cache',
  debounceMs: 200,
  maxResults: 10,
  fetchEndpoint: '/api/autocomplete/data',
});

// Attach to input field
Autocomplete.attach(inputElement, {
  type: 'pembayar_name',  // data type identifier
  minChars: 1,
  onSelect: (value) => console.log('Selected:', value),
});
```

### Backend Endpoint: `GET /api/autocomplete/data`

**Purpose:** Return all unique searchable values grouped by type  
**Query Params:**
- `types` (optional): comma-separated list of data types to fetch (e.g., `pembayar_name,penerima_name,category`)
- Default: fetch all types

**Response:**
```json
{
  "pembayar_name": ["Ahmad Hidayat", "Budi Santoso", ...],
  "penerima_name": ["Yayasan Al-Hajar", "Panti Asuhan", ...],
  "category": ["Zakat Mal", "Zakat Fitrah", ...],
  "no_transaksi": ["TRX-2026-001", "TRX-2026-002", ...]
}
```

**Performance:**
- Cache result in-memory on backend (refresh every 5 min or on-demand)
- Return gzipped JSON
- Expected size: <100KB for typical dataset

### Frontend Data Flow

```
Page Load (first time)
  ↓
Check sessionStorage['zakat_autocomplete_cache']
  ├─ Cache exists & valid → use it
  └─ Not found → fetch from /api/autocomplete/data
                    → store in sessionStorage
                    → use it
  ↓
User types in <input autocomplete="off" data-autocomplete="pembayar_name">
  ↓
Debounce input change (200ms)
  ↓
Fuzzy search against cached data for type "pembayar_name"
  ↓
Render <ul> dropdown with top 10 matches
  ↓
User clicks suggestion or presses Arrow Down/Enter
  ↓
Populate field, close dropdown
```

### Fuzzy Matching Algorithm

Simple approach (ponytail: minimal code):
1. **Case-insensitive substring match** — if query "aha" matches "Ahmad", it's a hit
2. **Levenshtein distance tolerance** — allow 1-2 character typos for names >5 chars
3. **Scoring** — rank by: (a) position of match (earlier = higher), (b) match length (exact > partial)

**Implementation:** Use existing lightweight library OR write 20-line custom matcher.

Example:
```javascript
query: "ahm"
candidates: ["Ahmad Hidayat", "Akhmad Suleman", "Rahman Aziz"]
matches: ["Ahmad Hidayat" (match at pos 0), "Akhmad Suleman" (match at pos 0)]
typo tolerance: "ahmd" → matches "Ahmad" (1 char diff)
```

### Autocomplete UI Behavior

**HTML:**
```html
<div class="autocomplete-wrapper">
  <input 
    type="text" 
    id="pembayar_name" 
    data-autocomplete="pembayar_name"
    autocomplete="off"
    placeholder="Cari nama pembayar..."
  />
  <ul class="autocomplete-dropdown" x-show="showSuggestions" x-transition>
    <template x-for="(item, idx) in suggestions" :key="idx">
      <li @click="selectSuggestion(item)" 
          :class="{'active': idx === activeIndex}">
        <span x-text="item"></span>
      </li>
    </template>
  </ul>
</div>
```

**Behavior:**
- Show dropdown only when input focused AND has suggestions
- Keyboard navigation: Arrow Up/Down, Enter to select, Escape to close
- Mouse: click item to select
- Click outside → close dropdown
- Min 1 character typed to trigger suggestions (configurable)

### Files to Create/Modify

| File | Action | Purpose |
|------|--------|---------|
| `resources/js/autocomplete.js` | Create | Core fuzzy search + cache management |
| `routes/api.php` | Modify | Add `/api/autocomplete/data` endpoint |
| `app/Http/Controllers/Api/AutocompleteController.php` | Create | Fetch & return unique values |
| `resources/views/internal/transactions/create.blade.php` | Modify | Attach autocomplete to form inputs |
| `resources/views/internal/transactions/index.blade.php` | Modify | Attach autocomplete to filter inputs |
| `resources/css/autocomplete.css` | Create | Dropdown styling |

## Data Freshness & Cache Invalidation

**Cache lifetime:** Session duration (cleared on page reload)  
**Refresh strategy:** 
- On-demand: User can manually refresh via icon button in dropdown
- Optional: Auto-refresh every 5 minutes (low priority, only if data frequently changes)
- On transaction creation: Optionally append new names to cache (low priority)

## Error Handling

- **Endpoint fails:** Degrade gracefully, show error message in dropdown ("Autocomplete unavailable"), allow manual entry
- **No matches:** Show "No suggestions" message
- **Network timeout:** Fallback to empty cache, still functional if partial data cached

## Testing Strategy

- Unit tests for fuzzy matching algorithm (10 test cases)
- Integration test: verify endpoint returns correct data structure
- Manual test: form input + select suggestion → value populated correctly
- Manual test: keyboard navigation (arrow keys, Enter, Escape)

## Performance Targets

- Cache load time: <500ms (on first page load)
- Fuzzy search response: <10ms (on subsequent typing)
- Dropdown render: <50ms
- Cache size: <100KB (sessionStorage has 5-10MB quota per browser)

## Implementation Phases

**Phase 1 (MVP):**
- Autocomplete.js with fuzzy matching
- Backend endpoint for pembayar_name, penerima_name
- Attach to transaction create form (pembayar_name, penerima_name inputs)
- Basic CSS styling

**Phase 2 (Extended):**
- Add category, no_transaksi to autocomplete
- Attach to transaction index filter
- Cache invalidation strategies
- Enhanced keyboard shortcuts

**Phase 3 (Nice-to-have):**
- Search history (recently selected names)
- Auto-refresh cache on timer
- A/B test: server-side search vs client-side (if performance matters)

## Success Criteria

✅ Users can type partial name → see suggestions within 200ms  
✅ Select suggestion → field auto-populates  
✅ Works across create form + filter bars  
✅ No impact on page load performance (<100ms added)  
✅ Graceful degradation if endpoint unavailable  

## Open Questions / TBD

- Cache refresh interval: 5 min? on-demand only? or auto-append new names on create?
- Include recent transactions in no_transaksi autocomplete, or all?
- Should phone number / address fields also have autocomplete? (Phase 2)
