import { usePage } from '@inertiajs/react';
import { Building2, LayoutDashboard, Settings } from 'lucide-react';
import { NavMain } from '@/components/app/nav-main';
import { NavUser } from '@/components/app/nav-user';
import { TenantSwitcher } from '@/components/app/tenant-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail,
} from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import { dashboard as blogDashboard } from '@/routes/blogs';
import { index as blogSettings } from '@/routes/blogs/settings';
import { index as workspaceSettings } from '@/routes/workspaces/settings';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const can = useCan();
    const { currentWorkspace, currentBlog } = usePage().props;

    const blogArgs =
        currentWorkspace && currentBlog
            ? { workspace: currentWorkspace.slug, blog: currentBlog.slug }
            : null;

    // Content, integrations and publishing items join this list in later phases.
    const blogNav: NavItem[] = blogArgs
        ? [
              {
                  title: 'Dashboard',
                  href: blogDashboard(blogArgs).url,
                  icon: LayoutDashboard,
                  exact: true,
              },
              ...(can('blog.settings') ||
              can('members.manage') ||
              can('audit.view')
                  ? [
                        {
                            title: 'Blog settings',
                            href: blogSettings(blogArgs).url,
                            icon: Settings,
                        },
                    ]
                  : []),
          ]
        : [];

    const workspaceNav: NavItem[] = currentWorkspace
        ? [
              {
                  title: 'Workspace settings',
                  href: workspaceSettings(currentWorkspace.slug).url,
                  icon: Building2,
              },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <TenantSwitcher />
            </SidebarHeader>
            <SidebarContent>
                {blogNav.length > 0 && <NavMain label="Blog" items={blogNav} />}
                {workspaceNav.length > 0 && (
                    <NavMain label="Workspace" items={workspaceNav} />
                )}
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
