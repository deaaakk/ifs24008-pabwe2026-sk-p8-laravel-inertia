import { Link } from '@inertiajs/react';
import { hashString, initials } from '../lib/format';

export function CategoryBadge({ category }) {
    return (
        <span className="inline-block rounded bg-saffron-soft px-2 py-0.5 text-xs font-semibold text-[#7a5b00]">
            {category}
        </span>
    );
}

export function StatusBadge({ published }) {
    return published ? (
        <span className="inline-flex items-center gap-1 rounded bg-success-soft px-2 py-0.5 text-xs font-semibold text-success">
            <span className="h-1.5 w-1.5 rounded-full bg-success" /> Terbit
        </span>
    ) : (
        <span className="inline-flex items-center gap-1 rounded bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">
            <span className="h-1.5 w-1.5 rounded-full bg-slate-500" /> Draf
        </span>
    );
}

const FALLBACKS = [
    ['#2547d0', '#0e1a2b'],
    ['#0e1a2b', '#2547d0'],
    ['#f2b705', '#c9561a'],
    ['#1e7b4f', '#0e1a2b'],
    ['#6b3fa0', '#2547d0'],
];

/** Cover artikel; bila tidak ada gambar, tampilkan blok warna dengan inisial judul. */
export function Cover({ post, className = '', letterSize = 'text-5xl' }) {
    if (post.cover_url) {
        return <img src={post.cover_url} alt="" loading="lazy" className={`object-cover ${className}`} />;
    }

    const [from, to] = FALLBACKS[hashString(post.title) % FALLBACKS.length];

    return (
        <div
            aria-hidden="true"
            className={`flex items-center justify-center font-serif font-bold text-white/90 ${letterSize} ${className}`}
            style={{ background: `linear-gradient(135deg, ${from}, ${to})` }}
        >
            {post.title?.[0]?.toUpperCase()}
        </div>
    );
}

export function Avatar({ user, size = 32 }) {
    if (user?.photo_url) {
        return (
            <img
                src={user.photo_url}
                alt=""
                className="rounded-full object-cover"
                style={{ width: size, height: size }}
            />
        );
    }
    return (
        <span
            aria-hidden="true"
            className="inline-flex shrink-0 items-center justify-center rounded-full bg-primary-soft font-semibold text-primary"
            style={{ width: size, height: size, fontSize: size * 0.38 }}
        >
            {initials(user?.name)}
        </span>
    );
}

export function StatCard({ label, value, hint, active = false, onClick, tone = 'ink' }) {
    const accents = { ink: 'bg-ink', success: 'bg-success', saffron: 'bg-saffron' };
    const Tag = onClick ? 'button' : 'div';

    return (
        <Tag
            type={onClick ? 'button' : undefined}
            onClick={onClick}
            aria-pressed={onClick ? active : undefined}
            className={`relative overflow-hidden rounded-xl border bg-white p-5 text-left transition ${onClick ? 'hover:-translate-y-0.5 hover:shadow-md' : ''} ${active ? 'border-primary ring-2 ring-primary/20' : 'border-line'}`}
        >
            <span className={`absolute inset-y-0 left-0 w-1.5 ${accents[tone]}`} />
            <p className="text-sm font-medium text-muted">{label}</p>
            <p className="mt-1 font-serif text-4xl font-bold leading-none">{value}</p>
            {hint && <p className="mt-2 text-xs text-muted">{hint}</p>}
        </Tag>
    );
}

export function Pagination({ links = [] }) {
    if (links.length <= 3) return null;

    const label = (text) => {
        if (text.includes('Previous')) return 'Sebelumnya';
        if (text.includes('Next')) return 'Berikutnya';
        return text;
    };

    return (
        <nav className="mt-8 flex flex-wrap items-center justify-center gap-1.5" aria-label="Halaman">
            {links.map((link) =>
                link.url ? (
                    <Link
                        key={link.label}
                        href={link.url}
                        preserveScroll
                        className={`rounded-md px-3 py-1.5 text-sm font-medium ${link.active ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-line hover:ring-ink/40'}`}
                    >
                        {label(link.label)}
                    </Link>
                ) : (
                    <span key={link.label} className="rounded-md px-3 py-1.5 text-sm text-muted/60">
                        {label(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}

export function EmptyState({ title, children }) {
    return (
        <div className="rounded-xl border border-dashed border-line bg-white px-6 py-14 text-center">
            <p className="font-serif text-xl font-semibold">{title}</p>
            {children && <div className="mt-2 text-sm text-muted">{children}</div>}
        </div>
    );
}

export function Field({ label, htmlFor, error, hint, children }) {
    return (
        <div>
            <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-semibold">
                {label}
            </label>
            {children}
            {hint && !error && <p className="mt-1 text-xs text-muted">{hint}</p>}
            {error && <p className="mt-1 text-xs font-medium text-danger">{error}</p>}
        </div>
    );
}

export const inputClass =
    'w-full rounded-lg border border-line bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-muted/60 focus:border-primary focus:ring-2 focus:ring-primary/20';

export function SearchBox({ value, onChange, onSubmit, placeholder = 'Cari…' }) {
    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit();
            }}
            className="w-full sm:max-w-xs"
            role="search"
        >
            <input
                type="search"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className={inputClass}
            />
        </form>
    );
}
