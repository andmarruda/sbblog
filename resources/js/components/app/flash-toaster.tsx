import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';

/**
 * Shows `Inertia::flash('toast', [...])` messages from the server.
 */
export function FlashToaster() {
    const { flash } = usePage();

    useEffect(() => {
        if (flash.toast) {
            toast[flash.toast.type](flash.toast.message);
        }
    }, [flash]);

    return <Toaster position="top-right" />;
}
