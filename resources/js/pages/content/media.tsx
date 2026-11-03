import { Form, router } from '@inertiajs/react';
import { ImageIcon, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { UploadButton } from '@/components/content/upload-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { destroy, index, update } from '@/routes/media';
import type { MediaItem, Paginated } from '@/types';

type Props = {
    media: Paginated<MediaItem>;
    query: { search?: string };
};

function formatBytes(bytes: number, locale: string) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${new Intl.NumberFormat(locale, { maximumFractionDigits: 1 }).format(value)} ${units[unit]}`;
}

export default function Media({ media, query }: Props) {
    const { t, formatDate, locale } = useTranslation();
    const can = useCan();
    const args = useBlogRoute();
    const [selected, setSelected] = useState<MediaItem | null>(null);
    const [search, setSearch] = useState(query.search ?? '');

    const reload = () => router.reload({ only: ['media'] });

    return (
        <>
            <PageHeader
                title={t('Media')}
                description={t(
                    'Images used by articles, authors and the blog.',
                )}
                actions={
                    can('media.upload') && (
                        <UploadButton onUploaded={reload} multiple />
                    )
                }
            />
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(index(args).url, search ? { search } : {}, {
                        preserveState: true,
                        replace: true,
                    });
                }}
            >
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t('Search by description…')}
                    className="max-w-xs"
                />
            </form>

            {media.data.length === 0 ? (
                <EmptyState
                    icon={ImageIcon}
                    title={t('No images yet')}
                    description={t('Upload images to use them in articles.')}
                />
            ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    {media.data.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => setSelected(item)}
                            className={cn(
                                'group overflow-hidden rounded-lg border bg-muted text-left focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                selected?.id === item.id && 'ring-2 ring-ring',
                            )}
                        >
                            <img
                                src={item.thumb_url}
                                alt={item.alt_text ?? ''}
                                loading="lazy"
                                className="aspect-square w-full object-cover"
                            />
                            <p className="truncate px-2 py-1.5 text-xs text-muted-foreground">
                                {item.alt_text || '—'}
                            </p>
                        </button>
                    ))}
                </div>
            )}

            {media.last_page > 1 && (
                <div className="flex justify-end gap-2">
                    {media.current_page > 1 && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    index(args).url,
                                    { ...query, page: media.current_page - 1 },
                                    { preserveState: true },
                                )
                            }
                        >
                            {t('Previous page')}
                        </Button>
                    )}
                    {media.current_page < media.last_page && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    index(args).url,
                                    { ...query, page: media.current_page + 1 },
                                    { preserveState: true },
                                )
                            }
                        >
                            {t('Next page')}
                        </Button>
                    )}
                </div>
            )}

            <Sheet
                open={selected !== null}
                onOpenChange={(open) => !open && setSelected(null)}
            >
                <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                    {selected && (
                        <>
                            <SheetHeader>
                                <SheetTitle>{t('Image details')}</SheetTitle>
                                <SheetDescription>
                                    {[
                                        selected.width && selected.height
                                            ? `${selected.width}×${selected.height}`
                                            : null,
                                        formatBytes(
                                            selected.size_bytes,
                                            locale,
                                        ),
                                        formatDate(selected.created_at),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="space-y-4 px-4 pb-4">
                                <img
                                    src={selected.url}
                                    alt={selected.alt_text ?? ''}
                                    className="w-full rounded-md border"
                                />
                                <Form
                                    key={selected.id}
                                    action={update({
                                        ...args,
                                        media: selected.id,
                                    })}
                                    options={{ preserveScroll: true }}
                                    onSuccess={() => setSelected(null)}
                                    className="grid gap-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <FormField
                                                id="alt_text"
                                                label={t(
                                                    'Description (alt text)',
                                                )}
                                                hint={t(
                                                    'Describe the image for people who cannot see it.',
                                                )}
                                                error={errors.alt_text}
                                            >
                                                <Input
                                                    id="alt_text"
                                                    name="alt_text"
                                                    defaultValue={
                                                        selected.alt_text ?? ''
                                                    }
                                                />
                                            </FormField>
                                            <div className="flex justify-between gap-2">
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    {t('Save')}
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            className="text-destructive"
                                                        >
                                                            <Trash2 />
                                                            {t('Delete')}
                                                        </Button>
                                                    }
                                                    title={t(
                                                        'Delete this image?',
                                                    )}
                                                    description={t(
                                                        'Images still used by an article, author or the blog cannot be deleted.',
                                                    )}
                                                    confirmLabel={t('Delete')}
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            destroy({
                                                                ...args,
                                                                media: selected.id,
                                                            }),
                                                            {
                                                                preserveScroll: true,
                                                                onSuccess: () =>
                                                                    setSelected(
                                                                        null,
                                                                    ),
                                                            },
                                                        )
                                                    }
                                                />
                                            </div>
                                            {errors.media && (
                                                <p className="text-sm text-destructive">
                                                    {errors.media}
                                                </p>
                                            )}
                                        </>
                                    )}
                                </Form>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </>
    );
}

Media.layout = {
    breadcrumbs: [{ title: 'Media' }],
};
