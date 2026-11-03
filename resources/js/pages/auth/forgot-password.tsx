import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { StatusMessage } from '@/components/app/status-message';
import { TextLink } from '@/components/app/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Forgot password')} />
            <div className="space-y-6">
                <StatusMessage message={status} />
                <Form action={email()} className="grid gap-4">
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
                                    autoFocus
                                    required
                                />
                            </FormField>
                            <Button type="submit" disabled={processing}>
                                {t('Email password reset link')}
                            </Button>
                        </>
                    )}
                </Form>
                <p className="text-center text-sm text-muted-foreground">
                    {t('Remembered it?')}{' '}
                    <TextLink href={login()}>{t('Log in')}</TextLink>
                </p>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot your password?',
    description:
        "Enter your email and we'll send you a link to choose a new one.",
};
