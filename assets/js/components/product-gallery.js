/**
 * Galeria din pagina de produs. Derularea e nativă (scroll-snap: swipe, trackpad, tastatură);
 * componenta doar ține minte poza curentă și mută galeria din săgeți.
 *
 * <div x-data="productGallery"><ul x-ref="track" @scroll.debounce="update()">...</ul></div>
 */
export default () => ({
	index: 0,
	count: 0,

	init() {
		this.count = this.$refs.track.children.length;
	},

	update() {
		const track = this.$refs.track;

		// Ultima poză nu poate ajunge la marginea din stânga — la capăt o considerăm curentă.
		if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 2) {
			this.index = this.count - 1;
			return;
		}

		const slideWidth = track.children[0]?.offsetWidth || 1;
		this.index = Math.round(track.scrollLeft / slideWidth);
	},

	go(index) {
		const slide = this.$refs.track.children[index];
		if (!slide) return;

		const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		this.$refs.track.scrollTo({ left: slide.offsetLeft, behavior: reduceMotion ? 'auto' : 'smooth' });
	},

	prev() {
		this.go(Math.max(0, this.index - 1));
	},

	next() {
		this.go(Math.min(this.count - 1, this.index + 1));
	},
});
