/**
 * Căutare cu rezultate live (partial/header-search.twig, date din src/Search.php prin REST).
 *
 * Desktop: câmpul din header + panou sub header. Mobil: iconița deschide un ecran complet cu același câmp.
 * Fără termen (sau fără rezultate) panoul arată căutările populare și noutățile.
 *
 * - cererile sunt amânate cât scrii (DEBOUNCE), cele vechi anulate, iar răspunsurile ținute în memorie
 * - rezultatele anterioare rămân vizibile (estompate) până vin cele noi — fără „pâlpâit”
 * - tastatură: ↓/↑ prin rezultate, Esc închide, „/” sau Ctrl/⌘+K deschide căutarea de oriunde
 * - starea „deschis” e în Alpine.store('search') — header-ul o citește (fundal alb, scroll blocat)
 *
 * <div x-data="headerSearch({ endpoint, recommendedEndpoint, searchUrl })">
 */
const MIN_LENGTH = 2; // ca Search::MIN_LENGTH
const DEBOUNCE = 180; // ms
const CACHE_LIMIT = 30;

// „Cămașă” -> „camasa”: comparăm fără diacritice și fără majuscule, ca baza de date.
const fold = (text) => text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

const escapeHtml = (text) =>
	text.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);

const isVisible = (el) => (el.checkVisibility ? el.checkVisibility() : el.offsetParent !== null);

const isEditable = (el) => el instanceof Element && el.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"]');

export default ({ endpoint, recommendedEndpoint, searchUrl }) => {
	// Starea internă, nereactivă (nu apare în template)
	const cache = new Map();
	let controller = null;
	let timer = null;
	let recommendedRequested = false;
	let returningFocus = false; // close() mută focusul în câmp — fără să redeschidă panoul (@focus)

	const abort = () => {
		controller?.abort();
		controller = null;
	};

	// Cache LRU mic: termenii la care revii (ștergi o literă, scrii la loc) apar instant.
	const cacheResult = (term, data) => {
		cache.delete(fold(term));
		cache.set(fold(term), data);
		if (cache.size > CACHE_LIMIT) cache.delete(cache.keys().next().value);
	};

	return {
		query: '',
		results: null, // ultimul răspuns: { query, total, count, products[], categories[] }
		loading: false,
		error: false,
		recommended: [],

		init() {
			this.$watch('query', () => this.schedule());
		},

		get open() {
			return this.$store.search.open;
		},

		get term() {
			return this.query.trim().replace(/\s+/g, ' ');
		},

		get hasTerm() {
			return this.term.length >= MIN_LENGTH;
		},

		/**
		 * idle (fără termen) | loading (primele rezultate) | results | empty | error
		 * Cât se încarcă rezultatele pentru termenul nou, cele anterioare rămân în starea `results`.
		 */
		get state() {
			if (!this.hasTerm) return 'idle';
			if (this.error && !this.loading) return 'error';
			if (this.results?.products.length) return 'results';
			if (this.loading || !this.results) return 'loading';
			return 'empty';
		},

		// Anunțat de cititoarele de ecran („7 produse”)
		get status() {
			return ['results', 'empty'].includes(this.state) && !this.loading ? this.results.count : '';
		},

		/* Deschidere / închidere
		   ---------------------------------------------------------------- */

		show() {
			if (returningFocus) return;

			if (!this.open) {
				this.$store.search.open = true;
				// Clasa se pune imediat, nu la următorul tick Alpine: pe mobil câmpul e ascuns până atunci,
				// iar iOS deschide tastatura doar dacă focus() vine sincron din click.
				this.$root.classList.add('is-open');
				this.fitPanel();
				this.loadRecommended();
			}

			if (document.activeElement !== this.$refs.input) {
				this.$refs.input.focus({ preventScroll: true });
			}
		},

		close() {
			if (!this.open) return;

			this.$store.search.open = false;

			// Focusul dintr-un element care dispare ar ajunge pe <body>. Îl ducem în câmp (desktop) sau,
			// pe mobil, unde câmpul dispare odată cu panoul, pe iconița care l-a deschis.
			const target = isVisible(this.$refs.toggle) ? this.$refs.toggle : this.$refs.input;

			if (this.$root.contains(document.activeElement) && document.activeElement !== target) {
				returningFocus = true;
				target.focus({ preventScroll: true });
				returningFocus = false;
			}
		},

		// Panoul de pe desktop ocupă restul ecranului sub header (header-ul poate fi sub bara de anunțuri).
		fitPanel() {
			const header = this.$root.closest('.site-header');
			if (header) {
				this.$refs.panel.style.setProperty('--search-top', `${Math.max(0, header.getBoundingClientRect().bottom)}px`);
			}
		},

		onFocusOut(event) {
			// Tab în afara căutării (pe alt link din header) o închide; click pe un spațiu gol din panou nu.
			if (event.relatedTarget && !this.$root.contains(event.relatedTarget)) this.close();
		},

		onPointerDown(event) {
			if (this.open && !this.$root.contains(event.target)) this.close();
		},

		shortcut(event) {
			const slash = event.key === '/' && !isEditable(event.target);
			const commandK = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';

			if (slash || commandK) {
				event.preventDefault();
				this.show();
			}
		},

		/* Căutare
		   ---------------------------------------------------------------- */

		schedule() {
			clearTimeout(timer);
			this.error = false;

			const term = this.term;

			if (term.length < MIN_LENGTH) {
				abort();
				this.loading = false;
				this.results = null;
				return;
			}

			const cached = cache.get(fold(term));
			if (cached) {
				abort();
				this.loading = false;
				this.results = cached;
				return;
			}

			this.loading = true;
			timer = setTimeout(() => this.fetchResults(term), DEBOUNCE);
		},

		async fetchResults(term) {
			abort();
			controller = new AbortController();

			const url = new URL(endpoint, window.location.href);
			url.searchParams.set('q', term);

			try {
				const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
				if (!response.ok) throw new Error(`HTTP ${response.status}`);

				const data = await response.json();
				cacheResult(term, data);

				// Între timp termenul s-ar fi putut schimba — arătăm doar răspunsul pentru ce e acum în câmp.
				if (fold(term) === fold(this.term)) {
					this.results = data;
					this.loading = false;
				}
			} catch (error) {
				if (error.name === 'AbortError') return;
				if (fold(term) === fold(this.term)) {
					this.loading = false;
					this.error = true;
				}
			}
		},

		async loadRecommended() {
			if (recommendedRequested) return;
			recommendedRequested = true;

			try {
				const response = await fetch(recommendedEndpoint, { headers: { Accept: 'application/json' } });
				if (!response.ok) throw new Error(`HTTP ${response.status}`);
				this.recommended = (await response.json()).products ?? [];
			} catch {
				recommendedRequested = false; // reîncercăm la următoarea deschidere
			}
		},

		urlFor(term) {
			const url = new URL(searchUrl, window.location.href);
			url.searchParams.set('s', term);
			return url.href;
		},

		pick(term) {
			this.query = term;
			this.show();
		},

		// Căutările populare completează câmpul și arată rezultatele pe loc, fără să plece din pagină.
		// Ctrl/⌘/Shift + click păstrează comportamentul link-ului (tab nou).
		pickLink(event) {
			if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

			event.preventDefault();
			this.pick(event.currentTarget.dataset.term);
		},

		clear() {
			this.query = '';
			this.$refs.input.focus();
		},

		// Fără text nu trimitem formularul (pagina de rezultate ar fi goală).
		submit(event) {
			if (!this.term) {
				event.preventDefault();
				this.$refs.input.focus();
			}
		},

		/* Tastatură
		   ---------------------------------------------------------------- */

		// Link-urile prin care te miști cu săgețile (doar cele vizibile): fără termen întâi căutările populare
		// din stânga, cu termen întâi rezultatele.
		items() {
			const columns = this.state === 'idle' ? [this.$refs.aside, this.$refs.main] : [this.$refs.main, this.$refs.aside];

			return columns.flatMap((column) => [...column.querySelectorAll('[data-search-item]')]).filter(isVisible);
		},

		focusItem(index) {
			this.show();
			this.$nextTick(() => this.items()[index]?.focus());
		},

		onPanelKeydown(event) {
			const items = this.items();
			const index = items.indexOf(document.activeElement);

			if (index === -1) return;

			if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
				event.preventDefault();
				items[Math.min(index + 1, items.length - 1)].focus();
			} else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
				event.preventDefault();
				(index === 0 ? this.$refs.input : items[index - 1]).focus();
			} else if (event.key === 'Backspace' || (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey && event.key !== ' ')) {
				// Continui să scrii fără să te întorci cu mouse-ul în câmp (tasta ajunge în câmp).
				this.$refs.input.focus();
			}
		},

		/* Afișare
		   ---------------------------------------------------------------- */

		/**
		 * Titlul cu termenii căutați în <mark>, ignorând diacriticele („camasa” marchează „Cămașă”).
		 * Textul e escapat — rezultatul merge în x-html.
		 */
		highlight(text) {
			const words = fold(this.results?.query ?? '')
				.split(/\s+/)
				.filter((word) => word.length >= MIN_LENGTH);

			if (!words.length || !text) return escapeHtml(text ?? '');

			// Textul „pliat” caracter cu caracter, ca pozițiile găsite să indice caracterele originale.
			const chars = Array.from(text);
			const owner = [];
			let folded = '';

			chars.forEach((char, i) => {
				const part = fold(char);
				folded += part;
				for (let k = 0; k < part.length; k++) owner.push(i);
			});

			const marked = new Array(chars.length).fill(false);

			for (const word of words) {
				for (let at = folded.indexOf(word); at !== -1; at = folded.indexOf(word, at + word.length)) {
					for (let k = at; k < at + word.length; k++) marked[owner[k]] = true;
				}
			}

			let html = '';
			let inside = false;

			chars.forEach((char, i) => {
				if (marked[i] !== inside) {
					html += marked[i] ? '<mark>' : '</mark>';
					inside = marked[i];
				}
				html += escapeHtml(char);
			});

			return inside ? `${html}</mark>` : html;
		},
	};
};
