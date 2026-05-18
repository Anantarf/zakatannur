const seenTransactionsStorageKey = 'seen_tx_ids';

export const readSeenTransactionIds = () => {
    try {
        return JSON.parse(localStorage.getItem(seenTransactionsStorageKey) || '[]');
    } catch {
        return [];
    }
};

export const hasSeenTransactionSnapshot = () => localStorage.getItem(seenTransactionsStorageKey) !== null;

export const writeSeenTransactionIds = (ids) => {
    localStorage.setItem(seenTransactionsStorageKey, JSON.stringify(ids.slice(-50)));
};

export const buildNotificationMessage = (items, formatCategory, joinGrammatically) => {
    const categories = [...new Set(items.map((item) => formatCategory(item.category)))];
    const parts = [];
    const sumUang = items.reduce((sum, item) => sum + (item.uang || 0), 0);
    const sumBeras = items.reduce((sum, item) => sum + (item.beras || 0), 0);

    if (sumUang > 0) {
        parts.push('Rp ' + sumUang.toLocaleString('id-ID'));
    }

    if (sumBeras > 0) {
        parts.push(sumBeras.toFixed(2).replace('.', ',') + ' Kg');
    }

    return `Alhamdulillah! Diperoleh ${joinGrammatically(categories)}: ${parts.join(' dan ')}`;
};
