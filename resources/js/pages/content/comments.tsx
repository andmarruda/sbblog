import { Form, Link, router } from '@inertiajs/react';
import {
    Check,
    MessageSquareReply,
    RotateCcw,
    ShieldAlert,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/app/empty-state';
import { PageHeader } from '@/components/app/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/articles';
import {
    bulk,
    destroy,
    index,
    moderate,
    reply,
    restore,
} from '@/routes/comments';
import type { Paginated } from '@/types';

type Comment = {
    id: string;
    author_name: string;
    body: string;
    status: 'pending' | 'approved' | 'rejected' | 'spam';
    article: { id: string; title: string };
    reply_to: string | null;
    created_at: string | null;
    deleted_at: string | null;
};

type Props = {
    comments: Paginated<Comment>;
    query: { status: string; search?: string };
    counts: Record<string, number>;
};

function ReplyForm({
    comment,
    onDone,
}: {
    comment: Comment;
    onDone: () => void;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();

    return (
        <Form
            action={reply({ ...args, comment: comment.id })}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            resetOnSuccess
            className="mt-3 grid gap-2"
        >
            {({ processing, errors }) => (
                <>
                    <Textarea
                        name="body"
                        rows={3}
                        placeholder={t('Reply as the blog…')}
                        aria-label={t('Reply')}
                        aria-invalid={errors.body ? true : undefined}
                        required
                    />
                    <div className="flex gap-2">
                        <Button type="submit" size="sm" disabled={processing}>
                            {t('Publish reply')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={onDone}
                        >
                            {t('Cancel')}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

export default function Comments({ comments, query, counts }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const args = useBlogRoute();
    const [selected, setSelected] = useState<string[]>([]);
    const [replying, setReplying] = useState<string | null>(null);
    const [search, setSearch] = useState(query.search ?? '');
    const trash = query.status === 'trash';

    const tabs = [
        { key: 'pending', label: t('Pending') },
        { key: 'approved', label: t('Approved') },
        { key: 'spam', label: t('Spam') },
        { key: 'rejected', label: t('Rejected') },
        { key: 'trash', label: t('Trash') },
    ];

    const act = (comment: Comment, status: 'approved' | 'rejected' | 'spam') =>
        router.post(
            moderate({ ...args, comment: comment.id }),
            { status },
            { preserveScroll: true },
        );

    const runBulk = (action: string) =>
        router.post(
            bulk(args),
            { action, ids: selected },
            { preserveScroll: true, onSuccess: () => setSelected([]) },
        );

    return (
        <>
            <PageHeader
                title={t('Comments')}
                description={t(
                    'Readers comment on the blog; approved comments go live.',
                )}
            />

            <nav
                className="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-muted p-1 text-sm"
                aria-label={t('Comments')}
            >
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={index(args, { query: { status: tab.key } })}
                        aria-current={
                            query.status === tab.key ? 'page' : undefined
                        }
                        className={cn(
                            'rounded-md px-3 py-1',
                            query.status === tab.key
                                ? 'bg-background font-medium shadow-xs'
                                : 'text-foreground/80 hover:text-foreground',
                        )}
                    >
                        {tab.label}
                        {tab.key !== 'trash' && counts[tab.key]
                            ? ` (${formatNumber(counts[tab.key])})`
                            : ''}
                    </Link>
                ))}
            </nav>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        index(args).url,
                        { status: query.status, search: search || undefined },
                        { preserveState: true },
                    );
                }}
            >
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t('Search…')}
                    aria-label={t('Search')}
                    className="max-w-xs"
                />
            </form>

            {selected.length > 0 && (
                <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/50 p-2 text-sm">
                    <span className="px-2">
                        {t(':count selected', { count: selected.length })}
                    </span>
                    {trash ? (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => runBulk('restore')}
                        >
                            <RotateCcw />
                            {t('Restore')}
                        </Button>
                    ) : (
                        <>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => runBulk('approved')}
                            >
                                <Check />
                                {t('Approve')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => runBulk('spam')}
                            >
                                <ShieldAlert />
                                {t('Spam')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => runBulk('delete')}
                            >
                                <Trash2 />
                                {t('Move to trash')}
                            </Button>
                        </>
                    )}
                </div>
            )}

            {comments.data.length === 0 ? (
                <EmptyState title={t('Nothing here')} />
            ) : (
                <ul className="divide-y rounded-lg border">
                    {comments.data.map((comment) => (
                        <li key={comment.id} className="flex gap-3 p-4">
                            <Checkbox
                                aria-label={t('Select')}
                                className="mt-1"
                                checked={selected.includes(comment.id)}
                                onCheckedChange={(checked) =>
                                    setSelected((current) =>
                                        checked === true
                                            ? [...current, comment.id]
                                            : current.filter(
                                                  (id) => id !== comment.id,
                                              ),
                                    )
                                }
                            />
                            <div className="min-w-0 flex-1">
                                <p className="text-sm">
                                    <span className="font-medium">
                                        {comment.author_name}
                                    </span>
                                    {comment.reply_to && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            → {comment.reply_to}
                                        </span>
                                    )}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {formatDate(
                                            comment.created_at,
                                            true,
                                        )}{' '}
                                        ·{' '}
                                    </span>
                                    <Link
                                        href={edit({
                                            ...args,
                                            article: comment.article.id,
                                        })}
                                        className="text-muted-foreground hover:underline"
                                    >
                                        {comment.article.title || t('Untitled')}
                                    </Link>
                                </p>
                                <p className="mt-1 text-sm whitespace-pre-wrap">
                                    {comment.body}
                                </p>
                                {replying === comment.id && (
                                    <ReplyForm
                                        comment={comment}
                                        onDone={() => setReplying(null)}
                                    />
                                )}
                            </div>
                            <div className="flex shrink-0 items-start gap-1">
                                {trash ? (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            router.post(
                                                restore({
                                                    ...args,
                                                    comment: comment.id,
                                                }),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <RotateCcw />
                                        {t('Restore')}
                                    </Button>
                                ) : (
                                    <>
                                        {comment.status !== 'approved' && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                aria-label={t('Approve')}
                                                onClick={() =>
                                                    act(comment, 'approved')
                                                }
                                            >
                                                <Check />
                                            </Button>
                                        )}
                                        {comment.status !== 'rejected' && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                aria-label={t('Reject')}
                                                onClick={() =>
                                                    act(comment, 'rejected')
                                                }
                                            >
                                                <X />
                                            </Button>
                                        )}
                                        {comment.status !== 'spam' && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                aria-label={t('Spam')}
                                                onClick={() =>
                                                    act(comment, 'spam')
                                                }
                                            >
                                                <ShieldAlert />
                                            </Button>
                                        )}
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={t('Reply')}
                                            onClick={() =>
                                                setReplying(comment.id)
                                            }
                                        >
                                            <MessageSquareReply />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={t('Move to trash')}
                                            onClick={() =>
                                                router.delete(
                                                    destroy({
                                                        ...args,
                                                        comment: comment.id,
                                                    }),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {comments.last_page > 1 && (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={comments.current_page <= 1}
                        onClick={() =>
                            router.get(
                                index(args).url,
                                { ...query, page: comments.current_page - 1 },
                                { preserveState: true },
                            )
                        }
                    >
                        {t('Previous page')}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={comments.current_page >= comments.last_page}
                        onClick={() =>
                            router.get(
                                index(args).url,
                                { ...query, page: comments.current_page + 1 },
                                { preserveState: true },
                            )
                        }
                    >
                        {t('Next page')}
                    </Button>
                </div>
            )}
        </>
    );
}

Comments.layout = {
    breadcrumbs: [{ title: 'Comments' }],
};
