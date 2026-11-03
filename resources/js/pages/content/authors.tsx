import { Form, router } from '@inertiajs/react';
import { ImageIcon, Pencil, Plus, Trash2, UserRound } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { SlugInput } from '@/components/app/slug-input';
import { MediaPicker } from '@/components/content/media-picker';
import {
    SocialLinksFields,
    type SocialNetwork,
} from '@/components/content/social-links-fields';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import { initials } from '@/components/app/nav-user';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, store, update } from '@/routes/authors';

type Author = {
    id: string;
    display_name: string;
    slug: string;
    bio: string | null;
    user: { id: string; name: string; email: string } | null;
    avatar: { id: string; url: string } | null;
    articles_count: number;
    social_links: Record<string, string>;
};

type Props = {
    authors: Author[];
    members: { id: string; name: string; email: string }[];
    networks: SocialNetwork[];
};

const GUEST = 'guest';

function AuthorDialog({
    author,
    members,
    networks,
    onClose,
}: {
    author: Author | null;
    members: Props['members'];
    networks: SocialNetwork[];
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [name, setName] = useState(author?.display_name ?? '');
    const [slug, setSlug] = useState(author?.slug ?? '');
    const [userId, setUserId] = useState(author?.user?.id ?? GUEST);
    const [avatar, setAvatar] = useState(author?.avatar ?? null);
    const [links, setLinks] = useState<Record<string, string>>(
        author?.social_links ?? {},
    );

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {author ? t('Edit author') : t('New author')}
                    </DialogTitle>
                </DialogHeader>
                <Form
                    action={
                        author
                            ? update({ ...args, authorProfile: author.id })
                            : store(args)
                    }
                    transform={(data) => ({
                        ...data,
                        user_id: userId === GUEST ? null : userId,
                        avatar_media_id: avatar?.id ?? null,
                        social_links: links,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex items-center gap-4">
                                <Avatar className="size-16">
                                    {avatar && (
                                        <AvatarImage src={avatar.url} alt="" />
                                    )}
                                    <AvatarFallback className="text-foreground">
                                        {initials(name || '?')}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex gap-2">
                                    <MediaPicker
                                        trigger={
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                            >
                                                <ImageIcon />
                                                {t('Choose photo')}
                                            </Button>
                                        }
                                        onSelect={(media) =>
                                            setAvatar({
                                                id: media.id,
                                                url: media.thumb_url,
                                            })
                                        }
                                    />
                                    {avatar && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setAvatar(null)}
                                        >
                                            {t('Remove')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <FormField
                                id="author-name"
                                label={t('Display name')}
                                error={errors.display_name}
                            >
                                <Input
                                    id="author-name"
                                    name="display_name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    required
                                />
                            </FormField>
                            <FormField
                                id="author-slug"
                                label={t('Address')}
                                error={errors.slug}
                            >
                                <SlugInput
                                    id="author-slug"
                                    name="slug"
                                    value={slug}
                                    onChange={setSlug}
                                    source={name}
                                />
                            </FormField>
                            <FormField
                                id="author-user"
                                label={t('Account')}
                                hint={t(
                                    'Guest authors write without an account.',
                                )}
                                error={errors.user_id}
                            >
                                <Select
                                    value={userId}
                                    onValueChange={setUserId}
                                >
                                    <SelectTrigger
                                        id="author-user"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={GUEST}>
                                            {t('Guest author')}
                                        </SelectItem>
                                        {members.map((member) => (
                                            <SelectItem
                                                key={member.id}
                                                value={member.id}
                                            >
                                                {member.name} ({member.email})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                id="author-bio"
                                label={t('Bio')}
                                error={errors.bio}
                            >
                                <Textarea
                                    id="author-bio"
                                    name="bio"
                                    defaultValue={author?.bio ?? ''}
                                    rows={4}
                                    maxLength={2000}
                                />
                            </FormField>
                            <div className="grid gap-2">
                                <p className="text-sm font-medium">
                                    {t('Social links')}
                                </p>
                                <SocialLinksFields
                                    networks={networks}
                                    value={links}
                                    onChange={setLinks}
                                    errors={errors}
                                    errorKey="social_links"
                                />
                            </div>
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

export default function Authors({ authors, members, networks }: Props) {
    const { t, tc } = useTranslation();
    const args = useBlogRoute();
    const [editing, setEditing] = useState<Author | null | undefined>(
        undefined,
    );

    return (
        <>
            <PageHeader
                title={t('Authors')}
                description={t(
                    'Who signs the articles. Authors can be members or guests.',
                )}
                actions={
                    <Button onClick={() => setEditing(null)}>
                        <Plus />
                        {t('New author')}
                    </Button>
                }
            />
            {authors.length === 0 ? (
                <EmptyState
                    icon={UserRound}
                    title={t('No authors yet')}
                    description={t(
                        'A profile is created automatically when a member writes their first article.',
                    )}
                />
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2">
                    {authors.map((author) => (
                        <li
                            key={author.id}
                            className="flex items-start gap-3 rounded-lg border p-4"
                        >
                            <Avatar className="size-12">
                                {author.avatar && (
                                    <AvatarImage
                                        src={author.avatar.url}
                                        alt=""
                                    />
                                )}
                                <AvatarFallback className="text-foreground">
                                    {initials(author.display_name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-medium">
                                        {author.display_name}
                                    </p>
                                    {!author.user && (
                                        <Badge variant="outline">
                                            {t('Guest')}
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    /{author.slug} ·{' '}
                                    {tc(
                                        ':count article|:count articles',
                                        author.articles_count,
                                    )}
                                </p>
                                {author.bio && (
                                    <p className="mt-1 line-clamp-2 text-sm">
                                        {author.bio}
                                    </p>
                                )}
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Edit')}
                                    onClick={() => setEditing(author)}
                                >
                                    <Pencil />
                                </Button>
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Delete')}
                                            disabled={author.articles_count > 0}
                                        >
                                            <Trash2 />
                                        </Button>
                                    }
                                    title={t('Delete :name?', {
                                        name: author.display_name,
                                    })}
                                    confirmLabel={t('Delete')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroy({
                                                ...args,
                                                authorProfile: author.id,
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
            {editing !== undefined && (
                <AuthorDialog
                    key={editing?.id ?? 'new'}
                    author={editing}
                    members={members}
                    networks={networks}
                    onClose={() => setEditing(undefined)}
                />
            )}
        </>
    );
}

Authors.layout = {
    breadcrumbs: [{ title: 'Authors' }],
};
