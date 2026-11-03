import { Link, router } from '@inertiajs/react';
import { ChevronLeft, RotateCcw } from 'lucide-react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { PageHeader } from '@/components/app/page-header';
import {
    DiffView,
    type RevisionDiffData,
} from '@/components/content/diff-view';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { edit, revisions as revisionsRoute } from '@/routes/articles';
import { restore } from '@/routes/articles/revisions';

type Revision = {
    id: string;
    number: number;
    title: string;
    author: string | null;
    created_at: string | null;
    is_current: boolean;
    is_published: boolean;
};

type Props = {
    article: { id: string; title: string };
    revisions: Revision[];
    from: string | null;
    to: string | null;
    diff: RevisionDiffData | null;
    canRestore: boolean;
};

const NONE = 'none';

export default function Revisions({
    article,
    revisions,
    from,
    to,
    diff,
    canRestore,
}: Props) {
    const { t, formatDate } = useTranslation();
    const args = { ...useBlogRoute(), article: article.id };
    const selected = revisions.find((revision) => revision.id === to);

    const go = (query: { from?: string | null; to?: string | null }) =>
        router.get(
            revisionsRoute(args).url,
            { from: query.from ?? undefined, to: query.to ?? undefined },
            { preserveScroll: true, preserveState: true, replace: true },
        );

    return (
        <>
            <PageHeader
                title={t('History')}
                description={article.title || t('Untitled')}
                actions={
                    <Button variant="ghost" asChild>
                        <Link href={edit(args)}>
                            <ChevronLeft />
                            {t('Back to the editor')}
                        </Link>
                    </Button>
                }
            />
            <div className="flex flex-col gap-6 lg:flex-row">
                <ul className="space-y-1 lg:w-72 lg:shrink-0">
                    {revisions.map((revision) => (
                        <li key={revision.id}>
                            <button
                                type="button"
                                onClick={() => go({ to: revision.id })}
                                className={cn(
                                    'w-full rounded-md border px-3 py-2 text-left text-sm transition-colors hover:bg-muted',
                                    revision.id === to &&
                                        'border-ring bg-muted',
                                )}
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-medium">
                                        #{revision.number}
                                    </span>
                                    <span className="flex gap-1">
                                        {revision.is_published && (
                                            <Badge>{t('Live')}</Badge>
                                        )}
                                        {revision.is_current && (
                                            <Badge variant="secondary">
                                                {t('Latest')}
                                            </Badge>
                                        )}
                                    </span>
                                </div>
                                <p className="truncate">
                                    {revision.title || t('Untitled')}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {[
                                        revision.author,
                                        formatDate(revision.created_at, true),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </button>
                        </li>
                    ))}
                </ul>

                <div className="min-w-0 flex-1 space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {t('Compare with')}
                        </span>
                        <Select
                            value={from ?? NONE}
                            onValueChange={(value) =>
                                go({ from: value === NONE ? null : value, to })
                            }
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>
                                    {t('Nothing (show everything)')}
                                </SelectItem>
                                {revisions
                                    .filter((revision) => revision.id !== to)
                                    .map((revision) => (
                                        <SelectItem
                                            key={revision.id}
                                            value={revision.id}
                                        >
                                            #{revision.number} ·{' '}
                                            {formatDate(
                                                revision.created_at,
                                                true,
                                            )}
                                        </SelectItem>
                                    ))}
                            </SelectContent>
                        </Select>
                        {canRestore && selected && !selected.is_current && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="ml-auto"
                                    >
                                        <RotateCcw />
                                        {t('Restore #:number', {
                                            number: selected.number,
                                        })}
                                    </Button>
                                }
                                title={t('Restore revision #:number?', {
                                    number: selected.number,
                                })}
                                description={t(
                                    'It becomes the latest draft. Nothing is lost: the current text stays in the history.',
                                )}
                                confirmLabel={t('Restore')}
                                onConfirm={() =>
                                    router.post(
                                        restore({
                                            ...args,
                                            revision: selected.id,
                                        }).url,
                                    )
                                }
                            />
                        )}
                    </div>
                    {diff && <DiffView diff={diff} />}
                </div>
            </div>
        </>
    );
}

Revisions.layout = {
    breadcrumbs: [{ title: 'Articles' }, { title: 'History' }],
};
