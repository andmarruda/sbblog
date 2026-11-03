import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export type RevisionDiffData = {
    title: { from: string; to: string };
    description: { from: string | null; to: string | null };
    cover_changed: boolean;
    body: { op: 'equal' | 'insert' | 'delete'; text: string }[];
};

function Field({
    label,
    from,
    to,
}: {
    label: string;
    from: string | null;
    to: string | null;
}) {
    const { t } = useTranslation();

    if ((from ?? '') === (to ?? '')) {
        return null;
    }

    return (
        <div className="space-y-1">
            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            {from && (
                <p className="rounded bg-red-50 px-2 py-1 text-sm text-red-900 line-through dark:bg-red-950 dark:text-red-200">
                    {from}
                </p>
            )}
            <p className="rounded bg-emerald-50 px-2 py-1 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {to || t('(empty)')}
            </p>
        </div>
    );
}

/**
 * Block-by-block comparison of two revisions.
 */
export function DiffView({
    diff,
    onSelectText,
}: {
    diff: RevisionDiffData;
    onSelectText?: (text: string) => void;
}) {
    const { t } = useTranslation();
    const changed = diff.body.some((line) => line.op !== 'equal');

    return (
        <div
            className="space-y-4"
            onMouseUp={() => {
                const text = window.getSelection()?.toString().trim();

                if (text && onSelectText) {
                    onSelectText(text);
                }
            }}
        >
            <Field
                label={t('Title')}
                from={diff.title.from}
                to={diff.title.to}
            />
            <Field
                label={t('Description')}
                from={diff.description.from}
                to={diff.description.to}
            />
            {diff.cover_changed && (
                <p className="text-sm text-muted-foreground">
                    {t('The cover image changed.')}
                </p>
            )}
            <div className="space-y-1 rounded-lg border p-3">
                {!changed && (
                    <p className="text-sm text-muted-foreground">
                        {t('The text did not change.')}
                    </p>
                )}
                {diff.body.map((line, index) => (
                    <p
                        key={index}
                        className={cn(
                            'rounded px-2 py-1 text-sm whitespace-pre-wrap',
                            line.op === 'insert' &&
                                'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
                            line.op === 'delete' &&
                                'bg-red-50 text-red-900 line-through dark:bg-red-950 dark:text-red-200',
                            line.op === 'equal' && 'text-muted-foreground',
                        )}
                    >
                        <span className="sr-only">
                            {line.op === 'insert'
                                ? t('Added:')
                                : line.op === 'delete'
                                  ? t('Removed:')
                                  : ''}
                        </span>
                        {line.text || ' '}
                    </p>
                ))}
            </div>
        </div>
    );
}
