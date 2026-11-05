import { Link, router, usePage } from '@inertiajs/react';
import {
    ChevronsUpDown,
    Info,
    Languages,
    LogOut,
    Palette,
    Settings,
    ShieldCheck,
} from 'lucide-react';
import { AppearanceMenuItems } from '@/components/app/appearance-menu';
import { LocaleMenuItems } from '@/components/app/locale-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { about, logout } from '@/routes';
import { index as adminUsers } from '@/routes/admin/users';
import { edit as editProfile } from '@/routes/profile';

export function initials(name: string) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

export function NavUser() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const { isMobile } = useSidebar();
    const user = auth.user;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent"
                        >
                            <Avatar className="size-8 rounded-lg">
                                <AvatarFallback className="rounded-lg text-foreground">
                                    {user ? initials(user.name) : '?'}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {user?.name ?? t('Guest')}
                                </span>
                                {user && (
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                )}
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="min-w-56"
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                    >
                        {user && (
                            <>
                                <DropdownMenuLabel className="font-normal">
                                    <div className="grid text-sm leading-tight">
                                        <span className="truncate font-medium">
                                            {user.name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {user.email}
                                        </span>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href={editProfile()} prefetch>
                                        <Settings />
                                        {t('Account settings')}
                                    </Link>
                                </DropdownMenuItem>
                                {user.is_admin && (
                                    <DropdownMenuItem asChild>
                                        <Link href={adminUsers()}>
                                            <ShieldCheck />
                                            {t('Administration')}
                                        </Link>
                                    </DropdownMenuItem>
                                )}
                            </>
                        )}
                        <DropdownMenuItem asChild>
                            <Link href={about()}>
                                <Info />
                                {t('About SBBlog')}
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                <Languages />
                                {t('Language')}
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <LocaleMenuItems />
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                <Palette />
                                {t('Appearance')}
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <AppearanceMenuItems />
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                        {user && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    onSelect={() => {
                                        router.flushAll();
                                        router.post(logout());
                                    }}
                                >
                                    <LogOut />
                                    {t('Log out')}
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
