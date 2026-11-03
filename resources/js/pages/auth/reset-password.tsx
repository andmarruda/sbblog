import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/password';

type Props = {
    token: string;
    email: string;
};

export default function ResetPassword({ token, email }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Reset password')} />
            <Form
                action={store()}
                transform={(data) => ({ ...data, token })}
                resetOnSuccess={['password', 'password_confirmation']}
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
                                defaultValue={email}
                                autoComplete="email"
                                readOnly
                            />
                        </FormField>
                        <FormField
                            id="password"
                            label={t('New password')}
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                autoFocus
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
                            {t('Reset password')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Choose a new password',
};
