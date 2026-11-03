import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { SectionLayout, type SectionNavItem } from '@/layouts/section-layout';
import {
    apps,
    audit,
    blogs,
    general,
    members,
    roles,
} from '@/routes/workspaces/settings';

export default function WorkspaceSettingsLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { t } = useTranslation();
    const can = useCan();
    const { currentWorkspace } = usePage().props;
    const workspace = currentWorkspace?.slug ?? '';

    const items: (SectionNavItem | false)[] = [
        { title: 'Blogs', href: blogs(workspace).url },
        can('workspace.settings') && {
            title: 'General',
            href: general(workspace).url,
        },
        can('members.manage') && {
            title: 'Members',
            href: members(workspace).url,
        },
        can('members.manage') && { title: 'Roles', href: roles(workspace).url },
        can('apps.manage') && { title: 'Apps', href: apps(workspace).url },
        can('workspace.settings') && {
            title: 'Audit log',
            href: audit(workspace).url,
        },
    ];

    return (
        <SectionLayout
            title={currentWorkspace?.name ?? ''}
            description={t('Workspace settings')}
            items={items.filter(
                (item): item is SectionNavItem => item !== false,
            )}
        >
            {children}
        </SectionLayout>
    );
}
