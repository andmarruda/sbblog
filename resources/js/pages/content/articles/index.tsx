import { Form, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Archive, ArchiveRestore, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { DataTable, type TableQuery } from '@/components/app/data-table';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import {
    StatusBadge,
    useStatusLabels,
} from '@/components/content/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { bulk, edit, index, store } from '@/routes/articles';
import type { ArticleStatus, Paginated } from '@/types';

type Row = {
    id: string;
    title: string;
    slug: string;
    status: ArticleStatus;
    author: string | null;
    category: string | null;
    language: string | null;
    has_unpublished_changes: boolean;
    updated_at: string | null;
    published_at: string | null;
    scheduled_at: string | null;
    deleted_at: string | null;
};

type Props = {
    articles: Paginated<Row>;
    query: TableQuery & { trashed?: string };
    filters: {
        authors: { id: string; display_name: string }[];
        categories: { id: string; name: string }[];
        languages: { id: string; code: string; label: string }[];
        tags: { id: string; name: string }[];
        defaultLanguageId: string;
    };
};

const ALL = 'all';

function NewArticleDialog({
    languages,
    defaultLanguageId,
}: {
    languages: Props['filters']['languages'];
    defaultLanguageId: string;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [language, setLanguage] = useState(defaultLanguageId);

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    {t('New article')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('New article')}</DialogTitle>
                </DialogHeader>
                <Form
                    action={store(args)}
                    transform={(data) => ({
                        ...data,
                        language_id: language || null,
                    })}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="new-title"
                                label={t('Title')}
                                error={errors.title}
                            >
                                <Input
                                    id="new-title"
                                    name="title"
                                    autoFocus
                                    required
                                />
                            </FormField>
                            {languages.length > 1 && (
                                <FormField
                                    id="new-language"
                                    label={t('Language')}
                                    error={errors.language_id}
                                >
                                    <Select
                                        value={language}
                                        onValueChange={setLanguage}
                                    >
                                        <SelectTrigger
                                            id="new-language"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languages.map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={option.id}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            )}
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {t('Start writing')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function FilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string | undefined;
    options: { id: string; name: string }[];
    onChange: (value: string | undefined) => void;
}) {
    const { t } = useTranslation();

    return (
        <Select
            value={value ?? ALL}
            onValueChange={(next) => onChange(next === ALL ? undefined : next)}
        >
            <SelectTrigger className="w-full sm:w-40" aria-label={label}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>
                    {label}: {t('All')}
                </SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.id} value={option.id}>
                        {option.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function Articles({ articles, query, filters }: Props) {
    const { t, formatDate } = useTranslation();
    const can = useCan();
    const args = useBlogRoute();
    const statusLabels = useStatusLabels();
    const [selected, setSelected] = useState<string[]>([]);
    const trashed = query.trashed === '1' || query.trashed === 'true';

    const runBulk = (action: 'archive' | 'delete' | 'restore') =>
        router.post(
            bulk(args),
            { action, ids: selected },
            { preserveScroll: true, onSuccess: () => setSelected([]) },
        );

    const columns = useMemo<ColumnDef<Row, unknown>[]>(
        () => [
            {
                id: 'select',
                header: () => (
                    <Checkbox
                        aria-label={t('Select all')}
                        checked={
                            articles.data.length > 0 &&
                            selected.length === articles.data.length
                        }
                        onCheckedChange={(checked) =>
                            setSelected(
                                checked === true
                                    ? articles.data.map((row) => row.id)
                                    : [],
                            )
                        }
                    />
                ),
                cell: ({ row }) => (
                    <Checkbox
                        aria-label={t('Select')}
                        checked={selected.includes(row.original.id)}
                        onCheckedChange={(checked) =>
                            setSelected((current) =>
                                checked === true
                                    ? [...current, row.original.id]
                                    : current.filter(
                                          (id) => id !== row.original.id,
                                      ),
                            )
                        }
                    />
                ),
            },
            {
                id: 'title',
                header: t('Title'),
                enableSorting: true,
                cell: ({ row }) => (
                    <div className="max-w-md min-w-48">
                        {row.original.deleted_at ? (
                            <p className="truncate font-medium">
                                {row.original.title || t('Untitled')}
                            </p>
                        ) : (
                            <Link
                                href={edit({
                                    ...args,
                                    article: row.original.id,
                                })}
                                className="block truncate font-medium hover:underline"
                            >
                                {row.original.title || t('Untitled')}
                            </Link>
                        )}
                        <p className="truncate text-sm text-muted-foreground">
                            /{row.original.slug}
                        </p>
                    </div>
                ),
            },
            {
                id: 'status',
                header: t('Status'),
                cell: ({ row }) => (
                    <div className="flex flex-col items-start gap-1">
                        <StatusBadge status={row.original.status} />
                        {row.original.has_unpublished_changes && (
                            <span className="text-xs text-muted-foreground">
                                {t('Unpublished changes')}
                            </span>
                        )}
                        {row.original.status === 'scheduled' &&
                            row.original.scheduled_at && (
                                <span className="text-xs text-muted-foreground">
                                    {formatDate(
                                        row.original.scheduled_at,
                                        true,
                                    )}
                                </span>
                            )}
                    </div>
                ),
            },
            {
                id: 'author',
                header: t('Author'),
                cell: ({ row }) => row.original.author ?? '—',
            },
            {
                id: 'category',
                header: t('Category'),
                cell: ({ row }) => row.original.category ?? '—',
            },
            ...(filters.languages.length > 1
                ? [
                      {
                          id: 'language',
                          header: t('Language'),
                          cell: ({ row }: { row: { original: Row } }) => (
                              <Badge variant="outline">
                                  {row.original.language}
                              </Badge>
                          ),
                      },
                  ]
                : []),
            {
                id: 'updated_at',
                header: t('Updated'),
                enableSorting: true,
                cell: ({ row }) => (
                    <span className="whitespace-nowrap text-muted-foreground">
                        {formatDate(row.original.updated_at)}
                    </span>
                ),
            },
        ],
        [
            t,
            formatDate,
            args.workspace,
            args.blog,
            selected,
            articles.data,
            filters.languages.length,
        ], // eslint-disable-line react-hooks/exhaustive-deps
    );

    return (
        <>
            <PageHeader
                title={t('Articles')}
                description={t('Everything written for this blog.')}
                actions={
                    can('articles.create') && (
                        <NewArticleDialog
                            languages={filters.languages}
                            defaultLanguageId={filters.defaultLanguageId}
                        />
                    )
                }
            />
            <Tabs
                value={trashed ? 'trash' : 'all'}
                onValueChange={(value) =>
                    router.get(
                        index(args).url,
                        value === 'trash' ? { trashed: 1 } : {},
                        { preserveState: false },
                    )
                }
            >
                <TabsList>
                    <TabsTrigger value="all">{t('Articles')}</TabsTrigger>
                    <TabsTrigger value="trash">{t('Trash')}</TabsTrigger>
                </TabsList>
            </Tabs>

            {selected.length > 0 && (
                <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/50 p-2 text-sm">
                    <span className="px-2">
                        {t(':count selected', { count: selected.length })}
                    </span>
                    {trashed ? (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => runBulk('restore')}
                        >
                            <ArchiveRestore />
                            {t('Restore')}
                        </Button>
                    ) : (
                        <>
                            {can('articles.publish') && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => runBulk('archive')}
                                >
                                    <Archive />
                                    {t('Archive')}
                                </Button>
                            )}
                            {can('articles.delete') && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => runBulk('delete')}
                                >
                                    <Trash2 />
                                    {t('Move to trash')}
                                </Button>
                            )}
                        </>
                    )}
                </div>
            )}

            <DataTable
                columns={columns}
                paginator={articles}
                query={query}
                getRowId={(row) => row.id}
                toolbar={(apply) => (
                    <>
                        <FilterSelect
                            label={t('Status')}
                            value={query.status as string | undefined}
                            options={(
                                Object.keys(statusLabels) as ArticleStatus[]
                            ).map((status) => ({
                                id: status,
                                name: statusLabels[status],
                            }))}
                            onChange={(status) => apply({ status })}
                        />
                        <FilterSelect
                            label={t('Author')}
                            value={query.author as string | undefined}
                            options={filters.authors.map((author) => ({
                                id: author.id,
                                name: author.display_name,
                            }))}
                            onChange={(author) => apply({ author })}
                        />
                        <FilterSelect
                            label={t('Category')}
                            value={query.category as string | undefined}
                            options={filters.categories}
                            onChange={(category) => apply({ category })}
                        />
                        <FilterSelect
                            label={t('Tag')}
                            value={query.tag as string | undefined}
                            options={filters.tags}
                            onChange={(tag) => apply({ tag })}
                        />
                        {filters.languages.length > 1 && (
                            <FilterSelect
                                label={t('Language')}
                                value={query.language as string | undefined}
                                options={filters.languages.map((language) => ({
                                    id: language.id,
                                    name: language.label,
                                }))}
                                onChange={(language) => apply({ language })}
                            />
                        )}
                    </>
                )}
            />
        </>
    );
}

Articles.layout = {
    breadcrumbs: [{ title: 'Articles' }],
};
