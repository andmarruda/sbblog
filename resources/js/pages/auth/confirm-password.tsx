import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Confirm password')} />
            <Form
                action={store()}
                resetOnSuccess={['password']}
                className="grid gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <FormField
                            id="password"
                            label={t('Password')}
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="current-password"
                                autoFocus
                                required
                            />
                        </FormField>
                        <Button type="submit" disabled={processing}>
                            {t('Confirm')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm your password',
    description:
        'This is a secure area. Please confirm your password to continue.',
};
