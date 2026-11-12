import { useEffect } from 'react';
import { CloseIcon } from './Icons';

const TONES = {
    primary: { header: 'bg-primary text-white', footer: 'bg-primary-soft border-primary/20' },
    saffron: { header: 'bg-saffron text-ink', footer: 'bg-saffron-soft border-saffron/40' },
    ink: { header: 'bg-ink text-white', footer: 'bg-slate-200 border-slate-300' },
    danger: { header: 'bg-danger text-white', footer: 'bg-danger-soft border-danger/20' },
};

/**
 * Modal dialog. fullscreen=true (default) dipakai oleh modal Tambah & Ubah.
 * Header & footer berwarna kontras per `tone`; tombol aksi rata kanan di footer.
 */
export default function Modal({
    open,
    title,
    subtitle,
    onClose,
    children,
    footer,
    tone = 'primary',
    fullscreen = true,
}) {
    useEffect(() => {
        if (!open) return undefined;
        const onKey = (event) => event.key === 'Escape' && onClose?.();
        document.addEventListener('keydown', onKey);
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = previous;
        };
    }, [open, onClose]);

    if (!open) return null;

    const colors = TONES[tone] ?? TONES.primary;

    return (
        <div
            className={`fixed inset-0 z-50 ${fullscreen ? '' : 'flex items-center justify-center bg-ink/60 p-4'}`}
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div
                className={`flex flex-col bg-white ${fullscreen ? 'h-full w-full' : 'max-h-full w-full max-w-lg overflow-hidden rounded-xl shadow-2xl'}`}
            >
                <header className={`flex items-start justify-between gap-4 px-5 py-4 sm:px-8 ${colors.header}`}>
                    <div>
                        <h2 className="font-serif text-2xl font-semibold leading-tight">{title}</h2>
                        {subtitle && <p className="mt-0.5 text-sm opacity-80">{subtitle}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup"
                        className="rounded-md p-1.5 opacity-80 transition hover:bg-black/10 hover:opacity-100"
                    >
                        <CloseIcon size={22} />
                    </button>
                </header>

                <div className="flex-1 overflow-y-auto px-5 py-6 sm:px-8">{children}</div>

                {footer && (
                    <footer className={`flex flex-wrap items-center justify-end gap-3 border-t px-5 py-3.5 sm:px-8 ${colors.footer}`}>
                        {footer}
                    </footer>
                )}
            </div>
        </div>
    );
}
