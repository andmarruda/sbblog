import type { LucideIcon } from 'lucide-react';

/**
 * Titles are translation keys; layouts translate them.
 */
export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    /** Translation key. */
    title: string;
    href: string;
    icon?: LucideIcon;
    /** Active only on this exact URL, not its children. */
    exact?: boolean;
};
