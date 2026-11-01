import { LayoutDashboard } from 'lucide-react';
import { EmptyState } from '@/components/app/empty-state';
import { PageHeader } from '@/components/app/page-header';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { t } = useTranslation();

    return (
        <>
            <PageHeader
                title={t('Dashboard')}
                description={t('An overview of your blog.')}
            />
            <EmptyState
                icon={LayoutDashboard}
                title={t('Nothing here yet')}
                description={t(
                    'Drafts, reviews, comments and traffic will show up here.',
                )}
            />
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard().url }],
};
