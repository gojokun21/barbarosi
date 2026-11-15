import Alpine from 'alpinejs';

import siteHeader from './components/site-header.js';
import announcement from './components/announcement.js';
import newsletter from './components/newsletter.js';

// Componente reutilizabile: <div x-data="siteHeader">
Alpine.data('siteHeader', siteHeader);
Alpine.data('announcement', announcement);
Alpine.data('newsletter', newsletter);

// Expus global pentru debugging în consolă și pentru plugin-uri externe
window.Alpine = Alpine;

Alpine.start();
