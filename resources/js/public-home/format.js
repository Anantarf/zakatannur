export const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

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

export const animateValue = (obj, start, end, duration = 2000, type = 'uang') => {
    if (!obj) {
        return;
    }

    let startTimestamp = null;

    const step = (timestamp) => {
        if (!startTimestamp) {
            startTimestamp = timestamp;
        }

        const elapsed = timestamp - startTimestamp;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = easeOutExpo(progress);
        const current = easedProgress * (end - start) + start;

        if (type === 'uang') {
            obj.textContent = 'Rp ' + Math.floor(current).toLocaleString('id-ID');
        } else if (type === 'beras') {
            obj.textContent = current.toFixed(2).replace('.', ',') + ' Kg';
        } else if (type === 'jiwa') {
            obj.textContent = Math.floor(current).toLocaleString('id-ID') + ' Jiwa';
        } else {
            obj.textContent = Math.floor(current).toLocaleString('id-ID');
        }

        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };

    window.requestAnimationFrame(step);
};
