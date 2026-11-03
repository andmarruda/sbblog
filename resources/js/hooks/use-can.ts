import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

/**
 * Check permission keys (e.g. "articles.publish") for the current blog.
 * The server is still the authority; this only hides UI.
 */
export function useCan() {
    const { can } = usePage().props;

    return useCallback(
        (...permissions: string[]) =>
            permissions.every((permission) => can.includes(permission)),
        [can],
    );
}
