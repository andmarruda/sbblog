import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { index as sessions } from '@/routes/sessions';
import { edit as editPassword } from '@/routes/user-password';

const items = [
    { title: 'Profile', href: editProfile().url },
    { title: 'Password', href: editPassword().url },
    { title: 'Sessions', href: sessions().url },
];

export default function SettingsLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();
    const { url } = usePage();

    return (
        <div className="space-y-6">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('Account settings')}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {t('Manage your profile, password and sessions.')}
                </p>
            </div>
            <Separator />
            <div className="flex flex-col gap-8 lg:flex-row">
                <nav className="flex gap-1 overflow-x-auto lg:w-48 lg:flex-col">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            prefetch
                            className={cn(
                                'rounded-md px-3 py-2 text-sm whitespace-nowrap transition-colors hover:bg-muted',
                                url.startsWith(item.href) &&
                                    'bg-muted font-medium',
                            )}
                        >
                            {t(item.title)}
                        </Link>
                    ))}
                </nav>
                <div className="max-w-2xl flex-1 space-y-10">{children}</div>
            </div>
        </div>
    );
}
