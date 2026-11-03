import { Form } from '@inertiajs/react';
import { Check, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { PageTitle } from '@/components/app/page-title';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { approve } from '@/routes/oauth';

type Props = {
    app: { name: string; first_party: boolean };
    scopes: { key: string; description: string }[];
    blogs: { id: string; name: string; workspace: string }[];
    request: Record<string, string>;
    redirectHost: string | null;
};

export default function Authorize({
    app,
    scopes,
    blogs,
    request,
    redirectHost,
}: Props) {
    const { t } = useTranslation();
    const [blog, setBlog] = useState(blogs[0]?.id ?? '');

    return (
        <>
            <PageTitle title={t('Connect :app', { app: app.name })} />
            <div className="space-y-5">
                <p className="text-center">
                    {t(':app wants access to one of your blogs.', {
                        app: app.name,
                    })}
                </p>
                {app.first_party && (
                    <p className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                        <ShieldCheck className="size-4" />
                        {t('Made by the SBBlog team')}
                    </p>
                )}
                <ul className="space-y-2 rounded-lg border p-3 text-sm">
                    {scopes.map((scope) => (
                        <li key={scope.key} className="flex gap-2">
                            <Check className="mt-0.5 size-4 shrink-0" />
                            {t(scope.description)}
                        </li>
                    ))}
                    <li className="text-muted-foreground">
                        {t(
                            'It can never publish: people review everything it writes.',
                        )}
                    </li>
                </ul>
                {blogs.length === 0 ? (
                    <Alert>
                        <AlertDescription>
                            {t("You can't install apps on any blog.")}
                        </AlertDescription>
                    </Alert>
                ) : (
                    <Select value={blog} onValueChange={setBlog}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Blog')}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {blogs.map((option) => (
                                <SelectItem key={option.id} value={option.id}>
                                    {option.workspace} · {option.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                <p className="text-center text-xs text-muted-foreground">
                    {t('You will be sent back to :host.', {
                        host: redirectHost ?? '',
                    })}
                </p>
                <div className="grid grid-cols-2 gap-2">
                    <Form
                        action={approve()}
                        transform={() => ({ ...request, approve: false })}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                className="w-full"
                                disabled={processing}
                            >
                                {t('Cancel')}
                            </Button>
                        )}
                    </Form>
                    <Form
                        action={approve()}
                        transform={() => ({
                            ...request,
                            approve: true,
                            blog_id: blog,
                        })}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing || blog === ''}
                            >
                                {t('Allow')}
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

Authorize.layout = {
    title: 'Connect an app',
};
