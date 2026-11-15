/**
 * Bară de anunțuri care rotește mesajele.
 *
 * <div x-data="announcement(2)">
 *   <p :class="{ 'is-active': current === 0 }">...</p>
 * </div>
 */
export default (count = 1, interval = 4000) => ({
	current: 0,
	timer: null,

	init() {
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (count < 2 || reducedMotion) return;

		this.timer = setInterval(() => {
			this.current = (this.current + 1) % count;
		}, interval);
	},

	destroy() {
		clearInterval(this.timer);
	},
});
