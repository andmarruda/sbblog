import { usePage } from '@inertiajs/react';

/**
 * Route arguments for the current blog: `{ workspace, blog }`.
 */
export function useBlogRoute() {
    const { currentWorkspace, currentBlog } = usePage().props;

    return {
        workspace: currentWorkspace?.slug ?? '',
        blog: currentBlog?.slug ?? '',
    };
}
