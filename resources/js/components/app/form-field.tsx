import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    /** The id of the control, used by the label. */
    id: string;
    /** Already translated. */
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: ReactNode;
};

export function FormField({
    id,
    label,
    error,
    hint,
    required = false,
    className,
    children,
}: Props) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required && (
                    <span aria-hidden className="text-destructive">
                        *
                    </span>
                )}
            </Label>
            {children}
            {error ? (
                <p id={`${id}-error`} className="text-sm text-destructive">
                    {error}
                </p>
            ) : (
                hint && <p className="text-sm text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}
