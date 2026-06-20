import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
window.Alpine = Alpine;

Alpine.store('toast', {
    show: false,
    message: '',
    undoRoute: null,
    _timer: null,

    flash(message, undoRoute = null) {
        clearTimeout(this._timer);
        this.message = message;
        this.undoRoute = undoRoute;
        this.show = true;
        this._timer = setTimeout(() => { this.show = false; }, 5000);
    },

    dismiss() {
        clearTimeout(this._timer);
        this.show = false;
    },
});

const bootPageModules = async () => {
    const imports = [];

    if (document.getElementById('public-home-config')) {
        imports.push(import('./public-home'));
    }

    if (document.getElementById('transaction-form-config')) {
        imports.push(import('./transaction-form'));
    }

    if (document.querySelector('[data-chatbot-widget]')) {
        imports.push(import('./chatbot-widget'));
    }

    await Promise.all(imports);
};

bootPageModules().finally(() => {
    Alpine.start();
});
