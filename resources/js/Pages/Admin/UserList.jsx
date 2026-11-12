import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout, { PageHeader } from '../../Layouts/AppLayout';
import Button from '../../Components/Button';
import { Avatar, EmptyState, Pagination, SearchBox, StatCard } from '../../Components/Ui';
import { DocIcon, DownloadIcon } from '../../Components/Icons';

export default function UserList({ users, summary, filters }) {
    const [q, setQ] = useState(filters.q);
    const search = () => router.get('/admin/users', { q }, { preserveState: true, replace: true });

    return (
        <>
            <Head title="Pengguna" />
            <PageHeader
                title="Pengguna"
                description="Pantau penulis dan jumlah artikel mereka."
                actions={
                    <a href="/admin/posts/export" className="inline-flex items-center gap-2 rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        <DownloadIcon size={16} /> Ekspor semua artikel
                    </a>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Pengguna" value={summary.users} />
                <StatCard label="Admin" value={summary.admins} tone="saffron" />
                <StatCard label="Total Artikel" value={summary.posts} />
                <StatCard label="Dipublikasikan" value={summary.published} tone="success" />
            </div>

            <div className="mb-4">
                <SearchBox value={q} onChange={setQ} onSubmit={search} placeholder="Cari nama atau username…" />
            </div>

            {users.data.length === 0 ? (
                <EmptyState title="Pengguna tidak ditemukan" />
            ) : (
                <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                    {users.data.map((user) => (
                        <li key={user.id} className="flex flex-wrap items-center gap-4 p-4">
                            <Avatar user={user} size={40} />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-semibold">{user.name}</p>
                                <p className="text-sm text-muted">
                                    @{user.username} ·{' '}
                                    <span className={user.role === 'admin' ? 'font-semibold text-primary' : ''}>
                                        {user.role === 'admin' ? 'Admin' : 'Penulis'}
                                    </span>
                                </p>
                            </div>
                            <p className="text-sm text-muted">
                                <strong className="text-ink">{user.posts_count}</strong> artikel ·{' '}
                                <strong className="text-success">{user.published_posts_count}</strong> terbit
                            </p>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" icon={DocIcon} href={`/admin/users/${user.id}/posts`}>Artikel</Button>
                                <a href={`/admin/users/${user.id}/posts/export`} className="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-sm font-semibold hover:bg-paper" aria-label={`Ekspor artikel ${user.name}`}>
                                    <DownloadIcon size={15} /> Excel
                                </a>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <Pagination links={users.links} />
        </>
    );
}

UserList.layout = (page) => <AppLayout>{page}</AppLayout>;
