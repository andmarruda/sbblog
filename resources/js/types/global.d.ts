import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';

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
            /** Permission keys for the current blog (shared once tenancy lands). */
            can?: string[];
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
