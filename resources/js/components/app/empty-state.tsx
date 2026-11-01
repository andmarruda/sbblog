import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    icon?: LucideIcon;
    /** Already translated. */
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed p-10 text-center',
                className,
            )}
        >
            {Icon && (
                <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                    <Icon className="size-6 text-muted-foreground" />
                </div>
            )}
            <div className="space-y-1">
                <h3 className="font-medium">{title}</h3>
                {description && (
                    <p className="max-w-sm text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action}
        </div>
    );
}
