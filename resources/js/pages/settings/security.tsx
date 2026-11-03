import { Form, router } from '@inertiajs/react';
import {
    Copy,
    Fingerprint,
    MonitorSmartphone,
    ShieldCheck,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import { HttpError } from '@/lib/http';
import { cancelled, passkeysSupported, register } from '@/lib/passkeys';
import { edit as editProfile } from '@/routes/profile';
import { recoveryCodes as regenerate } from '@/routes/security';
import {
    destroy as destroyDevice,
    destroyAll,
} from '@/routes/security/devices';
import {
    destroy as destroyPasskey,
    options as passkeyOptions,
    store as storePasskey,
} from '@/routes/security/passkeys';
import { confirm, disable, enable } from '@/routes/security/two-factor';

type Props = {
    totp: {
        enabled: boolean;
        setup: { qr_svg: string; secret: string } | null;
        recovery_codes_left: number;
    };
    recoveryCodes: string[] | null;
    passkeys: {
        id: string;
        name: string;
        created_at: string | null;
        last_used_at: string | null;
    }[];
    devices: {
        id: string;
        label: string | null;
        current: boolean;
        created_at: string | null;
        expires_at: string;
    }[];
};

const options = { preserveScroll: true };

export default function Security({
    totp,
    recoveryCodes,
    passkeys,
    devices,
}: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <PageTitle title={t('Security')} />

            <SettingsSection
                title={t('Authenticator app')}
                description={t(
                    'Ask for a code from an app like Google Authenticator or 1Password when you sign in.',
                )}
            >
                {recoveryCodes && <RecoveryCodes codes={recoveryCodes} />}

                {totp.enabled ? (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Badge>
                                <ShieldCheck /> {t('On')}
                            </Badge>
                            <span className="text-sm text-muted-foreground">
                                {t(':count recovery codes left', {
                                    count: totp.recovery_codes_left,
                                })}
                            </span>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline">
                                        {t('New recovery codes')}
                                    </Button>
                                }
                                title={t('Create new recovery codes?')}
                                description={t(
                                    'Your current recovery codes stop working.',
                                )}
                                confirmLabel={t('Create')}
                                onConfirm={() =>
                                    router.post(regenerate(), {}, options)
                                }
                            />
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline">
                                        {t('Remove')}
                                    </Button>
                                }
                                title={t('Remove the authenticator app?')}
                                description={t(
                                    'Sign-ins no longer ask for its codes, and its recovery codes stop working.',
                                )}
                                confirmLabel={t('Remove')}
                                destructive
                                onConfirm={() =>
                                    router.delete(disable(), options)
                                }
                            />
                        </div>
                    </div>
                ) : totp.setup ? (
                    <div className="space-y-4">
                        <p className="text-sm">
                            {t(
                                'Scan this QR code with your app, or type the key, then enter the code it shows.',
                            )}
                        </p>
                        <div
                            className="w-fit rounded-lg border bg-white p-2"
                            // Generated by the server (BaconQrCode), not user input.
                            dangerouslySetInnerHTML={{
                                __html: totp.setup.qr_svg,
                            }}
                        />
                        <code className="block w-fit rounded bg-muted px-2 py-1 font-mono text-sm break-all">
                            {totp.setup.secret}
                        </code>
                        <Form
                            action={confirm()}
                            options={options}
                            resetOnError
                            className="flex flex-wrap items-end gap-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <FormField
                                        id="code"
                                        label={t('Code')}
                                        error={errors.code}
                                    >
                                        <Input
                                            id="code"
                                            name="code"
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            maxLength={7}
                                            className="w-36"
                                            required
                                        />
                                    </FormField>
                                    <Button type="submit" disabled={processing}>
                                        {t('Confirm')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() =>
                                            router.delete(disable(), options)
                                        }
                                    >
                                        {t('Cancel')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                ) : (
                    <Button onClick={() => router.post(enable(), {}, options)}>
                        {t('Set up')}
                    </Button>
                )}
            </SettingsSection>

            <SettingsSection
                title={t('Passkeys')}
                description={t(
                    'Sign in with your fingerprint, face or a security key instead of a password. A passkey also works as the second step.',
                )}
            >
                {passkeys.length > 0 && (
                    <ul className="divide-y rounded-lg border">
                        {passkeys.map((passkey) => (
                            <li
                                key={passkey.id}
                                className="flex items-center gap-3 p-3"
                            >
                                <Fingerprint className="size-5 text-muted-foreground" />
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {passkey.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {passkey.last_used_at
                                            ? t('Last used :date', {
                                                  date: formatDate(
                                                      passkey.last_used_at,
                                                  ),
                                              })
                                            : t('Added :date', {
                                                  date: formatDate(
                                                      passkey.created_at,
                                                  ),
                                              })}
                                    </p>
                                </div>
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm">
                                            {t('Remove')}
                                        </Button>
                                    }
                                    title={t('Remove :name?', {
                                        name: passkey.name,
                                    })}
                                    confirmLabel={t('Remove')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroyPasskey(passkey.id),
                                            options,
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
                <AddPasskey />
            </SettingsSection>

            <SettingsSection
                title={t('Trusted devices')}
                description={t(
                    'Devices where you chose to skip the second step for 30 days.',
                )}
            >
                {devices.length === 0 ? (
                    <EmptyState
                        icon={MonitorSmartphone}
                        title={t('No trusted devices')}
                    />
                ) : (
                    <div className="space-y-3">
                        <ul className="divide-y rounded-lg border">
                            {devices.map((device) => (
                                <li
                                    key={device.id}
                                    className="flex items-center gap-3 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="flex items-center gap-2 font-medium">
                                            {device.label ??
                                                t('Unknown device')}
                                            {device.current && (
                                                <Badge variant="secondary">
                                                    {t('This device')}
                                                </Badge>
                                            )}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {t('Trusted until :date', {
                                                date: formatDate(
                                                    device.expires_at,
                                                ),
                                            })}
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                destroyDevice(device.id),
                                                options,
                                            )
                                        }
                                    >
                                        {t('Remove')}
                                    </Button>
                                </li>
                            ))}
                        </ul>
                        <Button
                            variant="outline"
                            onClick={() => router.delete(destroyAll(), options)}
                        >
                            {t('Remove all')}
                        </Button>
                    </div>
                )}
            </SettingsSection>
        </>
    );
}

function RecoveryCodes({ codes }: { codes: string[] }) {
    const { t } = useTranslation();

    const copy = async () => {
        await navigator.clipboard.writeText(codes.join('\n'));
        toast.success(t('Copied.'));
    };

    return (
        <Alert className="mb-4">
            <ShieldCheck />
            <AlertDescription className="space-y-3">
                <p>
                    {t(
                        'Save these recovery codes somewhere safe. Each one works once, and they are not shown again.',
                    )}
                </p>
                <ul className="grid grid-cols-2 gap-1 font-mono text-sm">
                    {codes.map((code) => (
                        <li key={code}>{code}</li>
                    ))}
                </ul>
                <Button size="sm" variant="outline" onClick={copy}>
                    <Copy /> {t('Copy')}
                </Button>
            </AlertDescription>
        </Alert>
    );
}

function AddPasskey() {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [busy, setBusy] = useState(false);

    if (!passkeysSupported()) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('This browser does not support passkeys.')}
            </p>
        );
    }

    const add = async () => {
        setBusy(true);

        try {
            await register(
                passkeyOptions().url,
                storePasskey().url,
                name.trim(),
            );
            setName('');
            router.reload();
        } catch (error) {
            if (error instanceof HttpError && error.status === 423) {
                router.reload();
            } else if (!cancelled(error)) {
                toast.error(
                    error instanceof HttpError
                        ? (error.first() ?? t('Something went wrong.'))
                        : t('The passkey could not be verified. Try again.'),
                );
            }
        } finally {
            setBusy(false);
        }
    };

    return (
        <form
            className="flex flex-wrap items-end gap-2"
            onSubmit={(event) => {
                event.preventDefault();
                void add();
            }}
        >
            <FormField id="passkey-name" label={t('Name')}>
                <Input
                    id="passkey-name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder={t('e.g. MacBook, YubiKey')}
                    maxLength={64}
                    className="w-56"
                    required
                />
            </FormField>
            <Button type="submit" disabled={busy || name.trim() === ''}>
                <Fingerprint /> {t('Add a passkey')}
            </Button>
        </form>
    );
}

Security.layout = {
    breadcrumbs: [
        { title: 'Account settings', href: editProfile().url },
        { title: 'Security' },
    ],
};
