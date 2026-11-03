import { usePage } from '@inertiajs/react';
import {
    Building2,
    FileText,
    FolderTree,
    Image,
    LayoutDashboard,
    MessageSquare,
    Plug,
    Radio,
    Settings,
    Tags,
    UserRound,
} from 'lucide-react';
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
import { index as articles } from '@/routes/articles';
import { index as authors } from '@/routes/authors';
import { dashboard as blogDashboard } from '@/routes/blogs';
import { index as categories } from '@/routes/categories';
import { index as comments } from '@/routes/comments';
import { index as integrations } from '@/routes/integrations';
import { index as publishing } from '@/routes/publishing';
import { index as media } from '@/routes/media';
import { index as tags } from '@/routes/tags';
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

    const contentNav: NavItem[] = blogArgs
        ? [
              ...(can('articles.view')
                  ? [
                        {
                            title: 'Articles',
                            href: articles(blogArgs).url,
                            icon: FileText,
                        },
                        {
                            title: 'Media',
                            href: media(blogArgs).url,
                            icon: Image,
                        },
                    ]
                  : []),
              ...(can('categories.manage')
                  ? [
                        {
                            title: 'Categories',
                            href: categories(blogArgs).url,
                            icon: FolderTree,
                        },
                    ]
                  : []),
              ...(can('tags.manage')
                  ? [{ title: 'Tags', href: tags(blogArgs).url, icon: Tags }]
                  : []),
              ...(can('comments.moderate')
                  ? [
                        {
                            title: 'Comments',
                            href: comments(blogArgs).url,
                            icon: MessageSquare,
                        },
                    ]
                  : []),
              ...(can('authors.manage')
                  ? [
                        {
                            title: 'Authors',
                            href: authors(blogArgs).url,
                            icon: UserRound,
                        },
                    ]
                  : []),
          ]
        : [];

    // Integrations and publishing items join this list in later phases.
    const blogNav: NavItem[] = blogArgs
        ? [
              {
                  title: 'Dashboard',
                  href: blogDashboard(blogArgs).url,
                  icon: LayoutDashboard,
                  exact: true,
              },
              ...(can('integrations.manage')
                  ? [
                        {
                            title: 'Integrations',
                            href: integrations(blogArgs).url,
                            icon: Plug,
                        },
                    ]
                  : []),
              ...(can('frontends.manage')
                  ? [
                        {
                            title: 'Publishing',
                            href: publishing(blogArgs).url,
                            icon: Radio,
                        },
                    ]
                  : []),
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
                {contentNav.length > 0 && (
                    <NavMain label="Content" items={contentNav} />
                )}
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
