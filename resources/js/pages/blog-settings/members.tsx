import { router, usePage } from '@inertiajs/react';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { destroy, update } from '@/routes/blogs/settings/members';

type Props = {
    members: {
        id: string;
        name: string;
        email: string;
        workspace_role: 'owner' | 'admin' | 'member';
        role_id: string | null;
    }[];
    roles: { id: string; key: string; name: string }[];
};

const NO_ACCESS = 'none';

export default function BlogMembers({ members, roles }: Props) {
    const { t } = useTranslation();
    const labels = useLabels();
    const { currentWorkspace, currentBlog } = usePage().props;

    const change = (memberId: string, roleId: string) => {
        const args = {
            workspace: currentWorkspace?.slug ?? '',
            blog: currentBlog?.slug ?? '',
            member: memberId,
        };

        if (roleId === NO_ACCESS) {
            router.delete(destroy(args), { preserveScroll: true });
        } else {
            router.put(
                update(args),
                { role_id: roleId },
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <PageTitle title={t('Members')} />
            <SettingsSection
                title={t('Members')}
                description={t(
                    'Who can work on this blog. Workspace owners and admins always can.',
                )}
            >
                <div className="overflow-x-auto rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead className="w-56">
                                    {t('Role on the blog')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell>
                                        <p className="font-medium">
                                            {member.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {member.email}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        {member.workspace_role === 'member' ? (
                                            <Select
                                                value={
                                                    member.role_id ?? NO_ACCESS
                                                }
                                                onValueChange={(value) =>
                                                    change(member.id, value)
                                                }
                                            >
                                                <SelectTrigger
                                                    className="w-full"
                                                    aria-label={t(
                                                        'Role on the blog',
                                                    )}
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        value={NO_ACCESS}
                                                    >
                                                        {t('No access')}
                                                    </SelectItem>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role.id}
                                                            value={role.id}
                                                        >
                                                            {labels.role[
                                                                role.key
                                                            ] ?? role.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <Badge variant="secondary">
                                                {
                                                    labels.workspaceRole[
                                                        member.workspace_role
                                                    ]
                                                }{' '}
                                                · {t('All blogs')}
                                            </Badge>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </SettingsSection>
        </>
    );
}

BlogMembers.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'Members' }],
};
