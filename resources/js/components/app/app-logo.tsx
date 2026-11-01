import { usePage } from '@inertiajs/react';
import { NotebookPen } from 'lucide-react';

export function AppLogo() {
    const { name } = usePage().props;

    return (
        <div className="flex items-center gap-2">
            <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <NotebookPen className="size-4" />
            </div>
            <span className="truncate font-semibold">{name}</span>
        </div>
    );
}
