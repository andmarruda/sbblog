import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import type { NavItem } from '@/types';

export function NavMain({
    label,
    items,
}: {
    label?: string;
    items: NavItem[];
}) {
    const { t } = useTranslation();
    const { url } = usePage();

    return (
        <SidebarGroup>
            {label && <SidebarGroupLabel>{t(label)}</SidebarGroupLabel>}
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.href}>
                        <SidebarMenuButton
                            asChild
                            isActive={url.startsWith(item.href)}
                            tooltip={t(item.title)}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{t(item.title)}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
