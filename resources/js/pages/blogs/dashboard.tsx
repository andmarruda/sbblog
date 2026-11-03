import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CalendarClock,
    CircleCheck,
    FilePen,
    MessageSquare,
    MessageSquareDiff,
    SearchCheck,
    TriangleAlert,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/components/app/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { edit } from '@/routes/articles';
import { index as comments } from '@/routes/comments';
import { index as publishing } from '@/routes/publishing';

type ArticleItem = {
    id: string;
    title: string;
    updated_at: string | null;
    scheduled_at: string | null;
};

type Props = {
    stats: {
        drafts: number;
        in_review: number;
        scheduled: number;
        published: number;
        pending_comments: number;
        pending_suggestions: number;
    };
    myDrafts: ArticleItem[];
    inReview: ArticleItem[];
    upcoming: ArticleItem[];
    laggingFrontends: { id: string; name: string; behind: number }[];
    analyticsUrl: string | null;
};

function ArticleList({
    title,
    items,
    empty,
    date,
}: {
    title: string;
    items: ArticleItem[];
    empty: string;
    date: (item: ArticleItem) => string;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{empty}</p>
                ) : (
                    <ul className="space-y-2">
                        {items.map((item) => (
                            <li
                                key={item.id}
                                className="flex justify-between gap-2 text-sm"
                            >
                                <Link
                                    href={edit({ ...args, article: item.id })}
                                    className="truncate hover:underline"
                                >
                                    {item.title || t('Untitled')}
                                </Link>
                                <span className="shrink-0 text-muted-foreground">
                                    {date(item)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

function Stat({
    label,
    value,
    icon: Icon,
    action,
}: {
    label: string;
    value: number;
    icon: typeof FilePen;
    action?: ReactNode;
}) {
    const { formatNumber } = useTranslation();

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent className="flex items-end justify-between gap-2">
                <p className="text-3xl font-semibold tabular-nums">
                    {formatNumber(value)}
                </p>
                {action}
            </CardContent>
        </Card>
    );
}

export default function BlogDashboard({
    stats,
    myDrafts,
    inReview,
    upcoming,
    laggingFrontends,
    analyticsUrl,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const can = useCan();
    const args = useBlogRoute();
    const { currentBlog } = usePage().props;

    return (
        <>
            <PageHeader
                title={currentBlog?.name ?? t('Dashboard')}
                description={t('An overview of your blog.')}
                actions={
                    analyticsUrl && (
                        <Button variant="outline" asChild>
                            <a
                                href={analyticsUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <BarChart3 />
                                {t('Open analytics')}
                            </a>
                        </Button>
                    )
                }
            />

            {laggingFrontends.length > 0 && can('frontends.manage') && (
                <Link
                    href={publishing(args)}
                    className="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                >
                    <TriangleAlert className="size-4" />
                    {laggingFrontends
                        .map((frontend) =>
                            t(':name is :count events behind', {
                                name: frontend.name,
                                count: formatNumber(frontend.behind),
                            }),
                        )
                        .join(' · ')}
                </Link>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <Stat label={t('Drafts')} value={stats.drafts} icon={FilePen} />
                <Stat
                    label={t('In review')}
                    value={stats.in_review}
                    icon={SearchCheck}
                />
                <Stat
                    label={t('Scheduled')}
                    value={stats.scheduled}
                    icon={CalendarClock}
                />
                <Stat
                    label={t('Published')}
                    value={stats.published}
                    icon={CircleCheck}
                />
                <Stat
                    label={t('Pending suggestions')}
                    value={stats.pending_suggestions}
                    icon={MessageSquareDiff}
                />
                <Stat
                    label={t('Pending comments')}
                    value={stats.pending_comments}
                    icon={MessageSquare}
                    action={
                        can('comments.moderate') &&
                        stats.pending_comments > 0 && (
                            <Button size="sm" variant="ghost" asChild>
                                <Link href={comments(args)}>
                                    {t('Moderate')}
                                </Link>
                            </Button>
                        )
                    }
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <ArticleList
                    title={t('Your drafts')}
                    items={myDrafts}
                    empty={t('No drafts.')}
                    date={(item) => formatDate(item.updated_at)}
                />
                <ArticleList
                    title={t('Waiting for review')}
                    items={inReview}
                    empty={t('Nothing waiting.')}
                    date={(item) => formatDate(item.updated_at)}
                />
                <ArticleList
                    title={t('Coming up')}
                    items={upcoming}
                    empty={t('Nothing scheduled.')}
                    date={(item) => formatDate(item.scheduled_at, true)}
                />
            </div>
        </>
    );
}

BlogDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard' }],
};
