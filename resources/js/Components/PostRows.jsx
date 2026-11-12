import { Link } from '@inertiajs/react';
import { CategoryBadge, Cover, StatusBadge } from './Ui';
import { formatDate } from '../lib/format';

/** Daftar artikel read-only (monitoring) untuk halaman admin. */
export default function PostRows({ posts, showAuthor = true }) {
    return (
        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
            {posts.map((post) => (
                <li key={post.id} className="flex items-center gap-4 p-3.5 sm:p-4">
                    <Cover post={post} className="h-14 w-20 shrink-0 rounded-md" letterSize="text-2xl" />
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-serif text-lg font-semibold leading-snug">{post.title}</p>
                        <div className="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted">
                            <CategoryBadge category={post.category} />
                            <StatusBadge published={post.is_published} />
                            {showAuthor && <span>oleh {post.user?.name}</span>}
                            <span>Dibuat {formatDate(post.created_at)}</span>
                        </div>
                    </div>
                    {post.is_published && (
                        <Link href={`/articles/${post.slug}`} className="text-sm font-semibold text-primary hover:underline">
                            Baca
                        </Link>
                    )}
                </li>
            ))}
        </ul>
    );
}
