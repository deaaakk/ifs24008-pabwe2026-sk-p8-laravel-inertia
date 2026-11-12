import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Spinner } from './Icons';

/**
 * Top progress bar + floating pill "Memproses tindakan..." selama
 * navigasi atau request Inertia berlangsung.
 */
export default function ProgressIndicator() {
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setBusy(true));
        const offFinish = router.on('finish', () => setBusy(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    return (
        <>
            <div
                aria-hidden="true"
                className={`pointer-events-none fixed inset-x-0 top-0 z-[100] h-1 overflow-hidden transition-opacity duration-200 ${busy ? 'opacity-100' : 'opacity-0'}`}
            >
                <div className="progress-bar h-full w-1/4 bg-saffron" />
            </div>

            <div
                role="status"
                aria-live="polite"
                className={`pointer-events-none fixed bottom-5 left-1/2 z-[100] flex -translate-x-1/2 items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-white shadow-lg transition-all duration-200 ${busy ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'}`}
            >
                <Spinner size={14} />
                Memproses tindakan...
            </div>
        </>
    );
}
