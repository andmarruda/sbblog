import {
    AuditLogTable,
    type AuditLogEntry,
} from '@/components/app/audit-log-table';
import type { TableQuery } from '@/components/app/data-table';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { useTranslation } from '@/hooks/use-translation';
import type { Paginated } from '@/types';

type Props = {
    logs: Paginated<AuditLogEntry>;
    query: TableQuery;
};

export default function BlogAudit({ logs, query }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Audit log')} />
            <SettingsSection
                title={t('Audit log')}
                description={t('Changes made in this blog.')}
            >
                <AuditLogTable logs={logs} query={query} />
            </SettingsSection>
        </>
    );
}

BlogAudit.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'Audit log' }],
};
