import { defineConfig } from 'vite';
import fs from 'node:fs';
import path from 'node:path';

const DEV_PORT = 5173;
const DEV_ORIGIN = `http://localhost:${DEV_PORT}`;
const HOT_FILE = path.resolve(import.meta.dirname, 'hot');

/**
 * Integrare WordPress:
 *  - `npm run dev` scrie fișierul `hot` -> PHP știe să încarce assets de pe serverul Vite (HMR)
 *  - la oprirea serverului fișierul e șters -> PHP revine pe build-ul din `dist/`
 *  - modificările în .twig / .php declanșează full reload în browser
 */
function wordpress() {
	const removeHot = () => fs.rmSync(HOT_FILE, { force: true });
	let isBuild = false;

	return {
		name: 'barbarosi:wordpress',
		configResolved(config) {
			isBuild = config.command === 'build';
		},
		buildStart() {
			// un `hot` rămas de la un dev server oprit forțat nu trebuie să strice build-ul
			if (isBuild) removeHot();
		},
		configureServer(server) {
			server.httpServer?.once('listening', () => fs.writeFileSync(HOT_FILE, DEV_ORIGIN));
			server.httpServer?.once('close', removeHot);
			for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
				process.once(signal, () => {
					removeHot();
					process.exit();
				});
			}
			process.once('exit', removeHot);

			server.watcher.on('change', (file) => {
				if (/\.(twig|php)$/.test(file)) {
					server.ws.send({ type: 'full-reload', path: '*' });
				}
			});
		},
	};
}

export default defineConfig(({ command }) => ({
	// În build căile din CSS (fonturi, imagini) devin relative la fișierul CSS din dist/
	base: command === 'build' ? './' : '/',
	publicDir: false,
	plugins: [wordpress()],
	server: {
		port: DEV_PORT,
		strictPort: true,
		origin: DEV_ORIGIN,
		cors: { origin: /^https?:\/\/barbarosi\.local(:\d+)?$/ },
		watch: {
			ignored: ['**/vendor/**', '**/dist/**'],
		},
	},
	build: {
		outDir: 'dist',
		emptyOutDir: true,
		manifest: true,
		rolldownOptions: {
			input: ['assets/js/main.js', 'assets/scss/main.scss'],
		},
	},
}));
