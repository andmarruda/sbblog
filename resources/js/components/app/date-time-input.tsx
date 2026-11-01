import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';

type Props = Omit<
    ComponentProps<typeof Input>,
    'value' | 'onChange' | 'type'
> & {
    /** ISO 8601 instant (UTC), as the API sends it. */
    value: string | null;
    onChange: (value: string | null) => void;
};

const pad = (value: number) => String(value).padStart(2, '0');

/** ISO instant → "YYYY-MM-DDTHH:mm" in the browser's timezone. */
function toLocalInput(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/**
 * Edits an instant in the user's local time and emits it back as an ISO string (UTC).
 */
export function DateTimeInput({ value, onChange, ...props }: Props) {
    return (
        <Input
            {...props}
            type="datetime-local"
            value={toLocalInput(value)}
            onChange={(event) =>
                onChange(
                    event.target.value
                        ? new Date(event.target.value).toISOString()
                        : null,
                )
            }
        />
    );
}
