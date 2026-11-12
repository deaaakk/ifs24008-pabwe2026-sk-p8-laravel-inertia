import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import ProgressIndicator from './Components/ProgressIndicator';

// Halaman dimuat per-rute (code-splitting), bukan digabung ke satu berkas.
const pages = import.meta.glob('./Pages/**/*.jsx');

// Jaring pengaman aksesibilitas: setiap halaman wajib punya satu <h1>.
// Jika sebuah halaman belum punya, dipasang <h1> tersembunyi dari judul halaman.
function ensureHeadingOne() {
    const marker = 'data-fallback-h1';
    const real = document.querySelector(`h1:not([${marker}])`);
    const fallback = document.querySelector(`h1[${marker}]`);

    if (real) {
        fallback?.remove();
        return;
    }

    const text = document.title || 'BlogVerse Pro';

    if (fallback) {
        fallback.textContent = text;
        return;
    }

    const h1 = document.createElement('h1');
    h1.setAttribute(marker, '');
    h1.textContent = text;
    Object.assign(h1.style, {
        position: 'absolute',
        width: '1px',
        height: '1px',
        margin: '-1px',
        padding: '0',
        border: '0',
        overflow: 'hidden',
        clip: 'rect(0 0 0 0)',
        whiteSpace: 'nowrap',
    });

    (document.querySelector('main') ?? document.getElementById('app'))?.prepend(h1);
}

createInertiaApp({
    title: (title) => (title ? `${title} · BlogVerse Pro` : 'BlogVerse Pro'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }

        return page();
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <ProgressIndicator />
                <App {...props} />
            </>,
        );

        router.on('navigate', () => setTimeout(ensureHeadingOne, 150));
        setTimeout(ensureHeadingOne, 300);
    },
    // Progress bar bawaan dimatikan; diganti ProgressIndicator kustom.
    progress: false,
});