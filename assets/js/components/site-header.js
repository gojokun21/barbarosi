/**
 * Header: transparent peste hero (doar pe prima pagină), mega meniu pe hover/focus, drawer pe mobil.
 * Cât e deschisă căutarea ($store.search, vezi header-search.js) header-ul e alb, fără mega meniu.
 *
 * <header x-data="siteHeader({ transparent: true })" :class="{ 'is-transparent': !solid }">
 */
const CLOSE_DELAY = 150; // ms — evită închiderea meniului când mouse-ul trece între link și panou
const SCROLL_THRESHOLD = 10;

export default ({ transparent = false } = {}) => ({
	transparent,
	scrolled: false,
	hovered: false,
	activeMenu: null,
	drawerOpen: false,
	closeTimer: null,

	init() {
		const onScroll = () => {
			this.scrolled = window.scrollY > SCROLL_THRESHOLD;
		};
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });

		this.$watch('drawerOpen', (open) => {
			this.syncScrollLock();
			if (open) {
				this.activeMenu = null;
				this.$nextTick(() => this.$refs.drawerClose?.focus());
			}
		});

		this.$watch('$store.search.open', (open) => {
			if (open) {
				clearTimeout(this.closeTimer);
				this.activeMenu = null;
				this.drawerOpen = false;
			}
			this.syncScrollLock();
		});

		// Drawer-ul e doar pentru mobil — îl închidem dacă fereastra trece pe desktop.
		window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
			if (e.matches) this.drawerOpen = false;
		});
	},

	get solid() {
		return (
			!this.transparent ||
			this.scrolled ||
			this.hovered ||
			this.activeMenu !== null ||
			this.drawerOpen ||
			this.$store.search.open
		);
	},

	// Drawer-ul și căutarea pot fi deschise pe rând — scroll-ul rămâne blocat cât e deschis oricare.
	syncScrollLock() {
		document.documentElement.classList.toggle('is-scroll-locked', this.drawerOpen || this.$store.search.open);
	},

	openMenu(key) {
		// Panoul de căutare ocupă locul mega meniului — nu le suprapunem.
		if (this.$store.search.open) return;

		clearTimeout(this.closeTimer);
		this.activeMenu = key;
	},

	scheduleClose() {
		clearTimeout(this.closeTimer);
		this.closeTimer = setTimeout(() => {
			this.activeMenu = null;
		}, CLOSE_DELAY);
	},

	toggleDrawer() {
		this.drawerOpen = !this.drawerOpen;
	},

	closeAll() {
		clearTimeout(this.closeTimer);
		this.activeMenu = null;
		this.drawerOpen = false;
	},
});
