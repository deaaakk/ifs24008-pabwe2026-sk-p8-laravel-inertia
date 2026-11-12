import { useMemo } from 'react';
import { router, useForm } from '@inertiajs/react';
import Modal from './Modal';
import Button from './Button';
import MarkdownEditor from './MarkdownEditor';
import { Field, inputClass } from './Ui';
import { ImageIcon, TrashIcon } from './Icons';

/**
 * Modal Tambah / Ubah artikel (full screen).
 * - post = null  -> mode Tambah  (header biru)
 * - post = {...} -> mode Ubah    (header kuning saffron)
 * Komponen dimount hanya saat modal terbuka, sehingga state form selalu segar.
 */
export default function PostFormModal({ post, categories, onClose }) {
    const editing = Boolean(post);

    const form = useForm({
        title: post?.title ?? '',
        category: post?.category ?? 'Umum',
        excerpt: post?.excerpt ?? '',
        content: post?.content ?? '',
        is_published: post?.is_published ?? false,
        cover: null,
    });

    const preview = useMemo(
        () => (form.data.cover ? URL.createObjectURL(form.data.cover) : post?.cover_url ?? null),
        [form.data.cover, post?.cover_url],
    );

    const submit = (event) => {
        event.preventDefault();

        form.transform(({ cover, is_published, ...rest }) => ({
            ...rest,
            is_published: is_published ? 1 : 0,
            ...(cover ? { cover } : {}),
        }));

        const options = { preserveScroll: true, onSuccess: onClose };

        if (editing) {
            form.put(`/dashboard/posts/${post.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    const removeCover = () => router.delete(`/dashboard/posts/${post.id}/cover`, { preserveScroll: true, preserveState: true });

    return (
        <Modal
            open
            fullscreen
            tone={editing ? 'saffron' : 'primary'}
            title={editing ? 'Ubah Artikel' : 'Tambah Artikel'}
            subtitle={editing ? post.title : 'Tulis artikel baru dengan Markdown'}
            onClose={onClose}
            footer={
                <>
                    <Button variant="outline" onClick={onClose} disabled={form.processing}>
                        Batal
                    </Button>
                    <button
                        type="submit"
                        form="post-form"
                        disabled={form.processing}
                        className={`inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold text-white transition disabled:opacity-60 ${editing ? 'bg-ink hover:bg-black' : 'bg-primary hover:bg-primary-dark'}`}
                    >
                        {form.processing && <span className="spinner inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white" />}
                        {editing ? 'Simpan Perubahan' : 'Simpan'}
                    </button>
                </>
            }
        >
            <form id="post-form" onSubmit={submit} className="mx-auto grid max-w-5xl gap-6">
                <div className="grid gap-6 md:grid-cols-[1fr_16rem]">
                    <div className="grid gap-5">
                        <Field label="Judul" htmlFor="post-title" error={form.errors.title}>
                            <input
                                id="post-title"
                                className={`${inputClass} font-serif text-lg`}
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                placeholder="Judul yang membuat orang berhenti menggulir"
                                maxLength={255}
                                autoFocus
                            />
                        </Field>

                        <Field label="Kategori" htmlFor="post-category" error={form.errors.category}>
                            <select
                                id="post-category"
                                className={inputClass}
                                value={form.data.category}
                                onChange={(e) => form.setData('category', e.target.value)}
                            >
                                {categories.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Ringkasan" htmlFor="post-excerpt" error={form.errors.excerpt} hint="1–2 kalimat pemikat pembaca (maks. 500 karakter).">
                            <textarea
                                id="post-excerpt"
                                rows={3}
                                className={inputClass}
                                value={form.data.excerpt}
                                onChange={(e) => form.setData('excerpt', e.target.value)}
                                maxLength={500}
                            />
                        </Field>
                    </div>

                    <div className="grid content-start gap-4">
                        <Field label="Foto Cover" htmlFor="post-cover" error={form.errors.cover} hint="JPG, PNG, atau WEBP. Maks. 2 MB.">
                            <div className="overflow-hidden rounded-lg border border-line bg-paper">
                                {preview ? (
                                    <img src={preview} alt="Pratinjau cover" className="aspect-[4/3] w-full object-cover" />
                                ) : (
                                    <div className="flex aspect-[4/3] items-center justify-center text-muted">
                                        <ImageIcon size={32} />
                                    </div>
                                )}
                            </div>
                            <input
                                id="post-cover"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="mt-2 block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary"
                                onChange={(e) => form.setData('cover', e.target.files[0] ?? null)}
                            />
                            {editing && post.cover_image_path && !form.data.cover && (
                                <button
                                    type="button"
                                    onClick={removeCover}
                                    className="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-danger hover:underline"
                                >
                                    <TrashIcon size={14} /> Hapus cover saat ini
                                </button>
                            )}
                        </Field>

                        <label htmlFor="post-published" className="flex items-start gap-3 rounded-lg border border-line bg-paper p-3.5">
                            <input
                                id="post-published"
                                type="checkbox"
                                className="mt-0.5 h-4 w-4 accent-primary"
                                checked={form.data.is_published}
                                onChange={(e) => form.setData('is_published', e.target.checked)}
                            />
                            <span>
                                <span className="block text-sm font-semibold">Publikasikan</span>
                                <span className="block text-xs text-muted">Jika dimatikan, artikel tersimpan sebagai draf.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <Field label="Isi Artikel (Markdown)" htmlFor="post-content" error={form.errors.content}>
                    <MarkdownEditor value={form.data.content} onChange={(value) => form.setData('content', value)} />
                </Field>
            </form>
        </Modal>
    );
}
