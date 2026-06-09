const rupiahFormatter = new Intl.NumberFormat('id-ID');
const jiwaFormatter = new Intl.NumberFormat('id-ID');
const berasFormatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const safeNumber = (value) => (Number.isFinite(value) ? value : 0);
export const formatCategory = (category) => {
    const labels = {
        fitrah: 'Zakat Fitrah',
        fidyah: 'Fidyah',
        mal: 'Zakat Mal',
        infaq: 'Infaq Shodaqoh',
    };

    return labels[category] || category;
};

export const joinGrammatically = (items) => {
    if (items.length === 0) {
        return '';
    }

    if (items.length === 1) {
        return items[0];
    }

    if (items.length === 2) {
        return items[0] + ' dan ' + items[1];
    }

    return items.slice(0, -1).join(', ') + ', dan ' + items.slice(-1);
};

export const formatUang = (value) => 'Rp ' + rupiahFormatter.format(safeNumber(value));

export const formatBeras = (value) => berasFormatter.format(safeNumber(value)) + ' Kg';

export const formatJiwa = (value) => jiwaFormatter.format(safeNumber(value)) + ' Jiwa';

export const formatJiwaPlain = (value) => jiwaFormatter.format(safeNumber(value));

export const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

export const animateValue = (obj, start, end, duration = 2000, type = 'uang') => {
    if (!obj) {
        return;
    }

    let startTimestamp = null;
    let lastRendered = '';

    const render = (value) => {
        let text;
        if (type === 'uang') {
            text = formatUang(value);
        } else if (type === 'beras') {
            text = formatBeras(value);
        } else if (type === 'jiwa') {
            text = formatJiwa(value);
        } else {
            text = jiwaFormatter.format(Math.floor(value));
        }

        if (text !== lastRendered) {
            obj.textContent = text;
            lastRendered = text;
        }
    };

    const step = (timestamp) => {
        if (!startTimestamp) {
            startTimestamp = timestamp;
        }

        const elapsed = timestamp - startTimestamp;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = easeOutExpo(progress);
        const current = easedProgress * (end - start) + start;

        if (type === 'uang' || type === 'jiwa') {
            render(Math.floor(current));
        } else {
            render(current);
        }

        if (progress < 1) {
            globalThis.requestAnimationFrame(step);
        }
    };

    globalThis.requestAnimationFrame(step);
};