import './bootstrap';
import './chatbot-widget';
import './public-home';
import './transaction-form';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse'
 
Alpine.plugin(collapse)
window.Alpine = Alpine;
 
Alpine.start();
