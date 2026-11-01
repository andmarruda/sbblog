import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';

type Props = {
    /** Already translated. Also used as the document title. */
    title: string;
    description?: string;
    /** Buttons aligned to the right. */
    actions?: ReactNode;
};

export function PageHeader({ title, description, actions }: Props) {
    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
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
                {actions && (
                    <div className="flex shrink-0 items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
        </>
    );
}
