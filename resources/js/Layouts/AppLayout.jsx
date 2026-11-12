import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Wordmark } from './PublicLayout';
import { Avatar } from '../Components/Ui';
import { Spinner, CloseIcon, DocIcon, GlobeIcon, LogoutIcon, MenuIcon, UserIcon, UsersIcon } from '../Components/Icons';

const MEMBER_NAV = [
    { href: '/dashboard/posts', label: 'Artikel Saya', icon: DocIcon },
    { href: '/profile', label: 'Profil', icon: UserIcon },
];

const ADMIN_NAV = [
    { href: '/admin/users', label: 'Pengguna', icon: UsersIcon },
    { href: '/admin/posts', label: 'Semua Artikel', icon: DocIcon },
    { href: '/profile', label: 'Profil', icon: UserIcon },
];

/** Layout area login (penulis & admin): sidebar + toast notifikasi. */
export default function AppLayout({ children }) {
    const { auth, flash } = usePage().props;
    const url = usePage().url;
    const user = auth.user;
    const nav = user.role === 'admin' ? ADMIN_NAV : MEMBER_NAV;

    const [menuOpen, setMenuOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const [toast, setToast] = useState(null);

    useEffect(() => setMenuOpen(false), [url]);

    useEffect(() => {
        if (!flash.success) return undefined;
        setToast(flash.success);
        const timer = setTimeout(() => setToast(null), 4000);
        return () => clearTimeout(timer);
    }, [flash.success]);

    const logout = () =>
        router.post('/logout', {}, { onStart: () => setLoggingOut(true), onFinish: () => setLoggingOut(false) });

    const isActive = (href) => url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);

    const sidebar = (
        <div className="flex h-full flex-col bg-ink text-white">
            <div className="flex h-16 items-center justify-between px-5">
                <Wordmark light />
                <button
                    type="button"
                    aria-label="Tutup menu"
                    className="rounded p-1 text-white/70 hover:text-white lg:hidden"
                    onClick={() => setMenuOpen(false)}
                >
                    <CloseIcon />
                </button>
            </div>

            <nav className="flex-1 space-y-1 px-3 py-4" aria-label="Dashboard">
                {nav.map(({ href, label, icon: Icon }) => (
                    <Link
                        key={href}
                        href={href}
                        className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${isActive(href) ? 'bg-white/10 text-saffron' : 'text-white/75 hover:bg-white/5 hover:text-white'}`}
                    >
                        <Icon size={18} />
                        {label}
                    </Link>
                ))}
                <Link
                    href="/"
                    className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/75 transition hover:bg-white/5 hover:text-white"
                >
                    <GlobeIcon size={18} />
                    Lihat Blog Publik
                </Link>
            </nav>

            <div className="border-t border-white/10 p-4">
                <div className="mb-3 flex items-center gap-3">
                    <Avatar user={user} size={36} />
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold">{user.name}</p>
                        <p className="truncate text-xs text-white/60">
                            @{user.username} · {user.role === 'admin' ? 'Admin' : 'Penulis'}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={logout}
                    disabled={loggingOut}
                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold transition hover:bg-white/20 disabled:opacity-60"
                >
                    {loggingOut ? <Spinner /> : <LogoutIcon size={16} />}
                    Keluar
                </button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen lg:pl-64">
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 lg:block">{sidebar}</aside>

            {menuOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="absolute inset-0 bg-ink/60" onClick={() => setMenuOpen(false)} />
                    <aside className="absolute inset-y-0 left-0 w-64">{sidebar}</aside>
                </div>
            )}

            <header className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-white px-4 lg:hidden">
                <button type="button" aria-label="Buka menu" onClick={() => setMenuOpen(true)} className="rounded p-1.5 hover:bg-paper">
                    <MenuIcon />
                </button>
                <Wordmark />
            </header>

            {toast && (
                <div
                    role="status"
                    className="toast-in fixed right-4 top-4 z-[90] flex max-w-sm items-start gap-3 rounded-lg bg-ink px-4 py-3 text-sm font-medium text-white shadow-xl"
                >
                    <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-saffron" />
                    <span>{toast}</span>
                    <button type="button" aria-label="Tutup notifikasi" onClick={() => setToast(null)} className="text-white/60 hover:text-white">
                        <CloseIcon size={16} />
                    </button>
                </div>
            )}

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-8">
                {children}
            </main>
        </div>
    );
}

export function PageHeader({ title, description, actions }) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 className="font-serif text-3xl font-bold leading-tight sm:text-4xl">{title}</h1>
                {description && <p className="mt-1 max-w-2xl text-muted">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
