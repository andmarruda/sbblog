import type { ColumnDef } from '@tanstack/react-table';
import { Bot } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable, type TableQuery } from '@/components/app/data-table';
import { useTranslation } from '@/hooks/use-translation';
import type { Paginated } from '@/types';

export type AuditLogEntry = {
    id: string;
    action: string;
    actor: { id?: string; name: string; email?: string; app?: boolean } | null;
    blog: string | null;
    subject_type: string | null;
    subject_id: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
};

type Props = {
    logs: Paginated<AuditLogEntry>;
    query: TableQuery;
    showBlog?: boolean;
};

export function AuditLogTable({ logs, query, showBlog = false }: Props) {
    const { t, formatDate } = useTranslation();

    const columns = useMemo<ColumnDef<AuditLogEntry, unknown>[]>(
        () => [
            {
                id: 'created_at',
                header: t('When'),
                cell: ({ row }) => (
                    <span className="whitespace-nowrap text-muted-foreground">
                        {formatDate(row.original.created_at, true)}
                    </span>
                ),
            },
            {
                id: 'actor',
                header: t('Who'),
                cell: ({ row }) => {
                    const actor = row.original.actor;

                    if (!actor) {
                        return (
                            <span className="text-muted-foreground">
                                {t('System or visitor')}
                            </span>
                        );
                    }

                    return (
                        <span className="flex items-center gap-1">
                            {actor.app && (
                                <Bot className="size-4 text-muted-foreground" />
                            )}
                            {actor.name}
                        </span>
                    );
                },
            },
            {
                id: 'action',
                header: t('Action'),
                cell: ({ row }) => (
                    <code className="text-xs">{row.original.action}</code>
                ),
            },
            ...(showBlog
                ? [
                      {
                          id: 'blog',
                          header: t('Blog'),
                          cell: ({
                              row,
                          }: {
                              row: { original: AuditLogEntry };
                          }) => row.original.blog ?? '—',
                      },
                  ]
                : []),
            {
                id: 'details',
                header: t('Details'),
                cell: ({ row }) => {
                    const metadata = row.original.metadata;

                    return metadata && Object.keys(metadata).length > 0 ? (
                        <code className="line-clamp-2 max-w-md text-xs break-all text-muted-foreground">
                            {JSON.stringify(metadata)}
                        </code>
                    ) : null;
                },
            },
        ],
        [t, formatDate, showBlog],
    );

    return (
        <DataTable
            columns={columns}
            paginator={logs}
            query={query}
            getRowId={(row) => row.id}
        />
    );
}
