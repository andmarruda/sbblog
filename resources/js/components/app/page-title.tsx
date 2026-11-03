import { Head } from '@inertiajs/react';

/**
 * Sets the document title for pages whose heading lives in the layout.
 */
export function PageTitle({ title }: { title: string }) {
    return <Head title={title} />;
}
