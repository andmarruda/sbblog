import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Props = Omit<
    ComponentProps<typeof Input>,
    'value' | 'onChange' | 'type'
> & {
    value: string | null;
    onChange: (value: string) => void;
};

export function ColorInput({ value, onChange, id, ...props }: Props) {
    const color = value ?? '';

    return (
        <div className="flex items-center gap-2">
            <input
                type="color"
                aria-hidden
                tabIndex={-1}
                value={/^#[0-9a-f]{6}$/i.test(color) ? color : '#000000'}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    'size-9 shrink-0 cursor-pointer rounded-md border bg-transparent p-1',
                    // No color yet: a faded swatch instead of a misleading black one.
                    color === '' && 'opacity-30',
                )}
            />
            <Input
                {...props}
                id={id}
                value={color}
                placeholder="#000000"
                maxLength={7}
                onChange={(event) => onChange(event.target.value)}
                className="font-mono"
            />
        </div>
    );
}
