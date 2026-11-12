import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import ProgressIndicator from './Components/ProgressIndicator';

createInertiaApp({
    title: (title) => (title ? `${title} · BlogVerse Pro` : 'BlogVerse Pro'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <ProgressIndicator />
                <App {...props} />
            </>,
        );
    },
    // Progress bar bawaan dimatikan; diganti ProgressIndicator kustom.
    progress: false,
});
