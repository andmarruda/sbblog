import { Form, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { GitMerge, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { DataTable, type TableQuery } from '@/components/app/data-table';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { SlugInput } from '@/components/app/slug-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, merge, store, update } from '@/routes/tags';
import type { Paginated } from '@/types';

type Tag = { id: string; name: string; slug: string; articles_count: number };

type Props = {
    tags: Paginated<Tag>;
    query: TableQuery;
};

function TagDialog({ tag, onClose }: { tag: Tag | null; onClose: () => void }) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [name, setName] = useState(tag?.name ?? '');
    const [slug, setSlug] = useState(tag?.slug ?? '');

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {tag ? t('Edit tag') : t('New tag')}
                    </DialogTitle>
                </DialogHeader>
                <Form
                    action={
                        tag ? update({ ...args, tag: tag.id }) : store(args)
                    }
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="tag-name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="tag-name"
                                    name="name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    required
                                />
                            </FormField>
                            <FormField
                                id="tag-slug"
                                label={t('Address')}
                                error={errors.slug}
                            >
                                <SlugInput
                                    id="tag-slug"
                                    name="slug"
                                    value={slug}
                                    onChange={setSlug}
                                    source={name}
                                />
                            </FormField>
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function MergeDialog({
    tag,
    tags,
    onClose,
}: {
    tag: Tag;
    tags: Tag[];
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [target, setTarget] = useState('');

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Merge :name into…', { name: tag.name })}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Its articles move to the chosen tag and this tag is deleted.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={merge({ ...args, tag: tag.id })}
                    transform={() => ({ target_id: target })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="merge-target"
                                label={t('Tag')}
                                error={errors.target_id}
                            >
                                <Select
                                    value={target}
                                    onValueChange={setTarget}
                                >
                                    <SelectTrigger
                                        id="merge-target"
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={t('Choose a tag')}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tags
                                            .filter(
                                                (option) =>
                                                    option.id !== tag.id,
                                            )
                                            .map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={option.id}
                                                >
                                                    {option.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    disabled={processing || target === ''}
                                >
                                    {t('Merge')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function Tags({ tags, query }: Props) {
    const { t, formatNumber } = useTranslation();
    const args = useBlogRoute();
    const [editing, setEditing] = useState<Tag | null | undefined>(undefined);
    const [merging, setMerging] = useState<Tag | null>(null);

    const columns = useMemo<ColumnDef<Tag, unknown>[]>(
        () => [
            {
                id: 'name',
                header: t('Name'),
                enableSorting: true,
                cell: ({ row }) => (
                    <div>
                        <p className="font-medium">{row.original.name}</p>
                        <p className="text-sm text-muted-foreground">
                            /{row.original.slug}
                        </p>
                    </div>
                ),
            },
            {
                id: 'articles_count',
                header: t('Articles'),
                enableSorting: true,
                cell: ({ row }) => formatNumber(row.original.articles_count),
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => (
                    <div className="flex justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('Edit')}
                            onClick={() => setEditing(row.original)}
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('Merge')}
                            onClick={() => setMerging(row.original)}
                        >
                            <GitMerge />
                        </Button>
                        <ConfirmDialog
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Delete')}
                                >
                                    <Trash2 />
                                </Button>
                            }
                            title={t('Delete :name?', {
                                name: row.original.name,
                            })}
                            description={t('It is removed from every article.')}
                            confirmLabel={t('Delete')}
                            destructive
                            onConfirm={() =>
                                router.delete(
                                    destroy({ ...args, tag: row.original.id }),
                                    { preserveScroll: true },
                                )
                            }
                        />
                    </div>
                ),
            },
        ],
        [t, formatNumber, args.workspace, args.blog], // eslint-disable-line react-hooks/exhaustive-deps
    );

    return (
        <>
            <PageHeader
                title={t('Tags')}
                description={t(
                    'Keywords that connect articles across categories.',
                )}
                actions={
                    <Button onClick={() => setEditing(null)}>
                        <Plus />
                        {t('New tag')}
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                paginator={tags}
                query={query}
                getRowId={(row) => row.id}
            />
            {editing !== undefined && (
                <TagDialog
                    key={editing?.id ?? 'new'}
                    tag={editing}
                    onClose={() => setEditing(undefined)}
                />
            )}
            {merging && (
                <MergeDialog
                    tag={merging}
                    tags={tags.data}
                    onClose={() => setMerging(null)}
                />
            )}
        </>
    );
}

Tags.layout = {
    breadcrumbs: [{ title: 'Tags' }],
};
