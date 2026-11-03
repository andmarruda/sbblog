import { ImageIcon, Search } from 'lucide-react';
import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { EmptyState } from '@/components/app/empty-state';
import { UploadButton } from '@/components/content/upload-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { http } from '@/lib/http';
import { cn } from '@/lib/utils';
import { picker } from '@/routes/media';
import type { MediaItem, Paginated } from '@/types';

type Props = {
    trigger: ReactNode;
    onSelect: (media: MediaItem) => void;
};

/**
 * Pick an image from the blog's library, or upload one.
 */
export function MediaPicker({ trigger, onSelect }: Props) {
    const { t } = useTranslation();
    const can = useCan();
    const args = useBlogRoute();
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [result, setResult] = useState<Paginated<MediaItem> | null>(null);
    const [loading, setLoading] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);

        try {
            setResult(
                await http<Paginated<MediaItem>>(
                    picker(args, {
                        query: { search: search || undefined, page },
                    }).url,
                ),
            );
        } finally {
            setLoading(false);
        }
    }, [args.workspace, args.blog, search, page]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!open) {
            return;
        }

        const timeout = setTimeout(() => void load(), 250);

        return () => clearTimeout(timeout);
    }, [open, load]);

    const choose = (media: MediaItem) => {
        onSelect(media);
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{t('Choose an image')}</DialogTitle>
                    <DialogDescription>
                        {t('From the media library of this blog.')}
                    </DialogDescription>
                </DialogHeader>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setPage(1);
                            }}
                            placeholder={t('Search by description…')}
                            className="pl-8"
                        />
                    </div>
                    {can('media.upload') && (
                        <UploadButton onUploaded={choose} />
                    )}
                </div>

                <div className="max-h-[55vh] min-h-48 overflow-y-auto">
                    {loading && !result ? (
                        <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                            {Array.from({ length: 12 }).map((_, index) => (
                                <Skeleton
                                    key={index}
                                    className="aspect-square"
                                />
                            ))}
                        </div>
                    ) : result && result.data.length > 0 ? (
                        <div
                            className={cn(
                                'grid grid-cols-3 gap-2 sm:grid-cols-6',
                                loading && 'opacity-60',
                            )}
                        >
                            {result.data.map((media) => (
                                <button
                                    key={media.id}
                                    type="button"
                                    onClick={() => choose(media)}
                                    className="group relative aspect-square overflow-hidden rounded-md border bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    title={media.alt_text ?? ''}
                                >
                                    <img
                                        src={media.thumb_url}
                                        alt={media.alt_text ?? ''}
                                        loading="lazy"
                                        className="size-full object-cover transition-transform group-hover:scale-105"
                                    />
                                </button>
                            ))}
                        </div>
                    ) : (
                        <EmptyState
                            icon={ImageIcon}
                            title={t('No images yet')}
                        />
                    )}
                </div>

                {result && result.last_page > 1 && (
                    <div className="flex items-center justify-end gap-2 text-sm text-muted-foreground">
                        {t('Page :page of :pages', {
                            page: result.current_page,
                            pages: result.last_page,
                        })}
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page <= 1}
                            onClick={() => setPage(page - 1)}
                        >
                            {t('Previous page')}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page >= result.last_page}
                            onClick={() => setPage(page + 1)}
                        >
                            {t('Next page')}
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
