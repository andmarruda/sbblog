import { Form } from '@inertiajs/react';
import { Fingerprint, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { FormField } from '@/components/app/form-field';
import { PasswordInput } from '@/components/app/password-input';
import { StatusMessage } from '@/components/app/status-message';
import { TextLink } from '@/components/app/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageTitle } from '@/components/app/page-title';
import { useTranslation } from '@/hooks/use-translation';
import { HttpError } from '@/lib/http';
import { authenticate, cancelled, passkeysSupported } from '@/lib/passkeys';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { login as passkeyLogin } from '@/routes/passkeys';
import { options as passkeyLoginOptions } from '@/routes/passkeys/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    /** Sign-up is invite-only: true after opening an invitation link. */
    canRegister: boolean;
};

export default function Login({ status, canRegister }: Props) {
    const { t } = useTranslation();
    const [busy, setBusy] = useState(false);

    const signInWithPasskey = async () => {
        setBusy(true);

        try {
            const { redirect } = await authenticate<{ redirect: string }>(
                passkeyLoginOptions().url,
                passkeyLogin().url,
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

    return (
        <>
            <PageTitle title={t('Log in')} />
            <div className="space-y-6">
                <StatusMessage message={status} />
                <Form
                    action={store()}
                    resetOnSuccess={['password']}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="email"
                                label={t('Email')}
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    placeholder={t('name@example.com')}
                                    className="h-10"
                                    autoFocus
                                    required
                                />
                            </FormField>
                            <div className="grid gap-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password">
                                        {t('Password')}
                                    </Label>
                                    <TextLink
                                        href={request()}
                                        className="text-sm"
                                    >
                                        {t('Forgot your password?')}
                                    </TextLink>
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="current-password"
                                    className="h-10"
                                    required
                                />
                                {errors.password && (
                                    <p className="text-sm text-destructive">
                                        {errors.password}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    value="1"
                                />
                                <Label htmlFor="remember">
                                    {t('Remember me')}
                                </Label>
                            </div>
                            <Button
                                type="submit"
                                size="lg"
                                className="h-10"
                                disabled={processing}
                            >
                                {processing && (
                                    <LoaderCircle className="animate-spin" />
                                )}
                                {t('Log in')}
                            </Button>
                        </>
                    )}
                </Form>
                {passkeysSupported() && (
                    <>
                        <div className="flex items-center gap-3 text-xs text-muted-foreground uppercase">
                            <span className="h-px flex-1 bg-border" />
                            {t('or')}
                            <span className="h-px flex-1 bg-border" />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            className="h-10 w-full"
                            disabled={busy}
                            onClick={signInWithPasskey}
                        >
                            <Fingerprint /> {t('Sign in with a passkey')}
                        </Button>
                    </>
                )}
                {canRegister && (
                    <p className="text-center text-sm text-muted-foreground">
                        {t("Don't have an account?")}{' '}
                        <TextLink href={register()}>{t('Sign up')}</TextLink>
                    </p>
                )}
            </div>
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password to continue.',
};
