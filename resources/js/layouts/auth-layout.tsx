import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppLogo } from '@/components/app/app-logo';
import { FlashToaster } from '@/components/app/flash-toaster';
import { PreferencesMenu } from '@/components/app/preferences-menu';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { home } from '@/routes';

type Props = {
    children: ReactNode;
    /** Translation key. */
    title?: string;
    /** Translation key. */
    description?: string;
};

export default function AuthLayout({ children, title, description }: Props) {
    const { t } = useTranslation();

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-4 md:p-10">
            <div className="absolute top-4 right-4">
                <PreferencesMenu />
            </div>
            <Link href={home()} className="flex items-center">
                <AppLogo />
            </Link>
            <Card className="w-full max-w-sm">
                {(title || description) && (
                    <CardHeader className="text-center">
                        {title && (
                            <CardTitle className="text-xl">
                                {t(title)}
                            </CardTitle>
                        )}
                        {description && (
                            <CardDescription>{t(description)}</CardDescription>
                        )}
                    </CardHeader>
                )}
                <CardContent>{children}</CardContent>
            </Card>
            <FlashToaster />
        </div>
    );
}
