import type { RequestPayload } from '@inertiajs/core';
import { Link, router } from '@inertiajs/react';
import {
    Archive,
    CalendarClock,
    ChevronLeft,
    Eye,
    History,
    ImageIcon,
    Languages,
    Loader2,
    MessageSquareDiff,
    MoreHorizontal,
    Send,
    Trash2,
    Undo2,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ColorInput } from '@/components/app/color-input';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { DateTimeInput } from '@/components/app/date-time-input';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SlugInput } from '@/components/app/slug-input';
import { MediaPicker } from '@/components/content/media-picker';
import { StatusBadge } from '@/components/content/status-badge';
import { TagPicker } from '@/components/content/tag-picker';
import type { MediaUrls } from '@/components/editor/media-context';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import {
    archive,
    destroy,
    draft,
    edit,
    index,
    preview,
    publish,
    review,
    revisions,
    schedule,
    unpublish,
    update,
} from '@/routes/articles';
import {
    index as suggestions,
    store as storeSuggestion,
} from '@/routes/articles/suggestions';
import { store as storeTranslation } from '@/routes/articles/translations';
import type { ArticleStatus, Option, TiptapDocument } from '@/types';

type Language = { id: string; code: string; label: string };

type Props = {
    article: {
        id: string;
        slug: string;
        status: ArticleStatus;
        category_id: string | null;
        author_profile_id: string;
        bgcolor: string | null;
        tag_ids: string[];
        language: Language | null;
        scheduled_at: string | null;
        published_at: string | null;
        is_live: boolean;
        has_unpublished_changes: boolean;
    };
    revision: {
        id: string | null;
        number: number | null;
        title: string;
        description: string | null;
        body: TiptapDocument;
        cover_media_id: string | null;
        created_at: string | null;
    };
    media: MediaUrls;
    translations: {
        id: string;
        status: ArticleStatus;
        language: Language | null;
    }[];
    options: {
        categories: { id: string; name: string; parent_id: string | null }[];
        authors: { id: string; display_name: string }[];
        tags: Option[];
        languages: Language[];
    };
    pendingSuggestions: number;
    abilities: {
        update: boolean;
        publish: boolean;
        delete: boolean;
        suggest: boolean;
        review: boolean;
        manageTags: boolean;
        uploadMedia: boolean;
        create: boolean;
    };
};

type Content = {
    title: string;
    description: string | null;
    body: TiptapDocument;
    cover_media_id: string | null;
};

type SaveState = 'saved' | 'dirty' | 'saving' | 'error';

const NO_CATEGORY = 'none';

/** Inertia sends JSON, so nested documents are fine; its types only know form data. */
const payload = (data: object) => data as RequestPayload;
const AUTOSAVE_MS = 2000;

export default function EditArticle({
    article,
    revision,
    media,
    translations,
    options,
    pendingSuggestions,
    abilities,
}: Props) {
    const { t, formatDate } = useTranslation();
    const args = { ...useBlogRoute(), article: article.id };
    const canEdit = abilities.update;
    const suggestMode = !canEdit && abilities.suggest;

    const [content, setContent] = useState<Content>({
        title: revision.title,
        description: revision.description,
        body: revision.body,
        cover_media_id: revision.cover_media_id,
    });
    const [cover, setCover] = useState(
        revision.cover_media_id
            ? (media[revision.cover_media_id] ?? null)
            : null,
    );
    const [saveState, setSaveState] = useState<SaveState>('saved');
    const [savedAt, setSavedAt] = useState(revision.created_at);
    const [bodyError, setBodyError] = useState<string | null>(null);
    const [note, setNote] = useState('');
    const [scheduleAt, setScheduleAt] = useState<string | null>(
        article.scheduled_at,
    );
    const [slug, setSlug] = useState(article.slug);
    const [tags, setTags] = useState(article.tag_ids);
    const [color, setColor] = useState(article.bgcolor ?? '');
    const contentRef = useRef(content);
    contentRef.current = content;

    const change = (patch: Partial<Content>) => {
        setContent((current) => ({ ...current, ...patch }));
        setSaveState('dirty');
    };

    const save = useCallback(
        (autosave: boolean) => {
            if (!canEdit) {
                return;
            }

            setSaveState('saving');
            router.put(
                draft(args).url,
                payload({ ...contentRef.current, autosave }),
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['revision', 'article', 'pendingSuggestions'],
                    onSuccess: () => {
                        setSaveState((state) =>
                            state === 'saving' ? 'saved' : state,
                        );
                        setSavedAt(new Date().toISOString());
                        setBodyError(null);
                    },
                    onError: (errors) => {
                        setSaveState('error');
                        setBodyError(
                            errors.body ??
                                errors.title ??
                                errors.cover_media_id ??
                                null,
                        );
                    },
                },
            );
        },
        [canEdit, article.id], // eslint-disable-line react-hooks/exhaustive-deps
    );

    // Autosave after a pause in typing.
    useEffect(() => {
        if (saveState !== 'dirty' || !canEdit) {
            return;
        }

        const timeout = setTimeout(() => save(true), AUTOSAVE_MS);

        return () => clearTimeout(timeout);
    }, [content, saveState, canEdit, save]);

    // Warn before leaving with unsaved changes.
    useEffect(() => {
        const unsaved =
            saveState === 'dirty' ||
            saveState === 'saving' ||
            (suggestMode && saveState !== 'saved');
        const beforeUnload = (event: BeforeUnloadEvent) => {
            if (unsaved) {
                event.preventDefault();
            }
        };
        const removeInertia = router.on('before', (event) => {
            if (
                unsaved &&
                event.detail.visit.method === 'get' &&
                !window.confirm(t('You have unsaved changes. Leave anyway?'))
            ) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', beforeUnload);

        return () => {
            window.removeEventListener('beforeunload', beforeUnload);
            removeInertia();
        };
    }, [saveState, suggestMode, t]);

    const updateDetails = (data: Record<string, unknown>) =>
        router.patch(update(args).url, data as never, {
            preserveScroll: true,
            preserveState: true,
            only: ['article', 'flash', 'errors'],
        });

    const runAction = (action: () => void) => {
        if (saveState === 'dirty' || saveState === 'error') {
            // Make sure the latest text is saved before publishing or scheduling.
            router.put(
                draft(args).url,
                payload({ ...contentRef.current, autosave: false }),
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        setSaveState('saved');
                        action();
                    },
                },
            );

            return;
        }

        action();
    };

    const missingLanguages = options.languages.filter(
        (language) =>
            language.id !== article.language?.id &&
            !translations.some(
                (translation) => translation.language?.id === language.id,
            ),
    );

    const statusText = {
        saved: savedAt
            ? t('Saved :date', { date: formatDate(savedAt, true) })
            : t('Saved'),
        dirty: t('Unsaved changes'),
        saving: t('Saving…'),
        error: t('Not saved'),
    }[saveState];

    return (
        <>
            <PageTitle title={content.title || t('Untitled')} />

            <div className="flex flex-wrap items-center gap-2">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={index(args)}>
                        <ChevronLeft />
                        {t('Articles')}
                    </Link>
                </Button>
                <StatusBadge status={article.status} />
                {article.has_unpublished_changes && (
                    <Badge variant="outline">{t('Unpublished changes')}</Badge>
                )}
                {article.status === 'scheduled' && article.scheduled_at && (
                    <span className="text-sm text-muted-foreground">
                        {t('Goes live :date', {
                            date: formatDate(article.scheduled_at, true),
                        })}
                    </span>
                )}
                {canEdit && (
                    <span
                        className="flex items-center gap-1 text-sm text-muted-foreground"
                        aria-live="polite"
                    >
                        {saveState === 'saving' && (
                            <Loader2 className="size-3 animate-spin" />
                        )}
                        {statusText}
                    </span>
                )}

                <div className="ml-auto flex flex-wrap items-center gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={preview(args)}>
                            <Eye />
                            {t('Preview')}
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={revisions(args)}>
                            <History />
                            {t('History')}
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={suggestions(args)}>
                            <MessageSquareDiff />
                            {t('Suggestions')}
                            {pendingSuggestions > 0 && (
                                <Badge>{pendingSuggestions}</Badge>
                            )}
                        </Link>
                    </Button>

                    {canEdit && saveState !== 'saved' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => save(false)}
                            disabled={saveState === 'saving'}
                        >
                            {t('Save')}
                        </Button>
                    )}

                    {abilities.publish && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={article.is_live}
                                >
                                    <CalendarClock />
                                    {t('Schedule')}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-72">
                                <div className="grid gap-3">
                                    <Label htmlFor="schedule-at">
                                        {t('Publish on')}
                                    </Label>
                                    <DateTimeInput
                                        id="schedule-at"
                                        value={scheduleAt}
                                        onChange={setScheduleAt}
                                    />
                                    <Button
                                        size="sm"
                                        disabled={!scheduleAt}
                                        onClick={() =>
                                            runAction(() =>
                                                router.post(
                                                    schedule(args).url,
                                                    {
                                                        scheduled_at:
                                                            scheduleAt,
                                                    },
                                                    { preserveScroll: true },
                                                ),
                                            )
                                        }
                                    >
                                        {t('Schedule')}
                                    </Button>
                                </div>
                            </PopoverContent>
                        </Popover>
                    )}

                    {abilities.publish ? (
                        <Button
                            size="sm"
                            onClick={() =>
                                runAction(() =>
                                    router.post(
                                        publish(args).url,
                                        {},
                                        { preserveScroll: true },
                                    ),
                                )
                            }
                            disabled={
                                article.is_live &&
                                !article.has_unpublished_changes &&
                                saveState === 'saved'
                            }
                        >
                            <Send />
                            {article.is_live
                                ? t('Update live article')
                                : t('Publish')}
                        </Button>
                    ) : (
                        canEdit &&
                        article.status === 'draft' && (
                            <Button
                                size="sm"
                                onClick={() =>
                                    runAction(() =>
                                        router.post(
                                            review(args).url,
                                            {},
                                            { preserveScroll: true },
                                        ),
                                    )
                                }
                            >
                                <Send />
                                {t('Send for review')}
                            </Button>
                        )
                    )}

                    {(abilities.publish || abilities.delete) && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('More actions')}
                                >
                                    <MoreHorizontal />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {abilities.publish &&
                                    article.status !== 'draft' && (
                                        <DropdownMenuItem
                                            onSelect={() =>
                                                router.post(
                                                    unpublish(args).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Undo2 />
                                            {article.is_live
                                                ? t('Unpublish')
                                                : t('Back to draft')}
                                        </DropdownMenuItem>
                                    )}
                                {abilities.publish &&
                                    article.status !== 'archived' && (
                                        <DropdownMenuItem
                                            onSelect={() =>
                                                router.post(
                                                    archive(args).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Archive />
                                            {t('Archive')}
                                        </DropdownMenuItem>
                                    )}
                                {abilities.delete && (
                                    <>
                                        <DropdownMenuSeparator />
                                        <ConfirmDialog
                                            trigger={
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={(event) =>
                                                        event.preventDefault()
                                                    }
                                                >
                                                    <Trash2 />
                                                    {t('Move to trash')}
                                                </DropdownMenuItem>
                                            }
                                            title={t(
                                                'Move this article to the trash?',
                                            )}
                                            description={t(
                                                'It leaves the public site and can be restored from the trash.',
                                            )}
                                            confirmLabel={t('Move to trash')}
                                            destructive
                                            onConfirm={() =>
                                                router.delete(destroy(args).url)
                                            }
                                        />
                                    </>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            {suggestMode && (
                <Alert>
                    <AlertDescription>
                        {t(
                            "You can't edit this article, but you can suggest changes. Edit below and send them for review.",
                        )}
                    </AlertDescription>
                </Alert>
            )}

            {bodyError && (
                <Alert variant="destructive">
                    <AlertDescription>{bodyError}</AlertDescription>
                </Alert>
            )}

            <div className="flex flex-col gap-6 xl:flex-row">
                <div className="min-w-0 flex-1 space-y-4">
                    <Textarea
                        value={content.title}
                        onChange={(event) =>
                            change({
                                title: event.target.value.replace(/\n/g, ' '),
                            })
                        }
                        placeholder={t('Title')}
                        aria-label={t('Title')}
                        readOnly={!canEdit && !suggestMode}
                        rows={1}
                        className="min-h-0 resize-none border-none px-0 text-3xl font-semibold shadow-none focus-visible:ring-0 md:text-4xl"
                    />
                    <Textarea
                        value={content.description ?? ''}
                        onChange={(event) =>
                            change({ description: event.target.value || null })
                        }
                        placeholder={t(
                            'Short description for search engines and social media (optional)',
                        )}
                        aria-label={t('Description')}
                        readOnly={!canEdit && !suggestMode}
                        rows={2}
                        maxLength={500}
                        className="resize-none text-muted-foreground"
                    />

                    <div className="flex flex-wrap items-center gap-3">
                        {cover && (
                            <img
                                src={cover.url}
                                alt={cover.alt ?? ''}
                                className="h-24 rounded-md border object-cover"
                            />
                        )}
                        {(canEdit || suggestMode) && (
                            <>
                                <MediaPicker
                                    trigger={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                        >
                                            <ImageIcon />
                                            {cover
                                                ? t('Change cover')
                                                : t('Add cover image')}
                                        </Button>
                                    }
                                    onSelect={(item) => {
                                        setCover({
                                            url: item.url,
                                            alt: item.alt_text,
                                        });
                                        change({ cover_media_id: item.id });
                                    }}
                                />
                                {cover && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setCover(null);
                                            change({ cover_media_id: null });
                                        }}
                                    >
                                        {t('Remove cover')}
                                    </Button>
                                )}
                            </>
                        )}
                    </div>

                    <RichTextEditor
                        initialContent={revision.body}
                        media={media}
                        editable={canEdit || suggestMode}
                        onChange={(body) => change({ body })}
                    />

                    {suggestMode && (
                        <div className="grid gap-2 rounded-lg border p-4">
                            <Label htmlFor="suggestion-note">
                                {t('Note for the reviewer (optional)')}
                            </Label>
                            <Textarea
                                id="suggestion-note"
                                value={note}
                                onChange={(event) =>
                                    setNote(event.target.value)
                                }
                                rows={2}
                            />
                            <div>
                                <Button
                                    onClick={() =>
                                        router.post(
                                            storeSuggestion(args).url,
                                            payload({
                                                ...contentRef.current,
                                                note,
                                            }),
                                            {
                                                onSuccess: () =>
                                                    setSaveState('saved'),
                                                onError: (errors) =>
                                                    setBodyError(
                                                        errors.body ??
                                                            errors.title ??
                                                            null,
                                                    ),
                                            },
                                        )
                                    }
                                    disabled={saveState === 'saved'}
                                >
                                    <MessageSquareDiff />
                                    {t('Send suggestion')}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                <aside className="space-y-6 xl:w-80 xl:shrink-0">
                    <section className="space-y-4 rounded-lg border p-4">
                        <h2 className="font-medium">{t('Details')}</h2>
                        <FormField id="slug" label={t('Address')}>
                            <SlugInput
                                id="slug"
                                value={slug}
                                onChange={setSlug}
                                disabled={!canEdit}
                                onBlurCapture={() =>
                                    slug !== article.slug &&
                                    updateDetails({ slug })
                                }
                            />
                        </FormField>
                        <FormField id="category" label={t('Category')}>
                            <Select
                                value={article.category_id ?? NO_CATEGORY}
                                onValueChange={(value) =>
                                    updateDetails({
                                        category_id:
                                            value === NO_CATEGORY
                                                ? null
                                                : value,
                                    })
                                }
                                disabled={!canEdit}
                            >
                                <SelectTrigger id="category" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CATEGORY}>
                                        {t('No category')}
                                    </SelectItem>
                                    {options.categories.map((category) => (
                                        <SelectItem
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <div className="grid gap-2">
                            <Label>{t('Tags')}</Label>
                            <TagPicker
                                options={options.tags}
                                value={tags}
                                onChange={(value) => {
                                    setTags(value);
                                    updateDetails({ tag_ids: value });
                                }}
                                canCreate={abilities.manageTags}
                                disabled={!canEdit}
                            />
                        </div>
                        <FormField id="author" label={t('Author')}>
                            <Select
                                value={article.author_profile_id}
                                onValueChange={(value) =>
                                    updateDetails({ author_profile_id: value })
                                }
                                disabled={!canEdit}
                            >
                                <SelectTrigger id="author" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.authors.map((author) => (
                                        <SelectItem
                                            key={author.id}
                                            value={author.id}
                                        >
                                            {author.display_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField id="bgcolor" label={t('Accent color')}>
                            <ColorInput
                                id="bgcolor"
                                value={color}
                                onChange={setColor}
                                disabled={!canEdit}
                                onBlur={() =>
                                    color !== (article.bgcolor ?? '') &&
                                    updateDetails({ bgcolor: color || null })
                                }
                            />
                        </FormField>
                    </section>

                    {options.languages.length > 1 && (
                        <section className="space-y-3 rounded-lg border p-4">
                            <h2 className="flex items-center gap-2 font-medium">
                                <Languages className="size-4" />
                                {t('Translations')}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {t('This version: :language', {
                                    language: article.language?.label ?? '',
                                })}
                            </p>
                            <ul className="space-y-1">
                                {translations.map((translation) => (
                                    <li
                                        key={translation.id}
                                        className="flex items-center justify-between gap-2 text-sm"
                                    >
                                        <Link
                                            href={edit({
                                                ...args,
                                                article: translation.id,
                                            })}
                                            className="hover:underline"
                                        >
                                            {translation.language?.label}
                                        </Link>
                                        <StatusBadge
                                            status={translation.status}
                                        />
                                    </li>
                                ))}
                            </ul>
                            {abilities.create &&
                                missingLanguages.map((language) => (
                                    <Button
                                        key={language.id}
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() =>
                                            router.post(
                                                storeTranslation(args).url,
                                                { language_id: language.id },
                                            )
                                        }
                                    >
                                        {t('Translate to :language', {
                                            language: language.label,
                                        })}
                                    </Button>
                                ))}
                        </section>
                    )}

                    {article.published_at && (
                        <p className="px-1 text-sm text-muted-foreground">
                            {t('First published :date', {
                                date: formatDate(article.published_at, true),
                            })}
                        </p>
                    )}
                </aside>
            </div>
        </>
    );
}

EditArticle.layout = {
    breadcrumbs: [{ title: 'Articles' }, { title: 'Edit' }],
};
