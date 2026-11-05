import { Form } from '@inertiajs/react';
import { LoaderCircle, ShieldCheck } from 'lucide-react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/setup';

export default function Setup() {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Set up SBBlog')} />
            <div className="space-y-6">
                <div className="flex gap-3 rounded-lg border bg-muted/50 p-3 text-sm text-muted-foreground">
                    <ShieldCheck className="mt-0.5 size-4 shrink-0 text-foreground" />
                    <p>
                        {t(
                            'This screen appears only once. After you create this account, new people join by invitation.',
                        )}
                    </p>
                </div>
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
                                    className="h-10"
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
                                    placeholder={t('name@example.com')}
                                    className="h-10"
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
                                    className="h-10"
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
                                    className="h-10"
                                    required
                                />
                            </FormField>
                            <Button
                                type="submit"
                                size="lg"
                                className="h-10"
                                disabled={processing}
                            >
                                {processing && (
                                    <LoaderCircle className="animate-spin" />
                                )}
                                {t('Create administrator')}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Setup.layout = {
    title: 'Create the administrator account',
    description:
        'You are setting up a new SBBlog. This account manages the whole instance.',
};
