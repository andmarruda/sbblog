import type { LucideIcon } from 'lucide-react';

/**
 * Titles are translation keys; layouts translate them.
 */
export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href: string;
    icon?: LucideIcon;
};
