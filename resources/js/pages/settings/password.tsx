import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editProfile } from '@/routes/profile';
import { update } from '@/routes/user-password';

export default function Password() {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Password')} />
            <SettingsSection
                title={t('Change password')}
                description={t(
                    'Other browsers signed in to your account will be logged out.',
                )}
            >
                <Form
                    action={update()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="current_password"
                                label={t('Current password')}
                                error={errors.current_password}
                            >
                                <PasswordInput
                                    id="current_password"
                                    name="current_password"
                                    autoComplete="current-password"
                                    required
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
                            <div>
                                <Button type="submit" disabled={processing}>
                                    {t('Update password')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </SettingsSection>
        </>
    );
}

Password.layout = {
    breadcrumbs: [
        { title: 'Account settings', href: editProfile().url },
        { title: 'Password' },
    ],
};
