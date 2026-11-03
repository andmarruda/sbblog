import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

export function TextLink({ className, ...props }: ComponentProps<typeof Link>) {
    return (
        <Link
            {...props}
            className={cn(
                'text-foreground underline decoration-muted-foreground/40 underline-offset-4 transition-colors hover:decoration-current',
                className,
            )}
        />
    );
}
