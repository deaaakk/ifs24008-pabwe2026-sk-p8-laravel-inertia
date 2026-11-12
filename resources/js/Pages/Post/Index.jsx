import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout, { PageHeader } from '../../Layouts/AppLayout';
import Button from '../../Components/Button';
import KebabMenu from '../../Components/KebabMenu';
import Modal from '../../Components/Modal';
import PostFormModal from '../../Components/PostFormModal';
import { CategoryBadge, Cover, EmptyState, Field, Pagination, SearchBox, StatCard, StatusBadge, inputClass } from '../../Components/Ui';
import { DownloadIcon, EyeIcon, PencilIcon, PlusIcon, SparklesIcon, SwapIcon, TrashIcon, UploadIcon } from '../../Components/Icons';
import { formatDate } from '../../lib/format';

function AiModal({ aiMax, onClose }) {
    const [drafts, setDrafts] = useState([]);
    const [selected, setSelected] = useState([]);

    const gen = useForm({ topic: '', count: 1 });
    const save = useForm({ posts: [] });

    const generate = (event) => {
        event.preventDefault();
        gen.post('/dashboard/posts/ai-generate', {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const result = page.props.flash.ai_drafts ?? [];
                setDrafts(result);
                setSelected(result.map((_, i) => i));
            },
        });
    };

    const saveSelected = () => {
        save.transform(() => ({ posts: drafts.filter((_, i) => selected.includes(i)) }));
        save.post('/dashboard/posts/ai-save', { preserveScroll: true, onSuccess: onClose });
    };

    const toggle = (i) => setSelected((cur) => (cur.includes(i) ? cur.filter((x) => x !== i) : [...cur, i]));

    return (
        <Modal
            open
            fullscreen={false}
            tone="ink"
            title="Generator Artikel AI"
            subtitle="Hasilkan draf artikel dengan OpenAI"
            onClose={onClose}
            footer={
                <>
                    <Button variant="outline" onClick={onClose}>Tutup</Button>
                    {drafts.length > 0 && (
                        <Button variant="ink" loading={save.processing} disabled={selected.length === 0} onClick={saveSelected}>
                            Simpan {selected.length} sebagai Draf
                        </Button>
                    )}
                </>
            }
        >
            <form onSubmit={generate} className="grid gap-4">
                <Field label="Topik" htmlFor="ai-topic" error={gen.errors.topic}>
                    <input id="ai-topic" className={inputClass} value={gen.data.topic} maxLength={200} placeholder="mis. Produktivitas bagi mahasiswa" onChange={(e) => gen.setData('topic', e.target.value)} autoFocus />
                </Field>
                <Field label="Jumlah artikel" htmlFor="ai-count" error={gen.errors.count} hint={`Antara 1 dan ${aiMax}.`}>
                    <input id="ai-count" type="number" min={1} max={aiMax} step={1} className={`${inputClass} w-28`} value={gen.data.count} onChange={(e) => gen.setData('count', e.target.value)} />
                </Field>
                <div>
                    <Button type="submit" variant="primary" icon={SparklesIcon} loading={gen.processing}>
                        {gen.processing ? 'Menghasilkan…' : 'Generate'}
                    </Button>
                </div>
            </form>

            {drafts.length > 0 && (
                <ul className="mt-6 divide-y divide-line rounded-lg border border-line">
                    {drafts.map((draft, i) => (
                        <li key={i}>
                            <label htmlFor={`draft-${i}`} className="flex items-start gap-3 p-3.5">
                                <input id={`draft-${i}`} type="checkbox" className="mt-1 h-4 w-4 accent-primary" checked={selected.includes(i)} onChange={() => toggle(i)} />
                                <span className="min-w-0">
                                    <span className="block font-serif text-lg font-semibold leading-snug">{draft.title}</span>
                                    <span className="mt-1 flex items-center gap-2"><CategoryBadge category={draft.category} /></span>
                                    <span className="mt-1.5 block text-sm text-muted">{draft.excerpt}</span>
                                </span>
                            </label>
                        </li>
                    ))}
                </ul>
            )}
            {save.errors.posts && <p className="mt-3 text-sm font-medium text-danger">{save.errors.posts}</p>}
        </Modal>
    );
}

function ImportModal({ onClose }) {
    const form = useForm({ file: null });

    const submit = (event) => {
        event.preventDefault();
        form.post('/dashboard/posts/import', { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Modal
            open
            fullscreen={false}
            tone="primary"
            title="Impor dari Excel"
            subtitle="Format .xlsx, .xls, atau .csv"
            onClose={onClose}
            footer={
                <>
                    <Button variant="outline" onClick={onClose} disabled={form.processing}>Batal</Button>
                    <button type="submit" form="import-form" disabled={!form.data.file || form.processing} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark disabled:opacity-60">
                        {form.processing && <span className="spinner inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white" />}
                        Impor
                    </button>
                </>
            }
        >
            <form id="import-form" onSubmit={submit} className="grid gap-4">
                <p className="text-sm text-muted">
                    Baris pertama harus berisi header: <code className="rounded bg-paper px-1">title</code>,{' '}
                    <code className="rounded bg-paper px-1">category</code>, <code className="rounded bg-paper px-1">excerpt</code>,{' '}
                    <code className="rounded bg-paper px-1">content</code>, <code className="rounded bg-paper px-1">is_published</code>{' '}
                    (1/0). Kolom <em>title</em> dan <em>content</em> wajib. Berkas hasil ekspor dapat diimpor kembali.
                </p>
                <Field label="Berkas" htmlFor="import-file" error={form.errors.file}>
                    <input id="import-file" type="file" accept=".xlsx,.xls,.csv" onChange={(e) => form.setData('file', e.target.files[0] ?? null)} className="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:font-semibold file:text-primary" />
                </Field>
            </form>
        </Modal>
    );
}

export default function Index({ posts, stats, filters, categories, aiMax }) {
    const [q, setQ] = useState(filters.q);
    const [modal, setModal] = useState(null); // 'create' | 'ai' | 'import'
    const [editingId, setEditingId] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [busyId, setBusyId] = useState(null);
    const [deletingNow, setDeletingNow] = useState(false);

    const editing = posts.data.find((p) => p.id === editingId);

    const visit = (params) =>
        router.get('/dashboard/posts', { q, status: filters.status, ...params }, { preserveState: true, replace: true });

    const toggle = (post) =>
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, {
            preserveScroll: true,
            onStart: () => setBusyId(post.id),
            onFinish: () => setBusyId(null),
        });

    const confirmDelete = () =>
        router.delete(`/dashboard/posts/${deleting.id}`, {
            preserveScroll: true,
            onStart: () => setDeletingNow(true),
            onFinish: () => {
                setDeletingNow(false);
                setDeleting(null);
            },
        });

    return (
        <>
            <Head title="Artikel Saya" />
            <PageHeader
                title="Artikel Saya"
                description="Kelola tulisanmu: tulis, terbitkan, impor, dan ekspor."
                actions={
                    <>
                        <Button icon={PlusIcon} onClick={() => setModal('create')}>Tambah Artikel</Button>
                        <Button variant="ink" icon={SparklesIcon} onClick={() => setModal('ai')}>Generate AI</Button>
                        <Button variant="outline" icon={UploadIcon} onClick={() => setModal('import')}>Impor</Button>
                        <a href="/dashboard/posts/export" className="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold transition hover:border-ink/40 hover:bg-paper">
                            <DownloadIcon size={16} /> Ekspor
                        </a>
                    </>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Total Artikel" value={stats.total} hint="Semua tulisanmu" active={!filters.status} onClick={() => visit({ status: '' })} />
                <StatCard label="Total Selesai" value={stats.published} hint="Sudah dipublikasikan" tone="success" active={filters.status === 'published'} onClick={() => visit({ status: 'published' })} />
                <StatCard label="Belum Selesai" value={stats.draft} hint="Masih berstatus draf" tone="saffron" active={filters.status === 'draft'} onClick={() => visit({ status: 'draft' })} />
            </div>

            <div className="mb-4">
                <SearchBox value={q} onChange={setQ} onSubmit={() => visit({ q })} placeholder="Cari artikelmu…" />
            </div>

            {posts.data.length === 0 ? (
                <EmptyState title="Belum ada artikel di sini">
                    Klik <strong>Tambah Artikel</strong> atau minta AI menyiapkan draf pertamamu.
                </EmptyState>
            ) : (
                <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                    {posts.data.map((post) => (
                        <li key={post.id} className="flex items-center gap-4 p-3.5 sm:p-4">
                            <Cover post={post} className="h-14 w-20 shrink-0 rounded-md" letterSize="text-2xl" />
                            <div className="min-w-0 flex-1">
                                <Link href={`/dashboard/posts/${post.id}`} className="block truncate font-serif text-lg font-semibold leading-snug hover:text-primary">
                                    {post.title}
                                </Link>
                                <div className="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted">
                                    <CategoryBadge category={post.category} />
                                    <StatusBadge published={post.is_published} />
                                    <span>Diubah {formatDate(post.updated_at)}</span>
                                </div>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                icon={SwapIcon}
                                loading={busyId === post.id}
                                onClick={() => toggle(post)}
                                className="hidden sm:inline-flex"
                            >
                                {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                            </Button>
                            <KebabMenu
                                items={[
                                    { label: 'Detail', icon: EyeIcon, href: `/dashboard/posts/${post.id}` },
                                    { label: 'Ubah', icon: PencilIcon, onClick: () => setEditingId(post.id) },
                                    { label: 'Hapus', icon: TrashIcon, tone: 'danger', onClick: () => setDeleting(post) },
                                    { label: post.is_published ? 'Jadikan Draf' : 'Publikasikan', icon: SwapIcon, onClick: () => toggle(post) },
                                ]}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <Pagination links={posts.links} />

            {modal === 'create' && <PostFormModal categories={categories} onClose={() => setModal(null)} />}
            {editing && <PostFormModal key={editing.id} post={editing} categories={categories} onClose={() => setEditingId(null)} />}
            {modal === 'ai' && <AiModal aiMax={aiMax} onClose={() => setModal(null)} />}
            {modal === 'import' && <ImportModal onClose={() => setModal(null)} />}

            <Modal
                open={Boolean(deleting)}
                fullscreen={false}
                tone="danger"
                title="Hapus artikel?"
                onClose={() => setDeleting(null)}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setDeleting(null)} disabled={deletingNow}>Batal</Button>
                        <Button variant="danger" icon={TrashIcon} loading={deletingNow} onClick={confirmDelete}>Hapus</Button>
                    </>
                }
            >
                <p>
                    Artikel <strong>{deleting?.title}</strong> beserta foto cover-nya akan dihapus permanen dan tidak dapat dikembalikan.
                </p>
            </Modal>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
