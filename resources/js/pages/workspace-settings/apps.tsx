import { Form, router, usePage } from '@inertiajs/react';
import { KeyRound, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SecretAlert } from '@/components/app/secret-alert';
import { SettingsSection } from '@/components/app/settings-section';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, secret, store } from '@/routes/workspaces/settings/apps';

type Props = {
    apps: {
        id: string;
        name: string;
        client_id: string;
        redirect_uris: string[];
        requested_scopes: string[];
        installations_count: number;
    }[];
    scopes: Record<string, string>;
    authorizeUrl: string;
    tokenUrl: string;
    newSecret: { app: string; secret: string } | null;
};

export default function Apps({
    apps,
    scopes,
    authorizeUrl,
    tokenUrl,
    newSecret,
}: Props) {
    const { t, tc } = useTranslation();
    const { currentWorkspace } = usePage().props;
    const workspace = currentWorkspace?.slug ?? '';
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<string[]>(['articles:read']);

    return (
        <>
            <PageTitle title={t('Apps')} />
            <SettingsSection
                title={t('Apps')}
                description={t(
                    'Apps you build on the API. Blogs install them through :url.',
                    { url: authorizeUrl },
                )}
            >
                {newSecret && (
                    <SecretAlert
                        label={t(
                            'Client secret. Copy it now: it will not be shown again.',
                        )}
                        value={newSecret.secret}
                    />
                )}
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button className="w-fit">
                            <Plus />
                            {t('New app')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t('New app')}</DialogTitle>
                        </DialogHeader>
                        <Form
                            action={store(workspace)}
                            transform={(data) => ({
                                ...data,
                                requested_scopes: selected,
                            })}
                            onSuccess={() => setOpen(false)}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <FormField
                                        id="app-name"
                                        label={t('Name')}
                                        error={errors.name}
                                    >
                                        <Input
                                            id="app-name"
                                            name="name"
                                            required
                                        />
                                    </FormField>
                                    <FormField
                                        id="app-redirects"
                                        label={t('Redirect addresses')}
                                        hint={t(
                                            'One per line. Codes are only sent to these.',
                                        )}
                                        error={
                                            errors.redirect_uris ??
                                            errors['redirect_uris.0']
                                        }
                                    >
                                        <Textarea
                                            id="app-redirects"
                                            name="redirect_uris_text"
                                            rows={3}
                                            placeholder="https://"
                                            required
                                        />
                                    </FormField>
                                    <fieldset className="grid gap-2">
                                        <legend className="mb-1 text-sm font-medium">
                                            {t('Scopes')}
                                        </legend>
                                        {Object.entries(scopes).map(
                                            ([key, description]) => (
                                                <div
                                                    key={key}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={`scope-${key}`}
                                                        checked={selected.includes(
                                                            key,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setSelected(
                                                                (current) =>
                                                                    checked ===
                                                                    true
                                                                        ? [
                                                                              ...current,
                                                                              key,
                                                                          ]
                                                                        : current.filter(
                                                                              (
                                                                                  value,
                                                                              ) =>
                                                                                  value !==
                                                                                  key,
                                                                          ),
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`scope-${key}`}
                                                        className="font-normal"
                                                    >
                                                        {t(description)}{' '}
                                                        <code className="text-xs text-muted-foreground">
                                                            {key}
                                                        </code>
                                                    </Label>
                                                </div>
                                            ),
                                        )}
                                        {errors.requested_scopes && (
                                            <p className="text-sm text-destructive">
                                                {errors.requested_scopes}
                                            </p>
                                        )}
                                    </fieldset>
                                    <DialogFooter>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {t('Create app')}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>

                {apps.length === 0 ? (
                    <EmptyState
                        title={t('No apps yet')}
                        description={t('Token endpoint: :url', {
                            url: tokenUrl,
                        })}
                    />
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {apps.map((app) => (
                            <li
                                key={app.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">{app.name}</p>
                                    <p className="font-mono text-xs text-muted-foreground">
                                        {app.client_id}
                                    </p>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {app.requested_scopes.map((scope) => (
                                            <Badge
                                                key={scope}
                                                variant="outline"
                                            >
                                                {scope}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {tc(
                                        ':count blog|:count blogs',
                                        app.installations_count,
                                    )}
                                </span>
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="ghost" size="sm">
                                            <KeyRound />
                                            {t('New secret')}
                                        </Button>
                                    }
                                    title={t('Replace the client secret?')}
                                    description={t(
                                        'The current secret stops working at once.',
                                    )}
                                    confirmLabel={t('Replace')}
                                    onConfirm={() =>
                                        router.post(
                                            secret({ workspace, app: app.id }),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                />
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
                                        name: app.name,
                                    })}
                                    description={t(
                                        'It is removed from every blog that installed it.',
                                    )}
                                    confirmLabel={t('Delete')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroy({ workspace, app: app.id }),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </SettingsSection>
        </>
    );
}

Apps.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'Apps' }],
};
