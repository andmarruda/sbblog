import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { ArticleStatus } from '@/types';

export function useStatusLabels(): Record<ArticleStatus, string> {
    const { t } = useTranslation();

    return {
        draft: t('Draft'),
        in_review: t('In review'),
        scheduled: t('Scheduled'),
        published: t('Published'),
        archived: t('Archived'),
    };
}

const styles: Record<ArticleStatus, string> = {
    draft: 'bg-muted text-foreground',
    in_review:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    scheduled: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
    published:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    archived: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
};

export function StatusBadge({ status }: { status: ArticleStatus }) {
    const labels = useStatusLabels();

    return (
        <Badge
            variant="outline"
            className={cn('border-transparent', styles[status])}
        >
            {labels[status]}
        </Badge>
    );
}
