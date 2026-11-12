import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';
import { buttonClass } from '../../Components/Button';
import { LoginIcon, UserPlusIcon } from '../../Components/Icons';
import { Avatar, CategoryBadge, Cover } from '../../Components/Ui';
import { formatDate } from '../../lib/format';

export default function PostShow({ post, readingTime }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={post.title}>
                {post.excerpt && <meta name="description" content={post.excerpt} />}
            </Head>

            <article className="mx-auto max-w-3xl px-4 pt-10 sm:px-6">
                <Link href="/articles" className="text-sm font-semibold text-primary hover:underline">
                    ‹ Semua artikel
                </Link>

                <header className="mt-5">
                    <CategoryBadge category={post.category} />
                    <h1 className="mt-3 font-serif text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl">{post.title}</h1>
                    {post.excerpt && <p className="mt-4 font-serif text-xl leading-relaxed text-muted">{post.excerpt}</p>}

                    <div className="mt-6 flex items-center gap-3 border-y border-line py-4 text-sm">
                        <Avatar user={post.user} size={40} />
                        <div>
                            <p className="font-semibold">{post.user?.name}</p>
                            <p className="text-muted">
                                <time dateTime={post.published_at}>{formatDate(post.published_at)}</time> · {readingTime} menit baca
                            </p>
                        </div>
                    </div>
                </header>

                {post.cover_url && (
                    <img src={post.cover_url} alt="" className="mt-8 aspect-[16/9] w-full rounded-xl object-cover" />
                )}
                {!post.cover_url && <Cover post={post} className="mt-8 aspect-[16/6] w-full rounded-xl" letterSize="text-8xl" />}

                <div className="mt-8">
                    <MarkdownViewer content={post.content} />
                </div>
            </article>

            <aside className="mx-auto mt-14 max-w-3xl px-4 sm:px-6" aria-label="Ajakan komunitas">
                <div className="rounded-2xl bg-ink p-7 text-white sm:p-9">
                    <h2 className="font-serif text-3xl font-bold leading-tight">Punya cerita sendiri untuk dibagikan?</h2>
                    <p className="mt-2 max-w-lg text-white/70">
                        Bergabunglah dengan komunitas penulis BlogVerse Pro. Terbitkan artikel, kelola draf, dan biarkan AI membantu
                        memulai tulisan pertamamu.
                    </p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        {auth.user ? (
                            <Link href={auth.user.role === 'admin' ? '/admin/users' : '/dashboard/posts'} className={buttonClass('saffron', 'lg')}>
                                Buka {auth.user.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis'}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className={buttonClass('saffron', 'lg')}>
                                    <LoginIcon size={18} /> Masuk
                                </Link>
                                <Link href="/register" className="inline-flex items-center justify-center gap-2 rounded-lg border border-white/30 px-5 py-2.5 font-semibold transition hover:bg-white/10">
                                    <UserPlusIcon size={18} /> Daftar Penulis
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </aside>
        </>
    );
}

PostShow.layout = (page) => <PublicLayout>{page}</PublicLayout>;
