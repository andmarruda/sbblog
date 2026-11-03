import { Form, router } from '@inertiajs/react';
import {
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    FolderTree,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { ColorInput } from '@/components/app/color-input';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { SettingsSection } from '@/components/app/settings-section';
import { SlugInput } from '@/components/app/slug-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { destroy, move, restore, store, update } from '@/routes/categories';

type Category = {
    id: string;
    parent_id: string | null;
    name: string;
    slug: string;
    bgcolor: string | null;
    position: number;
    articles_count: number;
};

type Props = {
    categories: Category[];
    trashed: { id: string; name: string; slug: string; deleted_at: string }[];
};

const ROOT = 'root';

/** Depth-first order with depth, so the tree can be shown as an indented list. */
function flatten(
    categories: Category[],
    parent: string | null = null,
    depth = 0,
): { category: Category; depth: number }[] {
    return categories
        .filter((category) => category.parent_id === parent)
        .flatMap((category) => [
            { category, depth },
            ...flatten(categories, category.id, depth + 1),
        ]);
}

function CategoryDialog({
    open,
    onOpenChange,
    category,
    categories,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    category: Category | null;
    categories: Category[];
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [name, setName] = useState(category?.name ?? '');
    const [slug, setSlug] = useState(category?.slug ?? '');
    const [parent, setParent] = useState(category?.parent_id ?? ROOT);
    const [color, setColor] = useState(category?.bgcolor ?? '');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {category ? t('Edit category') : t('New category')}
                    </DialogTitle>
                </DialogHeader>
                <Form
                    action={
                        category
                            ? update({ ...args, category: category.id })
                            : store(args)
                    }
                    transform={(data) => ({
                        ...data,
                        parent_id: parent === ROOT ? null : parent,
                        bgcolor: color || null,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="category-name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="category-name"
                                    name="name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    required
                                />
                            </FormField>
                            <FormField
                                id="category-slug"
                                label={t('Address')}
                                error={errors.slug}
                            >
                                <SlugInput
                                    id="category-slug"
                                    name="slug"
                                    value={slug}
                                    onChange={setSlug}
                                    source={name}
                                />
                            </FormField>
                            <FormField
                                id="category-parent"
                                label={t('Inside')}
                                error={errors.parent_id}
                            >
                                <Select
                                    value={parent}
                                    onValueChange={setParent}
                                >
                                    <SelectTrigger
                                        id="category-parent"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ROOT}>
                                            {t('Top level')}
                                        </SelectItem>
                                        {flatten(categories)
                                            .filter(
                                                ({ category: option }) =>
                                                    option.id !== category?.id,
                                            )
                                            .map(
                                                ({
                                                    category: option,
                                                    depth,
                                                }) => (
                                                    <SelectItem
                                                        key={option.id}
                                                        value={option.id}
                                                    >
                                                        {'— '.repeat(depth)}
                                                        {option.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                id="category-color"
                                label={t('Color')}
                                error={errors.bgcolor}
                            >
                                <ColorInput
                                    id="category-color"
                                    value={color}
                                    onChange={setColor}
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

export default function Categories({ categories, trashed }: Props) {
    const { t, tc, formatDate } = useTranslation();
    const args = useBlogRoute();
    const [editing, setEditing] = useState<Category | null>(null);
    const [open, setOpen] = useState(false);

    const edit = (category: Category | null) => {
        setEditing(category);
        setOpen(true);
    };

    const rows = flatten(categories);

    return (
        <>
            <PageHeader
                title={t('Categories')}
                description={t(
                    'Group articles by subject. Categories can be nested.',
                )}
                actions={
                    <Button onClick={() => edit(null)}>
                        <Plus />
                        {t('New category')}
                    </Button>
                }
            />

            {rows.length === 0 ? (
                <EmptyState icon={FolderTree} title={t('No categories yet')} />
            ) : (
                <ul className="divide-y rounded-lg border">
                    {rows.map(({ category, depth }) => (
                        <li
                            key={category.id}
                            className="flex items-center gap-3 p-3"
                            style={{ paddingLeft: `${0.75 + depth * 1.5}rem` }}
                        >
                            <span
                                className="size-3 shrink-0 rounded-full border"
                                style={{
                                    backgroundColor:
                                        category.bgcolor ?? 'transparent',
                                }}
                            />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {category.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    /{category.slug} ·{' '}
                                    {tc(
                                        ':count article|:count articles',
                                        category.articles_count,
                                    )}
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Move up')}
                                    onClick={() =>
                                        router.post(
                                            move({
                                                ...args,
                                                category: category.id,
                                            }),
                                            { direction: 'up' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ArrowUp />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Move down')}
                                    onClick={() =>
                                        router.post(
                                            move({
                                                ...args,
                                                category: category.id,
                                            }),
                                            { direction: 'down' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ArrowDown />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Edit')}
                                    onClick={() => edit(category)}
                                >
                                    <Pencil />
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
                                        name: category.name,
                                    })}
                                    description={t(
                                        'Its articles will have no category and its subcategories move up one level.',
                                    )}
                                    confirmLabel={t('Delete')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroy({
                                                ...args,
                                                category: category.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {trashed.length > 0 && (
                <SettingsSection title={t('Deleted categories')}>
                    <ul className="divide-y rounded-lg border">
                        {trashed.map((category) => (
                            <li
                                key={category.id}
                                className="flex items-center justify-between gap-4 p-3 text-sm"
                            >
                                <div>
                                    <p className="font-medium">
                                        {category.name}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {t('Deleted on :date', {
                                            date: formatDate(
                                                category.deleted_at,
                                            ),
                                        })}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            restore({
                                                ...args,
                                                category: category.id,
                                            }),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ArchiveRestore />
                                    {t('Restore')}
                                </Button>
                            </li>
                        ))}
                    </ul>
                </SettingsSection>
            )}

            {open && (
                <CategoryDialog
                    key={editing?.id ?? 'new'}
                    open={open}
                    onOpenChange={setOpen}
                    category={editing}
                    categories={categories}
                />
            )}
        </>
    );
}

Categories.layout = {
    breadcrumbs: [{ title: 'Categories' }],
};
