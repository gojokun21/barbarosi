# Barbarosi — temă WordPress

Timber 2 (Twig) + Vite 8 (SCSS, Alpine.js).

## Setup

```bash
composer install
npm install
npm run build
```

## Dezvoltare

```bash
npm run dev
```

Deschide `http://barbarosi.local` (nu `localhost:5173`). Cât timp rulează serverul Vite:

- modificările în SCSS se aplică instant, fără refresh (HMR)
- modificările în JS / `.twig` / `.php` dau reload automat

La oprire (Ctrl+C) site-ul trece singur pe fișierele din `dist/`.

> Dacă site-ul încearcă să încarce de pe `localhost:5173` deși serverul e oprit,
> șterge fișierul `hot` din rădăcina temei (rămâne doar dacă procesul a fost omorât forțat).

## Producție

```bash
npm run build
```

Fișierele compilate ajung în `dist/` (cu hash în nume). Pe server trebuie urcate `dist/` și `vendor/`;
`node_modules/` și `assets/` nu sunt necesare. Pe mediul `production` fișierul `hot` e ignorat.

## Structură

```
assets/
  js/
    main.js               entry JS — înregistrează componentele Alpine
    components/           componente Alpine.data()
  scss/
    main.scss             entry SCSS
    abstracts/            variabile, mixin-uri (fără output CSS)
    base/                 reset, stiluri globale
    components/           stiluri pe componente (header, mega meniu, footer, card produs...)
    sections/             stiluri pentru secțiunile primei pagini
  images/, fonts/         referite din SCSS cu url('../images/...')
src/
  StarterSite.php         context Timber, theme supports, CPT-uri, filtre Twig
  DemoContent.php         TEMPORAR: date demo pentru header, footer și prima pagină
  Vite.php                încărcare assets (dev server sau manifest)
front-page.php            prima pagină -> views/front-page.twig
views/                    template-uri Twig
  partial/                header, mega meniu, drawer mobil, card produs, imagine, iconițe
  sections/               secțiunile primei pagini (hero, produse, shop the look...)
dist/                     generat de `npm run build`
```

## Conținut demo

Prima pagină, header-ul și footer-ul primesc datele din `src/DemoContent.php` (imaginile vin
temporar de pe CDN-ul dailypaperclothing.com). Pentru producție înlocuiește metodele de acolo cu
date reale (produse WooCommerce, meniuri WP, ACF) păstrând forma array-urilor — template-urile
nu trebuie modificate.

## Rețete

**Partial SCSS nou** — creează `assets/scss/components/_card.scss`:

```scss
@use '../abstracts' as *;

.card {
	padding: $gutter;

	@include mq(md) { ... }
}
```

apoi adaugă `@forward 'card';` în `assets/scss/components/_index.scss`.

**Componentă Alpine nouă** — creează `assets/js/components/dropdown.js` (`export default () => ({ ... })`),
înregistreaz-o în `main.js` cu `Alpine.data('dropdown', dropdown)` și folosește în Twig cu `x-data="dropdown"`.

**Date din PHP în JS** — `wp_localize_script( 'barbarosi-main', 'barbarosi', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );`
în același hook `wp_enqueue_scripts` din `functions.php`, apoi `window.barbarosi.ajaxUrl` în JS.
