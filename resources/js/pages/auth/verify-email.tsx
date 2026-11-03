import { Form, Link } from '@inertiajs/react';
import { StatusMessage } from '@/components/app/status-message';
import { PageTitle } from '@/components/app/page-title';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Verify email')} />
            <div className="space-y-6">
                {status === 'verification-link-sent' && (
                    <StatusMessage
                        message={t(
                            'A new verification link has been sent to your email.',
                        )}
                    />
                )}
                <Form action={send()} className="grid gap-4">
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            {t('Resend verification email')}
                        </Button>
                    )}
                </Form>
                <Button variant="ghost" className="w-full" asChild>
                    <Link href={logout()} as="button">
                        {t('Log out')}
                    </Link>
                </Button>
            </div>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify your email',
    description:
        'We sent you a link. Click it to confirm your address, or ask for a new one below.',
};
