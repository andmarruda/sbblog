import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export type SectionNavItem = {
    /** Translation key. */
    title: string;
    href: string;
};

type Props = {
    /** Already translated. */
    title: string;
    description?: string;
    items: SectionNavItem[];
    children: ReactNode;
};

/**
 * A settings area: heading, side navigation and content.
 */
export function SectionLayout({ title, description, items, children }: Props) {
    const { t } = useTranslation();
    const { url } = usePage();
    const path = url.split('?')[0];

    return (
        <div className="space-y-6">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <Separator />
            <div className="flex flex-col gap-8 lg:flex-row">
                <nav className="flex gap-1 overflow-x-auto lg:w-48 lg:shrink-0 lg:flex-col">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            prefetch
                            className={cn(
                                'rounded-md px-3 py-2 text-sm whitespace-nowrap transition-colors hover:bg-muted',
                                path === item.href && 'bg-muted font-medium',
                            )}
                        >
                            {t(item.title)}
                        </Link>
                    ))}
                </nav>
                <div className="min-w-0 flex-1 space-y-10">{children}</div>
            </div>
        </div>
    );
}
