import { Form, Link, router } from '@inertiajs/react';
import {
    Copy,
    KeyRound,
    ListTree,
    Plus,
    RefreshCw,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { SettingsSection } from '@/components/app/settings-section';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { Switch } from '@/components/ui/switch';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { events } from '@/routes/publishing';
import {
    destroy as destroyFrontend,
    store as storeFrontend,
    token,
} from '@/routes/publishing/frontends';
import { store as buildSnapshot } from '@/routes/publishing/snapshots';
import {
    destroy as destroyTransport,
    store as storeTransport,
    test,
    update as updateTransport,
} from '@/routes/publishing/transports';

type Props = {
    latestSequence: number;
    frontends: {
        id: string;
        name: string;
        base_url: string;
        store_adapter: string;
        last_acked_sequence: number;
        behind: number;
        last_sync_at: string | null;
        core_version: string | null;
    }[];
    snapshots: {
        id: string;
        up_to_sequence: number;
        size_bytes: number;
        created_at: string | null;
    }[];
    transports: {
        id: string;
        driver: string;
        url: string | null;
        enabled: boolean;
        last_dispatched_sequence: number;
        behind: number;
        last_error: string | null;
        last_error_at: string | null;
    }[];
    storeAdapters: string[];
    syncUrl: string;
    newToken: { frontend?: string; transport?: string; token: string } | null;
};

function Secret({ label, value }: { label: string; value: string }) {
    const { t } = useTranslation();

    return (
        <Alert>
            <KeyRound />
            <AlertDescription className="space-y-2">
                <p>{label}</p>
                <div className="flex gap-2">
                    <code className="flex-1 overflow-x-auto rounded bg-muted px-2 py-1 text-xs">
                        {value}
                    </code>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                            void navigator.clipboard.writeText(value);
                            toast.success(t('Copied.'));
                        }}
                    >
                        <Copy />
                        {t('Copy')}
                    </Button>
                </div>
            </AlertDescription>
        </Alert>
    );
}

export default function Publishing({
    latestSequence,
    frontends,
    snapshots,
    transports,
    storeAdapters,
    syncUrl,
    newToken,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const args = useBlogRoute();
    const [adapter, setAdapter] = useState(storeAdapters[0] ?? 'sqlite');
    const [adding, setAdding] = useState(false);

    return (
        <>
            <PageHeader
                title={t('Publishing')}
                description={t(
                    'Frontends read this blog from its event log. :count events so far.',
                    { count: formatNumber(latestSequence) },
                )}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={events(args)}>
                            <ListTree />
                            {t('Event log')}
                        </Link>
                    </Button>
                }
            />

            {newToken?.frontend && (
                <Secret
                    label={t(
                        'Sync token for the new frontend. Copy it now: it will not be shown again.',
                    )}
                    value={newToken.token}
                />
            )}
            {newToken?.transport && (
                <Secret
                    label={t(
                        'Webhook signing secret. Copy it now: it will not be shown again.',
                    )}
                    value={newToken.token}
                />
            )}

            <SettingsSection
                title={t('Frontends')}
                description={t(
                    'Sites that render this blog. They pull :url with their token.',
                    { url: syncUrl },
                )}
            >
                <Dialog open={adding} onOpenChange={setAdding}>
                    <DialogTrigger asChild>
                        <Button className="w-fit">
                            <Plus />
                            {t('Add frontend')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t('Add frontend')}</DialogTitle>
                        </DialogHeader>
                        <Form
                            action={storeFrontend(args)}
                            transform={(data) => ({
                                ...data,
                                store_adapter: adapter,
                            })}
                            options={{ preserveScroll: true }}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <FormField
                                        id="frontend-name"
                                        label={t('Name')}
                                        error={errors.name}
                                    >
                                        <Input
                                            id="frontend-name"
                                            name="name"
                                            required
                                        />
                                    </FormField>
                                    <FormField
                                        id="frontend-url"
                                        label={t('Address')}
                                        error={errors.base_url}
                                    >
                                        <Input
                                            id="frontend-url"
                                            name="base_url"
                                            type="url"
                                            placeholder="https://"
                                            required
                                        />
                                    </FormField>
                                    <FormField
                                        id="frontend-adapter"
                                        label={t('Storage')}
                                        error={errors.store_adapter}
                                    >
                                        <Select
                                            value={adapter}
                                            onValueChange={setAdapter}
                                        >
                                            <SelectTrigger
                                                id="frontend-adapter"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {storeAdapters.map((option) => (
                                                    <SelectItem
                                                        key={option}
                                                        value={option}
                                                    >
                                                        {option}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </FormField>
                                    <DialogFooter>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {t('Add frontend')}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
                {frontends.length === 0 ? (
                    <EmptyState
                        title={t('No frontends yet')}
                        description={t(
                            'Add the site that will show this blog to readers.',
                        )}
                    />
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {frontends.map((frontend) => (
                            <li
                                key={frontend.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {frontend.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {[
                                            frontend.base_url,
                                            frontend.store_adapter,
                                            frontend.core_version &&
                                                `core ${frontend.core_version}`,
                                            frontend.last_sync_at
                                                ? t('Last sync :date', {
                                                      date: formatDate(
                                                          frontend.last_sync_at,
                                                          true,
                                                      ),
                                                  })
                                                : t('Never synced'),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                {frontend.behind === 0 ? (
                                    <Badge variant="secondary">
                                        {t('Up to date')}
                                    </Badge>
                                ) : (
                                    <Badge variant="destructive">
                                        {t(':count events behind', {
                                            count: formatNumber(
                                                frontend.behind,
                                            ),
                                        })}
                                    </Badge>
                                )}
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="ghost" size="sm">
                                            <RefreshCw />
                                            {t('New token')}
                                        </Button>
                                    }
                                    title={t('Replace the sync token?')}
                                    description={t(
                                        'The current token stops working at once. Update the frontend with the new one.',
                                    )}
                                    confirmLabel={t('Replace')}
                                    onConfirm={() =>
                                        router.post(
                                            token({
                                                ...args,
                                                frontend: frontend.id,
                                            }),
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
                                    title={t('Remove :name?', {
                                        name: frontend.name,
                                    })}
                                    confirmLabel={t('Remove')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroyFrontend({
                                                ...args,
                                                frontend: frontend.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </SettingsSection>

            <SettingsSection
                title={t('Snapshots')}
                description={t(
                    'New frontends load the latest snapshot, then the events after it. Built every night.',
                )}
            >
                <Button
                    variant="outline"
                    className="w-fit"
                    onClick={() =>
                        router.post(
                            buildSnapshot(args),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    {t('Build snapshot now')}
                </Button>
                {snapshots.length > 0 && (
                    <ul className="divide-y rounded-lg border text-sm">
                        {snapshots.map((snapshot) => (
                            <li
                                key={snapshot.id}
                                className="flex justify-between gap-2 p-3"
                            >
                                <span>
                                    {t('Up to event :sequence', {
                                        sequence: formatNumber(
                                            snapshot.up_to_sequence,
                                        ),
                                    })}
                                </span>
                                <span className="text-muted-foreground">
                                    {formatDate(snapshot.created_at, true)} ·{' '}
                                    {formatNumber(
                                        Math.round(snapshot.size_bytes / 1024),
                                    )}{' '}
                                    KB
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </SettingsSection>

            <SettingsSection
                title={t('Webhooks')}
                description={t(
                    'Push every new event to a URL, signed with HMAC-SHA256. Kafka, NATS and Redis are coming soon.',
                )}
            >
                <Form
                    action={storeTransport(args)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="flex flex-col gap-2 sm:flex-row"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex-1">
                                <Input
                                    name="url"
                                    type="url"
                                    placeholder="https://example.com/sbblog-webhook"
                                    aria-label={t('Webhook URL')}
                                    aria-invalid={errors.url ? true : undefined}
                                    required
                                />
                                {errors.url && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.url}
                                    </p>
                                )}
                            </div>
                            <Button type="submit" disabled={processing}>
                                <Plus />
                                {t('Add webhook')}
                            </Button>
                        </>
                    )}
                </Form>
                {transports.length > 0 && (
                    <ul className="divide-y rounded-lg border">
                        {transports.map((transport) => (
                            <li key={transport.id} className="space-y-2 p-3">
                                <div className="flex flex-wrap items-center gap-3">
                                    <p className="min-w-0 flex-1 truncate font-mono text-sm">
                                        {transport.url}
                                    </p>
                                    {transport.behind > 0 && (
                                        <Badge variant="outline">
                                            {t(':count events behind', {
                                                count: formatNumber(
                                                    transport.behind,
                                                ),
                                            })}
                                        </Badge>
                                    )}
                                    <Switch
                                        checked={transport.enabled}
                                        aria-label={t('Enabled')}
                                        onCheckedChange={(enabled) =>
                                            router.patch(
                                                updateTransport({
                                                    ...args,
                                                    eventTransport:
                                                        transport.id,
                                                }),
                                                { enabled },
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            router.post(
                                                test({
                                                    ...args,
                                                    eventTransport:
                                                        transport.id,
                                                }),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Send />
                                        {t('Send test')}
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
                                        title={t('Remove this webhook?')}
                                        confirmLabel={t('Remove')}
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                destroyTransport({
                                                    ...args,
                                                    eventTransport:
                                                        transport.id,
                                                }),
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </div>
                                {transport.last_error && (
                                    <p className="text-sm text-destructive">
                                        {formatDate(
                                            transport.last_error_at,
                                            true,
                                        )}
                                        : {transport.last_error}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </SettingsSection>
        </>
    );
}

Publishing.layout = {
    breadcrumbs: [{ title: 'Publishing' }],
};
