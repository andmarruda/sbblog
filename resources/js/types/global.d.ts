import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';
import type {
    BlogSummary,
    CurrentWorkspace,
    WorkspaceSummary,
} from '@/types/tenancy';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            locale: string;
            locales: string[];
            sidebarOpen: boolean;
            /** Workspaces and blogs the user can open (switcher). */
            tenants: WorkspaceSummary[];
            currentWorkspace: CurrentWorkspace | null;
            currentBlog: BlogSummary | null;
            /** Permission keys for the current blog, or the current workspace. */
            can: string[];
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: {
                type: 'success' | 'error' | 'info' | 'warning';
                message: string;
            };
        };
        layoutProps: {
            breadcrumbs: BreadcrumbItem[];
            title: string;
            description: string;
        };
    }
}
