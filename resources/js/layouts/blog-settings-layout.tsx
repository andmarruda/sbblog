import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCan } from '@/hooks/use-can';
import { useTranslation } from '@/hooks/use-translation';
import { SectionLayout, type SectionNavItem } from '@/layouts/section-layout';
import { audit, general, members } from '@/routes/blogs/settings';

export default function BlogSettingsLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { t } = useTranslation();
    const can = useCan();
    const { currentWorkspace, currentBlog } = usePage().props;
    const args = {
        workspace: currentWorkspace?.slug ?? '',
        blog: currentBlog?.slug ?? '',
    };

    const items: (SectionNavItem | false)[] = [
        can('blog.settings') && { title: 'General', href: general(args).url },
        can('members.manage') && { title: 'Members', href: members(args).url },
        can('audit.view') && { title: 'Audit log', href: audit(args).url },
    ];

    return (
        <SectionLayout
            title={currentBlog?.name ?? ''}
            description={t('Blog settings')}
            items={items.filter(
                (item): item is SectionNavItem => item !== false,
            )}
        >
            {children}
        </SectionLayout>
    );
}
