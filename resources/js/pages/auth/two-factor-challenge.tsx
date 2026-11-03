import { Form } from '@inertiajs/react';
import { Fingerprint } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { TextLink } from '@/components/app/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { HttpError } from '@/lib/http';
import { authenticate, cancelled, passkeysSupported } from '@/lib/passkeys';
import { login } from '@/routes';
import { passkey as passkeyRoute } from '@/routes/two-factor';
import { store } from '@/routes/two-factor/challenge';
import { options as passkeyOptions } from '@/routes/two-factor/passkey';

type Props = { totp: boolean; passkey: boolean };

export default function TwoFactorChallenge({ totp, passkey }: Props) {
    const { t } = useTranslation();
    const [recovery, setRecovery] = useState(false);
    const [trust, setTrust] = useState(false);
    const [busy, setBusy] = useState(false);
    const canUsePasskey = passkey && passkeysSupported();

    const usePasskey = async () => {
        setBusy(true);

        try {
            const { redirect } = await authenticate<{ redirect: string }>(
                passkeyOptions().url,
                passkeyRoute().url,
                { trust },
            );
            window.location.assign(redirect);
        } catch (error) {
            setBusy(false);

            if (!cancelled(error)) {
                toast.error(
                    error instanceof HttpError
                        ? (error.first() ?? t('Something went wrong.'))
                        : t('The passkey could not be verified. Try again.'),
                );
            }
        }
    };

    const trustDevice = (
        <div className="flex items-center gap-2">
            <Checkbox
                id="trust"
                checked={trust}
                onCheckedChange={(checked) => setTrust(checked === true)}
            />
            <Label htmlFor="trust">{t('Trust this device for 30 days')}</Label>
        </div>
    );

    return (
        <>
            <PageTitle title={t('Two-factor authentication')} />
            <div className="space-y-6">
                {canUsePasskey && (
                    <Button
                        type="button"
                        className="w-full"
                        variant={totp ? 'outline' : 'default'}
                        disabled={busy}
                        onClick={usePasskey}
                    >
                        <Fingerprint /> {t('Use a passkey')}
                    </Button>
                )}

                {totp && (
                    <Form
                        action={store()}
                        transform={(data) => ({ ...data, trust })}
                        resetOnError
                        className="grid gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                {recovery ? (
                                    <FormField
                                        id="recovery_code"
                                        label={t('Recovery code')}
                                        error={errors.recovery_code}
                                    >
                                        <Input
                                            id="recovery_code"
                                            name="recovery_code"
                                            autoComplete="off"
                                            autoFocus
                                            required
                                        />
                                    </FormField>
                                ) : (
                                    <FormField
                                        id="code"
                                        label={t(
                                            'Code from your authenticator app',
                                        )}
                                        error={errors.code}
                                    >
                                        <Input
                                            id="code"
                                            name="code"
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            pattern="[0-9 ]*"
                                            maxLength={7}
                                            autoFocus={!canUsePasskey}
                                            required
                                        />
                                    </FormField>
                                )}
                                {trustDevice}
                                <Button type="submit" disabled={processing}>
                                    {t('Continue')}
                                </Button>
                                <button
                                    type="button"
                                    className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                    onClick={() => setRecovery(!recovery)}
                                >
                                    {recovery
                                        ? t('Use a code from your app')
                                        : t('Use a recovery code')}
                                </button>
                            </>
                        )}
                    </Form>
                )}

                {!totp && canUsePasskey && trustDevice}

                {!totp && !canUsePasskey && (
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'This browser does not support passkeys. Sign in from a device that does.',
                        )}
                    </p>
                )}

                <p className="text-center text-sm text-muted-foreground">
                    <TextLink href={login()}>{t('Back to log in')}</TextLink>
                </p>
            </div>
        </>
    );
}

TwoFactorChallenge.layout = {
    title: 'Two-factor authentication',
    description: 'Confirm it is you to finish signing in.',
};
