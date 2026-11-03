import type { ReactNode } from 'react';

type Props = {
    /** Already translated. */
    title: string;
    description?: string;
    children: ReactNode;
};

export function SettingsSection({ title, description, children }: Props) {
    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-medium">{title}</h2>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </header>
            {children}
        </section>
    );
}
