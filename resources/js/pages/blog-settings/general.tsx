import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { DangerZone } from '@/components/app/danger-zone';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { SlugInput } from '@/components/app/slug-input';
import { MediaPicker } from '@/components/content/media-picker';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useCan } from '@/hooks/use-can';
import { useLabels } from '@/hooks/use-labels';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, update } from '@/routes/blogs/settings';

type Language = { id: string; code: string; label: string };

type Props = {
    blog: {
        id: string;
        name: string;
        slug: string;
        title: string | null;
        slogan: string | null;
        description: string | null;
        canonical_url: string | null;
        default_language_id: string;
        autoconvert_webp: boolean;
        comment_policy: string;
        language_ids: string[];
        brand_media_id: string | null;
        brand: { url: string } | null;
    };
    languages: Language[];
    commentPolicies: string[];
};

export default function BlogGeneral({
    blog,
    languages,
    commentPolicies,
}: Props) {
    const { t } = useTranslation();
    const labels = useLabels();
    const can = useCan();
    const { currentWorkspace } = usePage().props;
    const args = { workspace: currentWorkspace?.slug ?? '', blog: blog.slug };

    const [slug, setSlug] = useState(blog.slug);
    const [languageIds, setLanguageIds] = useState<string[]>(blog.language_ids);
    const [defaultLanguageId, setDefaultLanguageId] = useState(
        blog.default_language_id,
    );
    const [commentPolicy, setCommentPolicy] = useState(blog.comment_policy);
    const [webp, setWebp] = useState(blog.autoconvert_webp);
    const [brand, setBrand] = useState<{
        id: string | null;
        url: string | null;
    }>({ id: blog.brand_media_id, url: blog.brand?.url ?? null });

    const toggleLanguage = (id: string, checked: boolean) =>
        setLanguageIds((current) =>
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );

    return (
        <>
            <PageTitle title={t('Blog settings')} />
            <Form
                action={update(args)}
                transform={(data) => ({
                    ...data,
                    language_ids: languageIds,
                    default_language_id: defaultLanguageId,
                    comment_policy: commentPolicy,
                    autoconvert_webp: webp,
                    brand_media_id: brand.id,
                })}
                options={{ preserveScroll: true }}
                className="max-w-2xl space-y-10"
            >
                {({ processing, errors }) => (
                    <>
                        <SettingsSection
                            title={t('General')}
                            description={t('How the blog is named and found.')}
                        >
                            <div className="grid gap-4">
                                <FormField
                                    id="name"
                                    label={t('Name')}
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={blog.name}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="slug"
                                    label={t('Address')}
                                    hint={t(
                                        'Used in console URLs. Changing it breaks old links.',
                                    )}
                                    error={errors.slug}
                                >
                                    <SlugInput
                                        id="slug"
                                        name="slug"
                                        value={slug}
                                        onChange={setSlug}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="title"
                                    label={t('Title')}
                                    hint={t(
                                        'Shown in browser tabs and search results.',
                                    )}
                                    error={errors.title}
                                >
                                    <Input
                                        id="title"
                                        name="title"
                                        defaultValue={blog.title ?? ''}
                                    />
                                </FormField>
                                <FormField
                                    id="slogan"
                                    label={t('Slogan')}
                                    error={errors.slogan}
                                >
                                    <Input
                                        id="slogan"
                                        name="slogan"
                                        defaultValue={blog.slogan ?? ''}
                                    />
                                </FormField>
                                <FormField
                                    id="description"
                                    label={t('Description')}
                                    error={errors.description}
                                >
                                    <Textarea
                                        id="description"
                                        name="description"
                                        defaultValue={blog.description ?? ''}
                                        rows={3}
                                        maxLength={500}
                                    />
                                </FormField>
                                <FormField
                                    id="canonical_url"
                                    label={t('Public URL')}
                                    hint={t(
                                        'Where readers find the blog, e.g. https://blog.example.com',
                                    )}
                                    error={errors.canonical_url}
                                >
                                    <Input
                                        id="canonical_url"
                                        name="canonical_url"
                                        type="url"
                                        defaultValue={blog.canonical_url ?? ''}
                                    />
                                </FormField>
                            </div>
                        </SettingsSection>

                        <SettingsSection
                            title={t('Brand')}
                            description={t(
                                'Logo or image that represents the blog.',
                            )}
                        >
                            <div className="flex items-center gap-4">
                                {brand.url ? (
                                    <img
                                        src={brand.url}
                                        alt=""
                                        className="size-16 rounded-md border object-cover"
                                    />
                                ) : (
                                    <div className="size-16 rounded-md border border-dashed" />
                                )}
                                <MediaPicker
                                    trigger={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                        >
                                            {t('Choose image')}
                                        </Button>
                                    }
                                    onSelect={(media) =>
                                        setBrand({
                                            id: media.id,
                                            url: media.thumb_url,
                                        })
                                    }
                                />
                                {brand.id && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            setBrand({ id: null, url: null })
                                        }
                                    >
                                        {t('Remove')}
                                    </Button>
                                )}
                            </div>
                            {errors.brand_media_id && (
                                <p className="text-sm text-destructive">
                                    {errors.brand_media_id}
                                </p>
                            )}
                        </SettingsSection>

                        <SettingsSection
                            title={t('Languages')}
                            description={t(
                                'Languages articles can be written in.',
                            )}
                        >
                            <div className="grid gap-4">
                                <fieldset className="grid gap-2">
                                    <legend className="sr-only">
                                        {t('Languages')}
                                    </legend>
                                    {languages.map((language) => (
                                        <div
                                            key={language.id}
                                            className="flex items-center gap-2"
                                        >
                                            <Checkbox
                                                id={`language-${language.id}`}
                                                checked={languageIds.includes(
                                                    language.id,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleLanguage(
                                                        language.id,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor={`language-${language.id}`}
                                            >
                                                {language.label}
                                            </Label>
                                        </div>
                                    ))}
                                    {errors.language_ids && (
                                        <p className="text-sm text-destructive">
                                            {errors.language_ids}
                                        </p>
                                    )}
                                </fieldset>
                                <FormField
                                    id="default_language_id"
                                    label={t('Default language')}
                                    error={errors.default_language_id}
                                >
                                    <Select
                                        value={defaultLanguageId}
                                        onValueChange={setDefaultLanguageId}
                                    >
                                        <SelectTrigger
                                            id="default_language_id"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languages
                                                .filter((language) =>
                                                    languageIds.includes(
                                                        language.id,
                                                    ),
                                                )
                                                .map((language) => (
                                                    <SelectItem
                                                        key={language.id}
                                                        value={language.id}
                                                    >
                                                        {language.label}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            </div>
                        </SettingsSection>

                        <SettingsSection title={t('Comments and media')}>
                            <div className="grid gap-4">
                                <FormField
                                    id="comment_policy"
                                    label={t('Comments')}
                                    error={errors.comment_policy}
                                >
                                    <Select
                                        value={commentPolicy}
                                        onValueChange={setCommentPolicy}
                                    >
                                        <SelectTrigger
                                            id="comment_policy"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {commentPolicies.map((policy) => (
                                                <SelectItem
                                                    key={policy}
                                                    value={policy}
                                                >
                                                    {labels.commentPolicy[
                                                        policy
                                                    ] ?? policy}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                                <div className="flex items-center justify-between gap-4 rounded-lg border p-4">
                                    <div className="space-y-1">
                                        <Label htmlFor="autoconvert_webp">
                                            {t('Convert images to WebP')}
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'Smaller files and faster pages. The original is kept.',
                                            )}
                                        </p>
                                    </div>
                                    <Switch
                                        id="autoconvert_webp"
                                        checked={webp}
                                        onCheckedChange={setWebp}
                                    />
                                </div>
                            </div>
                        </SettingsSection>

                        <Button type="submit" disabled={processing}>
                            {t('Save')}
                        </Button>
                    </>
                )}
            </Form>

            {can('blogs.delete') && (
                <DangerZone
                    title={t('Delete blog')}
                    description={t(
                        'It can be restored later from the workspace settings.',
                    )}
                >
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">
                                {t('Delete blog')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('Delete blog?')}</DialogTitle>
                                <DialogDescription>
                                    {t('Type :text to confirm.', {
                                        text: blog.slug,
                                    })}
                                </DialogDescription>
                            </DialogHeader>
                            <Form action={destroy(args)} className="grid gap-4">
                                {({ processing, errors }) => (
                                    <>
                                        <FormField
                                            id="confirmation"
                                            label={t('Blog address')}
                                            error={errors.confirmation}
                                        >
                                            <Input
                                                id="confirmation"
                                                name="confirmation"
                                                autoComplete="off"
                                                required
                                            />
                                        </FormField>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button
                                                    variant="outline"
                                                    type="button"
                                                >
                                                    {t('Cancel')}
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {t('Delete blog')}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </DangerZone>
            )}
        </>
    );
}

BlogGeneral.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'General' }],
};
