import { Link } from '@inertiajs/react';
import { ChevronLeft, MessageSquareDiff } from 'lucide-react';
import { EmptyState } from '@/components/app/empty-state';
import { PageHeader } from '@/components/app/page-header';
import { SuggestionStatusBadge } from '@/components/content/suggestion-status';
import { Button } from '@/components/ui/button';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { edit } from '@/routes/articles';
import { show } from '@/routes/articles/suggestions';

export type SuggestionSummary = {
    id: string;
    status: 'pending' | 'accepted' | 'rejected' | 'superseded';
    source: string;
    title: string | null;
    note: string | null;
    requested_by: string | null;
    reviewed_by: string | null;
    reviewed_at: string | null;
    created_at: string | null;
    comments_count: number;
};

type Props = {
    article: { id: string; title: string };
    suggestions: SuggestionSummary[];
};

export default function Suggestions({ article, suggestions }: Props) {
    const { t, tc, formatDate } = useTranslation();
    const args = { ...useBlogRoute(), article: article.id };

    return (
        <>
            <PageHeader
                title={t('Suggestions')}
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
            {suggestions.length === 0 ? (
                <EmptyState
                    icon={MessageSquareDiff}
                    title={t('No suggestions yet')}
                    description={t(
                        'Reviewers and apps can propose changes without editing the article.',
                    )}
                />
            ) : (
                <ul className="divide-y rounded-lg border">
                    {suggestions.map((suggestion) => (
                        <li key={suggestion.id}>
                            <Link
                                href={show({
                                    ...args,
                                    suggestion: suggestion.id,
                                })}
                                className="flex items-center gap-4 p-4 hover:bg-muted/50"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">
                                        {suggestion.note ||
                                            suggestion.title ||
                                            t('Untitled')}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {[
                                            suggestion.requested_by ??
                                                suggestion.source,
                                            formatDate(
                                                suggestion.created_at,
                                                true,
                                            ),
                                            tc(
                                                ':count comment|:count comments',
                                                suggestion.comments_count,
                                            ),
                                        ].join(' · ')}
                                    </p>
                                </div>
                                <SuggestionStatusBadge
                                    status={suggestion.status}
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}

Suggestions.layout = {
    breadcrumbs: [{ title: 'Articles' }, { title: 'Suggestions' }],
};
