import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { buttonClass } from '../../Components/Button';
import { LoginIcon, UserPlusIcon } from '../../Components/Icons';
import { Avatar, CategoryBadge, Cover, EmptyState, Pagination, SearchBox } from '../../Components/Ui';
import { formatDate } from '../../lib/format';

function Hero({ categories }) {
    return (
        <section className="border-b border-line bg-white">
            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_1fr] md:py-20">
                <div>
                    <h1 className="font-serif text-4xl font-bold leading-[1.08] tracking-tight sm:text-6xl">
                        Tulisan yang layak dibaca sampai kalimat terakhir.
                    </h1>
                    <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted">
                        BlogVerse Pro mempertemukan penulis dan pembaca Indonesia: baca bebas tanpa akun, atau daftar untuk
                        menerbitkan gagasanmu sendiri — lengkap dengan editor Markdown dan bantuan AI.
                    </p>
                    <div className="mt-7 flex flex-wrap gap-3">
                        <Link href="/login" className={buttonClass('primary', 'lg')}>
                            <LoginIcon size={18} /> Masuk
                        </Link>
                        <Link href="/register" className={buttonClass('saffron', 'lg')}>
                            <UserPlusIcon size={18} /> Daftar Penulis
                        </Link>
                    </div>
                </div>

                <nav aria-label="Rubrik" className="self-end">
                    <p className="mb-3 font-serif text-lg font-semibold">Jelajahi rubrik</p>
                    <ul className="divide-y divide-line border-y border-line">
                        {categories.map((c) => (
                            <li key={c.value}>
                                <Link
                                    href={`/articles?category=${encodeURIComponent(c.value)}`}
                                    className="flex items-baseline justify-between gap-3 py-2.5 transition hover:text-primary"
                                >
                                    <span className="font-semibold">{c.value}</span>
                                    <span className="text-right text-sm text-muted">{c.label}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
            </div>
        </section>
    );
}

function ArticleCard({ post, featured }) {
    return (
        <article className={`group flex flex-col overflow-hidden rounded-xl border border-line bg-white transition hover:shadow-lg ${featured ? 'md:col-span-2 md:flex-row' : ''}`}>
            <Link href={`/articles/${post.slug}`} className={`block overflow-hidden ${featured ? 'md:w-1/2' : ''}`} tabIndex={-1} aria-hidden="true">
                <Cover post={post} className={`w-full transition duration-500 group-hover:scale-[1.03] ${featured ? 'aspect-[16/10] md:h-full' : 'aspect-[16/10]'}`} letterSize={featured ? 'text-8xl' : 'text-6xl'} />
            </Link>
            <div className={`flex flex-1 flex-col p-5 ${featured ? 'md:justify-center md:p-8' : ''}`}>
                <CategoryBadge category={post.category} />
                <h2 className={`mt-3 font-serif font-bold leading-snug ${featured ? 'text-3xl' : 'text-xl'}`}>
                    <Link href={`/articles/${post.slug}`} className="hover:text-primary">
                        {post.title}
                    </Link>
                </h2>
                {post.excerpt && <p className={`mt-2 text-muted ${featured ? 'text-base' : 'line-clamp-3 text-sm'}`}>{post.excerpt}</p>}
                <div className="mt-auto flex items-center gap-2.5 pt-5 text-sm text-muted">
                    <Avatar user={post.user} size={26} />
                    <span className="font-medium text-ink">{post.user?.name}</span>
                    <span aria-hidden="true">·</span>
                    <time dateTime={post.published_at}>{formatDate(post.published_at)}</time>
                </div>
            </div>
        </article>
    );
}

export default function PostIndex({ mode, posts, filters, categories }) {
    const [q, setQ] = useState(filters.q);
    const isHome = mode === 'home';
    const filtered = Boolean(filters.q || filters.category);
    const featuredFirst = posts.current_page === 1 && !filtered && posts.data.length > 2;

    const visit = (params) =>
        router.get('/articles', { q, category: filters.category, ...params }, { preserveState: true, replace: true });

    return (
        <>
            <Head title={isHome ? 'Beranda' : 'Artikel'} />
            {isHome && <Hero categories={categories} />}

            <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <h2 className="font-serif text-3xl font-bold">{isHome ? 'Terbaru' : 'Semua artikel'}</h2>
                    <SearchBox value={q} onChange={setQ} onSubmit={() => visit({ q })} placeholder="Cari judul atau isi artikel…" />
                </div>

                <div className="mb-8 flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                    {[{ value: '', label: 'Semua' }, ...categories].map((c) => {
                        const active = filters.category === c.value;
                        return (
                            <button
                                key={c.value || 'all'}
                                type="button"
                                aria-pressed={active}
                                onClick={() => visit({ category: c.value })}
                                className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition ${active ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-line hover:ring-ink/40'}`}
                            >
                                {c.value || 'Semua'}
                            </button>
                        );
                    })}
                </div>

                {posts.data.length === 0 ? (
                    <EmptyState title="Belum ada artikel yang cocok">
                        {filtered ? 'Coba kata kunci atau kategori lain.' : 'Penulis kami sedang menyiapkan tulisan pertama.'}
                    </EmptyState>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post, index) => (
                            <ArticleCard key={post.id} post={post} featured={featuredFirst && index === 0} />
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} />
            </section>
        </>
    );
}

PostIndex.layout = (page) => <PublicLayout>{page}</PublicLayout>;
