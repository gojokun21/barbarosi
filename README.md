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
    components/           stiluri pe componente (header, mega meniu, footer, card produs, butoane, galerie...)
    sections/             stiluri pentru secțiunile primei pagini
    pages/                stiluri specifice unei pagini (produs, coș, checkout, comandă primită)
  js/shop/                coș și checkout: completează cart.js / checkout.js din WooCommerce
  images/, fonts/         referite din SCSS cu url('../images/...')
src/
  StarterSite.php         context Timber, theme supports, CPT-uri, filtre Twig
  Shop.php                WooCommerce -> array-urile din template-uri (card, pagina de produs) + filtre WC
  Checkout.php            coș + checkout: câmpuri, texte, ordinea elementelor, helpers pentru woocommerce/
  Search.php              căutare: endpoint-uri REST pentru panoul live + datele paginii de rezultate
  DemoContent.php         TEMPORAR: date demo pentru header, footer și prima pagină
  DemoSeeder.php          TEMPORAR: comanda `wp barbarosi seed` (produse demo)
  demo/products.php       TEMPORAR: catalogul demo (15 produse)
  Vite.php                încărcare assets (dev server sau manifest)
front-page.php            prima pagină -> views/front-page.twig
single-product.php        pagina de produs WooCommerce -> views/single-product.twig
search.php                rezultatele căutării -> views/search.twig
page.php                  pagini; Coșul -> views/cart.twig, Finalizare comandă -> views/checkout.twig
views/                    template-uri Twig
  partial/                header, mega meniu, drawer mobil, card produs, imagine, iconițe, header/footer checkout
  sections/               secțiunile primei pagini (hero, produse, shop the look...)
woocommerce/              template-uri WooCommerce suprascrise (coș, checkout, cantitate)
dist/                     generat de `npm run build`
```

## Coș și checkout

Fluxul e cel clasic WooCommerce (paginile au shortcode-urile `[woocommerce_cart]` / `[woocommerce_checkout]`,
nu blocurile). Cadrul paginii e în Twig (`views/cart.twig`, `views/checkout.twig` — checkout-ul are header și
footer simplificate), iar markup-ul în template-urile suprascrise din `woocommerce/cart/` și `woocommerce/checkout/`.

Template-urile păstrează hook-urile și clasele de care depind `cart.js` / `checkout.js` din WooCommerce
(actualizări AJAX, cupoane, metode de plată) — fiecare fișier spune în antet ce nu trebuie redenumit.
CSS-ul WooCommerce și select2 nu se încarcă pe aceste pagini (`Shop::init`, `Checkout::dequeue_assets`).

La actualizarea WooCommerce verifică în **WooCommerce → Stare → Template-uri** dacă vreun template suprascris
a rămas în urmă (`@version` din antet) și preia modificările.

Ce trebuie configurat în magazin (nu ține de temă):

- **Limba site-ului: Română** (Setări → General). Textele temei sunt în română, dar mesajele WooCommerce
  (validări, „Undo?”, coduri de reducere, emailuri, numele țărilor) vin din traducerea pluginului.
- zone de livrare și metode de plată. Bara „livrare gratuită” din coș apare automat dacă zona clientului
  are *Free shipping* cu sumă minimă.
- textul despre datele personale și pagina de termeni (WooCommerce → Setări → Avansat / Personalizare → Checkout).

## Căutare

Câmpul din header (`views/partial/header-search.twig` + `assets/js/components/header-search.js`) deschide un
panou cu rezultate live: pe desktop sub header, pe mobil pe tot ecranul (iconița de lângă coș sau „Caută...” din
meniu). Fără termen arată căutările populare și noutățile; cât scrii, produsele
(cu termenul subliniat), categoriile potrivite și „Vezi toate”. Tastatură: ↓/↑ prin rezultate, Esc închide,
`/` sau Ctrl/⌘+K deschide căutarea de oriunde. Enter duce la pagina de rezultate (`search.php`).

- `/?s=…` caută doar produse, cu interogarea WooCommerce (sortare după relevanță, paginare, vizibilitate) —
  panoul și pagina dau aceleași rezultate.
- Pe lângă titlu și descriere se caută în SKU și în numele categoriilor, etichetelor și culorilor
  („hanorace” găsește produsele din „Hanorace & pulovere”); diacriticele nu contează.
- Pagina de rezultate are filtre pe categorii (chip-uri cu numărul de produse) și, fără rezultate, sugestii.
- REST (public, răspuns cache-uit 5 minute în browser): `GET /wp-json/barbarosi/v1/search?q=…` și
  `GET /wp-json/barbarosi/v1/search/recommended`.
- Căutările populare sunt temporar în `DemoContent::popular_searches()`.

## Conținut demo

Prima pagină, header-ul și footer-ul primesc datele din `src/DemoContent.php` (imaginile vin
temporar de pe CDN-ul dailypaperclothing.com). Pentru producție înlocuiește metodele de acolo cu
date reale (produse WooCommerce, meniuri WP, ACF) păstrând forma array-urilor — template-urile
nu trebuie modificate.

### Produse demo (WooCommerce)

Catalogul din `src/demo/products.php` se importă cu WP-CLI (în Local: click dreapta pe site →
**Open site shell**):

```bash
wp barbarosi seed              # creează / actualizează cele 15 produse (pozele se descarcă o singură dată)
wp barbarosi seed --fresh      # le șterge și le creează din nou
wp barbarosi unseed --images   # șterge produsele demo și pozele lor
```

Seed-ul creează atributele **Culoare** (`pa_culoare`) și **Mărime** (`pa_marime`), categoriile, tag-ul
**Nou** și ținutele din „Combină cu” (upsells). Fiecare culoare e un produs separat; culorile aceluiași
model sunt legate prin câmpul `_barbarosi_color_group`, iar mărimile sunt variații. Pe un produs adăugat
din admin, pentru swatch-uri completează meta-urile `_barbarosi_color_group` și `_barbarosi_swatch`
(cod hex) și, pentru „Ghid de mărimi”, `_barbarosi_size_guide` (`tops` sau `bottoms`).

Cardurile din prima pagină iau produsele după slug din WooCommerce; înainte de seed afișează
datele statice din catalog, cu link spre shop.

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
