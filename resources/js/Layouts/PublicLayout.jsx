import { Link, usePage } from '@inertiajs/react';
import { Avatar } from '../Components/Ui';
import { buttonClass } from '../Components/Button';
import { LoginIcon, UserPlusIcon } from '../Components/Icons';

export function Wordmark({ light = false }) {
    return (
        <Link href="/" className="flex items-baseline gap-1.5">
            <span className={`font-serif text-2xl font-bold tracking-tight ${light ? 'text-white' : 'text-ink'}`}>BlogVerse</span>
            <span className="rounded bg-saffron px-1.5 py-px text-[11px] font-bold text-ink">Pro</span>
        </Link>
    );
}

/** Layout halaman publik: Sticky Top Navbar, tanpa sidebar, footer editorial. */
export default function PublicLayout({ children }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const url = usePage().url;
    const active = (path) => (path === '/' ? url === '/' : url.startsWith(path));

    const navLink = (href, label) => (
        <Link
            href={href}
            className={`rounded-md px-3 py-1.5 text-sm font-medium transition hover:bg-paper ${active(href) ? 'text-primary' : 'text-ink/80'}`}
        >
            {label}
        </Link>
    );

    return (
        <div className="flex min-h-screen flex-col">
            <header className="sticky top-0 z-40 border-b border-line bg-white/90 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <div className="flex items-center gap-6">
                        <Wordmark />
                        <nav className="hidden items-center gap-1 sm:flex" aria-label="Utama">
                            {navLink('/', 'Beranda')}
                            {navLink('/articles', 'Artikel')}
                        </nav>
                    </div>

                    <div className="flex items-center gap-2">
                        {user ? (
                            <Link
                                href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                className="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm font-semibold ring-1 ring-line transition hover:ring-ink/40"
                            >
                                <Avatar user={user} size={28} />
                                {user.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis'}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className={buttonClass('outline', 'sm')}>
                                    <LoginIcon size={16} /> Masuk
                                </Link>
                                <Link href="/register" className={buttonClass('primary', 'sm')}>
                                    <UserPlusIcon size={16} /> Daftar
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {flash.success && (
                <div role="status" className="bg-success-soft px-4 py-2 text-center text-sm font-medium text-success">
                    {flash.success}
                </div>
            )}

            <main className="flex-1">{children}</main>

            <footer className="mt-16 bg-ink text-white">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-[1.4fr_1fr] sm:px-6">
                    <div>
                        <Wordmark light />
                        <p className="mt-3 max-w-sm font-serif text-lg leading-snug text-white/70">
                            Ruang menulis dan membaca untuk gagasan yang butuh lebih dari seratus empat puluh karakter.
                        </p>
                    </div>
                    <nav aria-label="Footer" className="flex flex-col gap-2 text-sm">
                        <Link href="/" className="text-white/80 hover:text-saffron">Beranda Publik</Link>
                        <Link href="/articles" className="text-white/80 hover:text-saffron">Semua Artikel</Link>
                        <Link href="/login" className="text-white/80 hover:text-saffron">Halaman Login</Link>
                        <Link href="/register" className="text-white/80 hover:text-saffron">Halaman Register</Link>
                    </nav>
                </div>
                <div className="border-t border-white/10 py-4 text-center text-xs text-white/50">
                    © {new Date().getFullYear()} BlogVerse Pro
                </div>
            </footer>
        </div>
    );
}
