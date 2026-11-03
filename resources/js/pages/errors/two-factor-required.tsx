import { Head, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editProfile } from '@/routes/profile';

export default function TwoFactorRequired() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Two-factor authentication required')} />
            <div className="flex min-h-svh flex-col items-center justify-center gap-4 p-6 text-center">
                <ShieldAlert className="size-10 text-muted-foreground" />
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('Two-factor authentication required')}
                </h1>
                <p className="max-w-md text-muted-foreground">
                    {t(
                        'This workspace requires two-factor authentication. Turn it on in your account to continue.',
                    )}
                </p>
                <Button asChild>
                    <Link href={editProfile()}>{t('Account settings')}</Link>
                </Button>
            </div>
        </>
    );
}
