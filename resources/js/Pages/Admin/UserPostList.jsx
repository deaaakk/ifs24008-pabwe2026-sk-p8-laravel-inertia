import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout, { PageHeader } from '../../Layouts/AppLayout';
import { Avatar, EmptyState, Pagination, SearchBox } from '../../Components/Ui';
import { DownloadIcon } from '../../Components/Icons';
import PostRows from '../../Components/PostRows';

export default function UserPostList({ author, posts, filters }) {
    const [q, setQ] = useState(filters.q);
    const base = `/admin/users/${author.id}/posts`;

    return (
        <>
            <Head title={`Artikel ${author.name}`} />
            <Link href="/admin/users" className="text-sm font-semibold text-primary hover:underline">‹ Semua pengguna</Link>

            <div className="mt-3">
                <PageHeader
                    title={`Artikel ${author.name}`}
                    description={`@${author.username} · tampilan read-only`}
                    actions={
                        <a href={`${base}/export`} className="inline-flex items-center gap-2 rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                            <DownloadIcon size={16} /> Ekspor ke Excel
                        </a>
                    }
                />
            </div>

            <div className="mb-4 flex items-center gap-3">
                <Avatar user={author} size={36} />
                <SearchBox value={q} onChange={setQ} onSubmit={() => router.get(base, { q }, { preserveState: true, replace: true })} placeholder="Cari artikel penulis ini…" />
            </div>

            {posts.data.length === 0 ? <EmptyState title="Penulis ini belum punya artikel" /> : <PostRows posts={posts.data} showAuthor={false} />}
            <Pagination links={posts.links} />
        </>
    );
}

UserPostList.layout = (page) => <AppLayout>{page}</AppLayout>;
