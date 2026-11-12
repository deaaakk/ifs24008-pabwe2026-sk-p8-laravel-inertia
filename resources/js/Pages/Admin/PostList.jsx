import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout, { PageHeader } from '../../Layouts/AppLayout';
import { EmptyState, Pagination, SearchBox } from '../../Components/Ui';
import { DownloadIcon } from '../../Components/Icons';
import PostRows from '../../Components/PostRows';

export default function PostList({ posts, filters }) {
    const [q, setQ] = useState(filters.q);

    return (
        <>
            <Head title="Semua Artikel" />
            <PageHeader
                title="Semua Artikel"
                description="Monitoring seluruh artikel sistem (read-only)."
                actions={
                    <a href="/admin/posts/export" className="inline-flex items-center gap-2 rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        <DownloadIcon size={16} /> Ekspor ke Excel
                    </a>
                }
            />
            <div className="mb-4">
                <SearchBox value={q} onChange={setQ} onSubmit={() => router.get('/admin/posts', { q }, { preserveState: true, replace: true })} placeholder="Cari judul atau isi…" />
            </div>
            {posts.data.length === 0 ? <EmptyState title="Tidak ada artikel" /> : <PostRows posts={posts.data} />}
            <Pagination links={posts.links} />
        </>
    );
}

PostList.layout = (page) => <AppLayout>{page}</AppLayout>;
