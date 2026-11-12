import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Button from '../../Components/Button';
import Modal from '../../Components/Modal';
import PostFormModal from '../../Components/PostFormModal';
import MarkdownViewer from '../../Components/MarkdownViewer';
import { CategoryBadge, Cover, StatusBadge } from '../../Components/Ui';
import { EyeIcon, ImageIcon, PencilIcon, SwapIcon, TrashIcon } from '../../Components/Icons';
import { formatDate } from '../../lib/format';

export default function Show({ post, readingTime, categories }) {
    const [editing, setEditing] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [busy, setBusy] = useState(null); // 'toggle' | 'cover' | 'delete'

    const run = (key, method, url, data = {}, options = {}) =>
        router[method](url, data, {
            preserveScroll: true,
            ...options,
            onStart: () => setBusy(key),
            onFinish: () => setBusy(null),
        });

    return (
        <>
            <Head title={post.title} />

            <Link href="/dashboard/posts" className="text-sm font-semibold text-primary hover:underline">
                ‹ Kembali ke daftar
            </Link>

            <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 max-w-3xl">
                    <div className="flex flex-wrap items-center gap-2">
                        <CategoryBadge category={post.category} />
                        <StatusBadge published={post.is_published} />
                    </div>
                    <h1 className="mt-3 font-serif text-4xl font-bold leading-tight">{post.title}</h1>
                    {post.excerpt && <p className="mt-2 font-serif text-lg text-muted">{post.excerpt}</p>}
                    <p className="mt-3 text-sm text-muted">
                        Dibuat {formatDate(post.created_at)} · Diterbitkan {formatDate(post.published_at)} · {readingTime} menit baca
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button icon={PencilIcon} onClick={() => setEditing(true)}>Ubah</Button>
                    <Button variant="outline" icon={SwapIcon} loading={busy === 'toggle'} onClick={() => run('toggle', 'patch', `/dashboard/posts/${post.id}/publish`)}>
                        {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                    </Button>
                    {post.is_published && (
                        <Button variant="outline" icon={EyeIcon} href={`/articles/${post.slug}`}>Lihat publik</Button>
                    )}
                    <Button variant="danger" icon={TrashIcon} onClick={() => setConfirming(true)}>Hapus</Button>
                </div>
            </div>

            <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_18rem]">
                <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                    <MarkdownViewer content={post.content} />
                </article>

                <aside className="space-y-3">
                    <h2 className="font-serif text-xl font-semibold">Foto Cover</h2>
                    <Cover post={post} className="aspect-[4/3] w-full rounded-lg" />
                    <label htmlFor="cover-upload" className="flex items-center justify-center gap-2 rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold transition hover:bg-paper">
                        <ImageIcon size={16} /> {busy === 'cover' ? 'Mengunggah…' : post.cover_url ? 'Ganti cover' : 'Unggah cover'}
                    </label>
                    <input
                        id="cover-upload"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="sr-only"
                        onChange={(e) => {
                            const file = e.target.files[0];
                            if (file) run('cover', 'post', `/dashboard/posts/${post.id}/cover`, { cover: file }, { forceFormData: true });
                            e.target.value = '';
                        }}
                    />
                    {post.cover_url && (
                        <Button variant="ghost" className="w-full !text-danger hover:!bg-danger-soft" icon={TrashIcon} onClick={() => run('cover', 'delete', `/dashboard/posts/${post.id}/cover`)}>
                            Hapus cover
                        </Button>
                    )}
                </aside>
            </div>

            {editing && <PostFormModal key={post.updated_at} post={post} categories={categories} onClose={() => setEditing(false)} />}

            <Modal
                open={confirming}
                fullscreen={false}
                tone="danger"
                title="Hapus artikel?"
                onClose={() => setConfirming(false)}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setConfirming(false)} disabled={busy === 'delete'}>Batal</Button>
                        <Button variant="danger" icon={TrashIcon} loading={busy === 'delete'} onClick={() => run('delete', 'delete', `/dashboard/posts/${post.id}`)}>Hapus</Button>
                    </>
                }
            >
                <p>Artikel <strong>{post.title}</strong> akan dihapus permanen.</p>
            </Modal>
        </>
    );
}

Show.layout = (page) => <AppLayout>{page}</AppLayout>;
