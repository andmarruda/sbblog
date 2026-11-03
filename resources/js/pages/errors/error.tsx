import { Head, Link } from '@inertiajs/react';
import { PreferencesMenu } from '@/components/app/preferences-menu';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { home } from '@/routes';

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Forbidden',
        description: "You don't have permission to access this page.",
    },
    404: {
        title: 'Page not found',
        description: "The page you're looking for doesn't exist or was moved.",
    },
    500: {
        title: 'Server error',
        description: 'Something went wrong on our side. Please try again.',
    },
    503: {
        title: 'Under maintenance',
        description: "We're doing some maintenance. Please check back soon.",
    },
};

export default function Error({
    status,
    reason,
}: {
    status: number;
    reason?: string | null;
}) {
    const { t } = useTranslation();
    const message = messages[status] ?? messages[500];

    return (
        <>
            <Head title={t(message.title)} />
            <div className="relative flex min-h-svh flex-col items-center justify-center gap-4 p-6 text-center">
                <div className="absolute top-4 right-4">
                    <PreferencesMenu />
                </div>
                <p className="text-sm font-medium text-muted-foreground">
                    {status}
                </p>
                <h1 className="text-3xl font-semibold tracking-tight">
                    {t(message.title)}
                </h1>
                <p className="max-w-md text-muted-foreground">
                    {reason ?? t(message.description)}
                </p>
                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        {t('Go back')}
                    </Button>
                    <Button asChild>
                        <Link href={home()}>{t('Home')}</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
