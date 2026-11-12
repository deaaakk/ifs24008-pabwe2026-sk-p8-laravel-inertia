import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { DotsIcon } from './Icons';

/**
 * Menu tindakan baris (⋮).
 * items: [{ label, icon, onClick?, href?, tone: 'primary' | 'danger' }]
 */
export default function KebabMenu({ items, label = 'Menu tindakan' }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return undefined;
        const onDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        const onKey = (event) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                aria-label={label}
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="rounded-md p-2 text-muted transition hover:bg-line/60 hover:text-ink"
            >
                <DotsIcon />
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 z-30 mt-1 w-44 overflow-hidden rounded-lg border border-line bg-white py-1 shadow-xl"
                >
                    {items.map(({ label: text, icon: Icon, onClick, href, tone = 'primary' }) => {
                        const color = tone === 'danger' ? 'text-danger hover:bg-danger-soft' : 'text-primary hover:bg-primary-soft';
                        const cls = `flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-sm font-medium ${color}`;
                        const content = (
                            <>
                                <Icon size={16} />
                                {text}
                            </>
                        );

                        return href ? (
                            <Link key={text} href={href} role="menuitem" className={cls}>
                                {content}
                            </Link>
                        ) : (
                            <button
                                key={text}
                                type="button"
                                role="menuitem"
                                className={cls}
                                onClick={() => {
                                    setOpen(false);
                                    onClick?.();
                                }}
                            >
                                {content}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
