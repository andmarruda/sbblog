import { Form, Link, router, usePage } from '@inertiajs/react';
import { ArchiveRestore, BookOpen, Plus } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { SlugInput } from '@/components/app/slug-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard as blogDashboard } from '@/routes/blogs';
import { restore, store } from '@/routes/workspaces/settings/blogs';

type Language = { id: string; code: string; label: string };

type Props = {
    blogs: {
        id: string;
        name: string;
        slug: string;
        articles_count: number;
        default_language: Language | null;
    }[];
    trashed: { id: string; name: string; slug: string; deleted_at: string }[];
    languages: Language[];
    defaultLanguageId: string | null;
};

export default function WorkspaceBlogs({
    blogs,
    trashed,
    languages,
    defaultLanguageId,
}: Props) {
    const { t, tc, formatDate } = useTranslation();
    const can = useCan();
    const { currentWorkspace } = usePage().props;
    const workspace = currentWorkspace?.slug ?? '';
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');

    const createButton = can('blogs.create') && (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    {t('New blog')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('New blog')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'You can change everything later in the blog settings.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={store(workspace)}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="blog-name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="blog-name"
                                    name="name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    required
                                />
                            </FormField>
                            <FormField
                                id="blog-slug"
                                label={t('Address')}
                                error={errors.slug}
                            >
                                <SlugInput
                                    id="blog-slug"
                                    name="slug"
                                    value={slug}
                                    onChange={setSlug}
                                    source={name}
                                />
                            </FormField>
                            <FormField
                                id="blog-language"
                                label={t('Main language')}
                                error={errors.language_id}
                            >
                                <Select
                                    name="language_id"
                                    defaultValue={
                                        defaultLanguageId ?? undefined
                                    }
                                >
                                    <SelectTrigger
                                        id="blog-language"
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={t('Choose a language')}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {languages.map((language) => (
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
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {t('Create blog')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );

    return (
        <>
            <PageTitle title={t('Blogs')} />
            <SettingsSection
                title={t('Blogs')}
                description={t('Blogs in this workspace that you can open.')}
            >
                {createButton && <div>{createButton}</div>}
                {blogs.length === 0 ? (
                    <EmptyState icon={BookOpen} title={t('No blogs yet')} />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {blogs.map((blog) => (
                            <Link
                                key={blog.id}
                                href={blogDashboard({
                                    workspace,
                                    blog: blog.slug,
                                })}
                                className="group"
                            >
                                <Card className="transition-colors group-hover:bg-muted/50">
                                    <CardHeader>
                                        <CardTitle>{blog.name}</CardTitle>
                                        <CardDescription>
                                            /{blog.slug} ·{' '}
                                            {blog.default_language?.label} ·{' '}
                                            {tc(
                                                ':count article|:count articles',
                                                blog.articles_count,
                                            )}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </SettingsSection>

            {trashed.length > 0 && (
                <SettingsSection
                    title={t('Deleted blogs')}
                    description={t(
                        'Restore a blog to make it reachable again.',
                    )}
                >
                    <ul className="divide-y rounded-lg border">
                        {trashed.map((blog) => (
                            <li
                                key={blog.id}
                                className="flex items-center justify-between gap-4 p-4 text-sm"
                            >
                                <div>
                                    <p className="font-medium">{blog.name}</p>
                                    <p className="text-muted-foreground">
                                        {t('Deleted on :date', {
                                            date: formatDate(blog.deleted_at),
                                        })}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            restore({
                                                workspace,
                                                blogId: blog.id,
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
        </>
    );
}

WorkspaceBlogs.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'Blogs' }],
};
