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

export default function WorkspaceAudit({ logs, query }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Audit log')} />
            <SettingsSection
                title={t('Audit log')}
                description={t('Changes made in this workspace and its blogs.')}
            >
                <AuditLogTable logs={logs} query={query} showBlog />
            </SettingsSection>
        </>
    );
}

WorkspaceAudit.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'Audit log' }],
};
