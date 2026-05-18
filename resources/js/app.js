import './bootstrap';
import './chatbot-widget';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse'
 
Alpine.plugin(collapse)
window.Alpine = Alpine;

const bootPageModules = async () => {
    const imports = [];

    if (document.getElementById('public-home-config')) {
        imports.push(import('./public-home'));
    }

    if (document.getElementById('transaction-form-config')) {
        imports.push(import('./transaction-form'));
    }

    await Promise.all(imports);
};

bootPageModules().finally(() => {
    Alpine.start();
});
