import { Link, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, Settings } from 'lucide-react';
import { AppLogo } from '@/components/app/app-logo';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { dashboard as blogDashboard } from '@/routes/blogs';
import { blogs as workspaceBlogs } from '@/routes/workspaces/settings';

/**
 * Sidebar header: the current blog, and a menu to jump to any workspace or blog.
 */
export function TenantSwitcher() {
    const { t } = useTranslation();
    const { tenants, currentWorkspace, currentBlog } = usePage().props;
    const { isMobile } = useSidebar();

    if (tenants.length === 0) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" asChild>
                        <Link href={dashboard()}>
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent"
                        >
                            <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">
                                {(
                                    currentBlog?.name ??
                                    currentWorkspace?.name ??
                                    '?'
                                )
                                    .charAt(0)
                                    .toUpperCase()}
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {currentBlog?.name ??
                                        currentWorkspace?.name ??
                                        t('Choose a blog')}
                                </span>
                                {currentBlog && currentWorkspace && (
                                    <span className="truncate text-xs text-muted-foreground">
                                        {currentWorkspace.name}
                                    </span>
                                )}
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="max-h-[70vh] min-w-64 overflow-y-auto"
                        align="start"
                        side={isMobile ? 'bottom' : 'right'}
                    >
                        {tenants.map((workspace, index) => (
                            <DropdownMenuGroup key={workspace.id}>
                                {index > 0 && <DropdownMenuSeparator />}
                                <DropdownMenuLabel className="text-xs text-muted-foreground">
                                    {workspace.name}
                                </DropdownMenuLabel>
                                {workspace.blogs.map((blog) => (
                                    <DropdownMenuItem key={blog.id} asChild>
                                        <Link
                                            href={blogDashboard({
                                                workspace: workspace.slug,
                                                blog: blog.slug,
                                            })}
                                        >
                                            <span className="truncate">
                                                {blog.name}
                                            </span>
                                            {blog.id === currentBlog?.id && (
                                                <Check className="ml-auto" />
                                            )}
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                                <DropdownMenuItem asChild>
                                    <Link href={workspaceBlogs(workspace.slug)}>
                                        {workspace.role === 'member' ? (
                                            <Settings />
                                        ) : (
                                            <Plus />
                                        )}
                                        {workspace.role === 'member'
                                            ? t('Workspace')
                                            : t('Manage blogs')}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
