import { usePage } from '@inertiajs/react';
import { BrandMark } from '@/components/app/brand-mark';

export function AppLogo() {
    const { name } = usePage().props;

    return (
        <div className="flex items-center gap-2">
            <BrandMark />
            <span className="truncate font-semibold tracking-tight">
                {name}
            </span>
        </div>
    );
}
