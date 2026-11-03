import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { TextLink } from '@/components/app/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Sign up')} />
            <div className="space-y-6">
                <Form
                    action={store()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    autoComplete="name"
                                    autoFocus
                                    required
                                />
                            </FormField>
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
                                    required
                                />
                            </FormField>
                            <FormField
                                id="password"
                                label={t('Password')}
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="new-password"
                                    required
                                />
                            </FormField>
                            <FormField
                                id="password_confirmation"
                                label={t('Confirm password')}
                                error={errors.password_confirmation}
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    required
                                />
                            </FormField>
                            <Button type="submit" disabled={processing}>
                                {t('Create account')}
                            </Button>
                        </>
                    )}
                </Form>
                <p className="text-center text-sm text-muted-foreground">
                    {t('Already have an account?')}{' '}
                    <TextLink href={login()}>{t('Log in')}</TextLink>
                </p>
            </div>
        </>
    );
}

Register.layout = {
    title: 'Create your account',
    description: 'Start writing in a few seconds.',
};
