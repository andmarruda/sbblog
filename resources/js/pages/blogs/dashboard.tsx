import { usePage } from '@inertiajs/react';
import {
    CalendarClock,
    CircleCheck,
    FilePen,
    MessageSquare,
    SearchCheck,
} from 'lucide-react';
import { PageHeader } from '@/components/app/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    stats: {
        drafts: number;
        in_review: number;
        scheduled: number;
        published: number;
        pending_comments: number;
    };
};

export default function BlogDashboard({ stats }: Props) {
    const { t, formatNumber } = useTranslation();
    const { currentBlog } = usePage().props;

    const cards = [
        { label: t('Drafts'), value: stats.drafts, icon: FilePen },
        { label: t('In review'), value: stats.in_review, icon: SearchCheck },
        { label: t('Scheduled'), value: stats.scheduled, icon: CalendarClock },
        { label: t('Published'), value: stats.published, icon: CircleCheck },
        {
            label: t('Pending comments'),
            value: stats.pending_comments,
            icon: MessageSquare,
        },
    ];

    return (
        <>
            <PageHeader
                title={currentBlog?.name ?? t('Dashboard')}
                description={t('An overview of your blog.')}
            />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {cards.map(({ label, value, icon: Icon }) => (
                    <Card key={label}>
                        <CardHeader className="flex flex-row items-center justify-between gap-2 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {label}
                            </CardTitle>
                            <Icon className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-semibold tabular-nums">
                                {formatNumber(value)}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </>
    );
}

BlogDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard' }],
};
