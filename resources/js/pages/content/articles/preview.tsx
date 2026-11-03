import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { PageTitle } from '@/components/app/page-title';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { edit } from '@/routes/articles';

type Props = {
    article: {
        id: string;
        title: string;
        description: string | null;
        html: string;
        cover: { url: string; alt: string | null } | null;
        author: string | null;
        category: string | null;
        tags: string[];
    };
};

/**
 * The latest draft as frontends will render it. The HTML is produced and escaped by the console.
 */
export default function Preview({ article }: Props) {
    const { t } = useTranslation();
    const args = { ...useBlogRoute(), article: article.id };

    return (
        <>
            <PageTitle title={t('Preview')} />
            <div className="flex items-center justify-between gap-2">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={edit(args)}>
                        <ChevronLeft />
                        {t('Back to the editor')}
                    </Link>
                </Button>
                <Badge variant="outline">
                    {t('Preview of the latest draft')}
                </Badge>
            </div>
            <article className="mx-auto w-full max-w-3xl space-y-6 py-6">
                {article.category && (
                    <p className="text-sm font-medium text-muted-foreground">
                        {article.category}
                    </p>
                )}
                <h1 className="text-4xl font-bold tracking-tight">
                    {article.title || t('Untitled')}
                </h1>
                {article.description && (
                    <p className="text-lg text-muted-foreground">
                        {article.description}
                    </p>
                )}
                {article.author && (
                    <p className="text-sm">
                        {t('By :name', { name: article.author })}
                    </p>
                )}
                {article.cover && (
                    <img
                        src={article.cover.url}
                        alt={article.cover.alt ?? ''}
                        className="w-full rounded-lg"
                    />
                )}
                <div
                    className="prose max-w-none prose-neutral dark:prose-invert"
                    dangerouslySetInnerHTML={{ __html: article.html }}
                />
                {article.tags.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {article.tags.map((tag) => (
                            <Badge key={tag} variant="secondary">
                                {tag}
                            </Badge>
                        ))}
                    </div>
                )}
            </article>
        </>
    );
}

Preview.layout = {
    breadcrumbs: [{ title: 'Articles' }, { title: 'Preview' }],
};
