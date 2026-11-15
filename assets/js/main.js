import Alpine from 'alpinejs';

import siteHeader from './components/site-header.js';
import headerSearch from './components/header-search.js';
import announcement from './components/announcement.js';
import newsletter from './components/newsletter.js';
import productGallery from './components/product-gallery.js';
import productForm from './components/product-form.js';
import initCart from './shop/cart.js';
import initCheckout from './shop/checkout.js';

// Starea comună: căutarea e deschisă (header-search.js) -> header-ul devine alb și blochează scroll-ul (site-header.js)
Alpine.store('search', { open: false });

// Componente reutilizabile: <div x-data="siteHeader">
Alpine.data('siteHeader', siteHeader);
Alpine.data('headerSearch', headerSearch);
Alpine.data('announcement', announcement);
Alpine.data('newsletter', newsletter);
Alpine.data('productGallery', productGallery);
Alpine.data('productForm', productForm);

// Coș și checkout: completează scripturile WooCommerce (event-uri delegate, fără Alpine)
if (document.body.classList.contains('woocommerce-cart')) initCart();
if (document.body.classList.contains('woocommerce-checkout')) initCheckout();

// Expus global pentru debugging în consolă și pentru plugin-uri externe
window.Alpine = Alpine;

Alpine.start();
