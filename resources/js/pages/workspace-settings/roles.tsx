import { Check, ShieldCheck } from 'lucide-react';
import { Fragment } from 'react';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useLabels } from '@/hooks/use-labels';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    roles: {
        id: string;
        key: string;
        name: string;
        is_system: boolean;
        permissions: string[];
    }[];
    permissions: {
        key: string;
        group: string;
        scope: string;
        requires_two_factor: boolean;
        description: string;
    }[];
};

export default function Roles({ roles, permissions }: Props) {
    const { t } = useTranslation();
    const labels = useLabels();
    const groups = [
        ...new Set(permissions.map((permission) => permission.group)),
    ];

    return (
        <>
            <PageTitle title={t('Roles')} />
            <SettingsSection
                title={t('Roles')}
                description={t(
                    'What each role can do. Custom roles come with paid plans.',
                )}
            >
                <div className="overflow-x-auto rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="min-w-64">
                                    {t('Permission')}
                                </TableHead>
                                {roles.map((role) => (
                                    <TableHead
                                        key={role.id}
                                        className="text-center"
                                    >
                                        {labels.role[role.key] ?? role.name}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {groups.map((group) => (
                                <Fragment key={group}>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableCell
                                            colSpan={roles.length + 1}
                                            className="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {t(group)}
                                        </TableCell>
                                    </TableRow>
                                    {permissions
                                        .filter(
                                            (permission) =>
                                                permission.group === group,
                                        )
                                        .map((permission) => (
                                            <TableRow key={permission.key}>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {t(
                                                            permission.description,
                                                        )}
                                                        {permission.requires_two_factor && (
                                                            <Badge
                                                                variant="outline"
                                                                className="gap-1"
                                                            >
                                                                <ShieldCheck className="size-3" />
                                                                2FA
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                {roles.map((role) => (
                                                    <TableCell
                                                        key={role.id}
                                                        className="text-center"
                                                    >
                                                        {role.permissions.includes(
                                                            permission.key,
                                                        ) && (
                                                            <Check
                                                                className="mx-auto size-4"
                                                                aria-label={t(
                                                                    'Allowed',
                                                                )}
                                                            />
                                                        )}
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))}
                                </Fragment>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </SettingsSection>
        </>
    );
}

Roles.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'Roles' }],
};
