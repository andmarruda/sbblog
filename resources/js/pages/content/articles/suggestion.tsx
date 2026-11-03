import { Form, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, Quote, X } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/app/page-header';
import {
    DiffView,
    type RevisionDiffData,
} from '@/components/content/diff-view';
import { SuggestionStatusBadge } from '@/components/content/suggestion-status';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { accept, comment, index, reject } from '@/routes/articles/suggestions';
import type { SuggestionSummary } from './suggestions';

type Props = {
    article: { id: string; title: string };
    suggestion: SuggestionSummary & { is_outdated: boolean };
    diff: RevisionDiffData | null;
    comments: {
        id: string;
        author: string | null;
        body: string;
        quote: string | null;
        created_at: string | null;
    }[];
    canReview: boolean;
};

export default function Suggestion({
    article,
    suggestion,
    diff,
    comments,
    canReview,
}: Props) {
    const { t, formatDate } = useTranslation();
    const args = {
        ...useBlogRoute(),
        article: article.id,
        suggestion: suggestion.id,
    };
    const [quote, setQuote] = useState<string | null>(null);
    const pending = suggestion.status === 'pending';

    return (
        <>
            <PageHeader
                title={t('Suggestion')}
                description={article.title || t('Untitled')}
                actions={
                    <Button variant="ghost" asChild>
                        <Link
                            href={index({
                                workspace: args.workspace,
                                blog: args.blog,
                                article: article.id,
                            })}
                        >
                            <ChevronLeft />
                            {t('All suggestions')}
                        </Link>
                    </Button>
                }
            />
            <div className="flex flex-wrap items-center gap-3">
                <SuggestionStatusBadge status={suggestion.status} />
                <span className="text-sm text-muted-foreground">
                    {[
                        suggestion.requested_by ?? suggestion.source,
                        formatDate(suggestion.created_at, true),
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                </span>
                {canReview && pending && (
                    <div className="ml-auto flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    reject(args).url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <X />
                            {t('Reject')}
                        </Button>
                        <Button
                            onClick={() => router.post(accept(args).url)}
                            disabled={suggestion.is_outdated}
                        >
                            <Check />
                            {t('Accept')}
                        </Button>
                    </div>
                )}
            </div>
            {suggestion.note && (
                <p className="rounded-lg bg-muted p-3 text-sm">
                    {suggestion.note}
                </p>
            )}
            {pending && suggestion.is_outdated && (
                <Alert>
                    <AlertDescription>
                        {t(
                            'The article changed after this suggestion was made. It can no longer be accepted as is.',
                        )}
                    </AlertDescription>
                </Alert>
            )}

            <div className="flex flex-col gap-6 lg:flex-row">
                <div className="min-w-0 flex-1">
                    {diff && <DiffView diff={diff} onSelectText={setQuote} />}
                </div>
                <aside className="space-y-4 lg:w-80 lg:shrink-0">
                    <h2 className="font-medium">{t('Discussion')}</h2>
                    <ul className="space-y-3">
                        {comments.map((item) => (
                            <li
                                key={item.id}
                                className="space-y-1 rounded-lg border p-3 text-sm"
                            >
                                <p className="text-xs text-muted-foreground">
                                    {item.author} ·{' '}
                                    {formatDate(item.created_at, true)}
                                </p>
                                {item.quote && (
                                    <blockquote className="border-l-2 pl-2 text-muted-foreground italic">
                                        {item.quote}
                                    </blockquote>
                                )}
                                <p className="whitespace-pre-wrap">
                                    {item.body}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <Form
                        action={comment(args)}
                        transform={(data) => ({ ...data, quote })}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        onSuccess={() => setQuote(null)}
                        className="grid gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                {quote ? (
                                    <div className="flex items-start gap-2 rounded-md bg-muted p-2 text-sm">
                                        <Quote className="mt-0.5 size-3 shrink-0" />
                                        <span className="line-clamp-3 flex-1 italic">
                                            {quote}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => setQuote(null)}
                                            aria-label={t('Remove quote')}
                                        >
                                            <X className="size-3" />
                                        </button>
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">
                                        {t(
                                            'Select text in the changes to comment on it.',
                                        )}
                                    </p>
                                )}
                                <Textarea
                                    name="body"
                                    rows={3}
                                    placeholder={t('Write a comment…')}
                                    aria-invalid={
                                        errors.body ? true : undefined
                                    }
                                    required
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {t('Comment')}
                                </Button>
                            </>
                        )}
                    </Form>
                </aside>
            </div>
        </>
    );
}

Suggestion.layout = {
    breadcrumbs: [{ title: 'Articles' }, { title: 'Suggestion' }],
};
