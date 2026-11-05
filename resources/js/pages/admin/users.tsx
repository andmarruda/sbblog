import { router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ShieldCheck, ShieldOff } from 'lucide-react';
import { useMemo } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { DataTable, type TableQuery } from '@/components/app/data-table';
import { PageHeader } from '@/components/app/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { admin as updateAdmin } from '@/routes/admin/users';
import type { Paginated } from '@/types';

type AdminUser = {
    id: string;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    last_login_at: string | null;
    created_at: string;
    workspaces_count: number;
};

type Props = {
    users: Paginated<AdminUser>;
    query: TableQuery;
    counts: { users: number; admins: number };
};

export default function Users({ users, query, counts }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const { auth } = usePage().props;

    const columns = useMemo<ColumnDef<AdminUser, unknown>[]>(
        () => [
            {
                id: 'name',
                header: t('Name'),
                enableSorting: true,
                cell: ({ row }) => (
                    <div>
                        <p className="flex items-center gap-2 font-medium">
                            {row.original.name}
                            {row.original.is_admin && (
                                <Badge variant="secondary">{t('Admin')}</Badge>
                            )}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {row.original.email}
                            {row.original.email_verified_at === null &&
                                ` · ${t('Not verified')}`}
                        </p>
                    </div>
                ),
            },
            {
                id: 'workspaces_count',
                header: t('Workspaces'),
                cell: ({ row }) => formatNumber(row.original.workspaces_count),
            },
            {
                id: 'last_login_at',
                header: t('Last login'),
                enableSorting: true,
                cell: ({ row }) =>
                    row.original.last_login_at
                        ? formatDate(row.original.last_login_at, true)
                        : t('Never'),
            },
            {
                id: 'created_at',
                header: t('Joined'),
                enableSorting: true,
                cell: ({ row }) => formatDate(row.original.created_at),
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => {
                    const user = row.original;

                    if (user.id === auth.user?.id) {
                        return null;
                    }

                    return (
                        <div className="flex justify-end">
                            <ConfirmDialog
                                trigger={
                                    <Button variant="ghost" size="sm">
                                        {user.is_admin ? (
                                            <ShieldOff />
                                        ) : (
                                            <ShieldCheck />
                                        )}
                                        {user.is_admin
                                            ? t('Revoke admin')
                                            : t('Make admin')}
                                    </Button>
                                }
                                title={
                                    user.is_admin
                                        ? t('Revoke admin access from :name?', {
                                              name: user.name,
                                          })
                                        : t('Make :name an administrator?', {
                                              name: user.name,
                                          })
                                }
                                description={
                                    user.is_admin
                                        ? t(
                                              'They keep their workspaces and blogs, but can no longer manage the instance.',
                                          )
                                        : t(
                                              'Administrators can see every account and grant or revoke admin access.',
                                          )
                                }
                                confirmLabel={
                                    user.is_admin
                                        ? t('Revoke admin')
                                        : t('Make admin')
                                }
                                destructive={user.is_admin}
                                onConfirm={() =>
                                    router.put(
                                        updateAdmin(user.id),
                                        { is_admin: !user.is_admin },
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        </div>
                    );
                },
            },
        ],
        [t, formatDate, formatNumber, auth.user?.id],
    );

    return (
        <>
            <PageHeader
                title={t('Users')}
                description={t(
                    ':users accounts on this instance, :admins administrators.',
                    {
                        users: formatNumber(counts.users),
                        admins: formatNumber(counts.admins),
                    },
                )}
            />
            <DataTable
                columns={columns}
                paginator={users}
                query={query}
                getRowId={(row) => row.id}
                toolbar={(apply) => (
                    <div className="flex gap-1">
                        <Button
                            variant={
                                query.role === 'admin' ? 'ghost' : 'secondary'
                            }
                            size="sm"
                            onClick={() => apply({ role: undefined })}
                        >
                            {t('All')}
                        </Button>
                        <Button
                            variant={
                                query.role === 'admin' ? 'secondary' : 'ghost'
                            }
                            size="sm"
                            onClick={() => apply({ role: 'admin' })}
                        >
                            {t('Administrators')}
                        </Button>
                    </div>
                )}
            />
        </>
    );
}

Users.layout = {
    breadcrumbs: [{ title: 'Administration' }, { title: 'Users' }],
};
