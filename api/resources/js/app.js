import './bootstrap';
import Alpine from 'alpinejs';
import { initThemeStore } from './theme';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    initThemeStore(Alpine);
});

Alpine.start();