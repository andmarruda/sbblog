import type { ReactNode } from 'react';

type Props = {
    /** Already translated. */
    title: string;
    description: string;
    children: ReactNode;
};

export function DangerZone({ title, description, children }: Props) {
    return (
        <section className="flex flex-col gap-4 rounded-lg border border-destructive/40 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-1">
                <h2 className="font-medium">{title}</h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            <div className="shrink-0">{children}</div>
        </section>
    );
}
