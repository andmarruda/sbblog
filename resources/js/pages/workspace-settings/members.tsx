import { Form, router, usePage } from '@inertiajs/react';
import { Crown, Mail, RotateCw, Trash2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
import {
    destroy as destroyInvitation,
    resend,
    store as storeInvitation,
} from '@/routes/workspaces/settings/invitations';
import {
    destroy as destroyMember,
    transfer,
    update as updateMember,
} from '@/routes/workspaces/settings/members';

type Member = {
    id: string;
    name: string;
    email: string;
    workspace_role: 'owner' | 'admin' | 'member';
    joined_at: string | null;
    blogs: { blog: string; role: string }[];
};

type Invitation = {
    id: string;
    email: string;
    workspace_role: string;
    blog: string | null;
    role: string | null;
    expires_at: string;
    expired: boolean;
};

type Props = {
    members: Member[];
    invitations: Invitation[];
    blogs: { id: string; name: string }[];
    roles: { id: string; key: string; name: string }[];
    isOwner: boolean;
    currentUserId: string;
};

const NO_BLOG = 'none';

function InviteDialog({ blogs, roles }: Pick<Props, 'blogs' | 'roles'>) {
    const { t } = useTranslation();
    const labels = useLabels();
    const { currentWorkspace } = usePage().props;
    const [open, setOpen] = useState(false);
    const [workspaceRole, setWorkspaceRole] = useState('member');
    const [blogId, setBlogId] = useState(NO_BLOG);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <UserPlus />
                    {t('Invite')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Invite someone')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'They get an email with a link that is valid for 7 days.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={storeInvitation(currentWorkspace?.slug ?? '')}
                    transform={(data) => ({
                        ...data,
                        workspace_role: workspaceRole,
                        blog_id: blogId === NO_BLOG ? null : blogId,
                    })}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="invite-email"
                                label={t('Email')}
                                error={errors.email}
                            >
                                <Input
                                    id="invite-email"
                                    name="email"
                                    type="email"
                                    required
                                />
                            </FormField>
                            <FormField
                                id="invite-role"
                                label={t('Workspace role')}
                                hint={t(
                                    'Admins manage the workspace and reach every blog.',
                                )}
                                error={errors.workspace_role}
                            >
                                <Select
                                    value={workspaceRole}
                                    onValueChange={setWorkspaceRole}
                                >
                                    <SelectTrigger
                                        id="invite-role"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="member">
                                            {labels.workspaceRole.member}
                                        </SelectItem>
                                        <SelectItem value="admin">
                                            {labels.workspaceRole.admin}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>
                            {workspaceRole === 'member' && (
                                <>
                                    <FormField
                                        id="invite-blog"
                                        label={t('Blog access')}
                                        error={errors.blog_id}
                                    >
                                        <Select
                                            value={blogId}
                                            onValueChange={setBlogId}
                                        >
                                            <SelectTrigger
                                                id="invite-blog"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={NO_BLOG}>
                                                    {t('Later')}
                                                </SelectItem>
                                                {blogs.map((blog) => (
                                                    <SelectItem
                                                        key={blog.id}
                                                        value={blog.id}
                                                    >
                                                        {blog.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </FormField>
                                    {blogId !== NO_BLOG && (
                                        <FormField
                                            id="invite-blog-role"
                                            label={t('Role on the blog')}
                                            error={errors.role_id}
                                        >
                                            <Select name="role_id">
                                                <SelectTrigger
                                                    id="invite-blog-role"
                                                    className="w-full"
                                                >
                                                    <SelectValue
                                                        placeholder={t(
                                                            'Choose a role',
                                                        )}
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
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
                                        </FormField>
                                    )}
                                </>
                            )}
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    <Mail />
                                    {t('Send invitation')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function TransferDialog({ member }: { member: Member }) {
    const { t } = useTranslation();
    const { currentWorkspace } = usePage().props;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={t('Transfer ownership')}
                >
                    <Crown />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Transfer ownership to :name?', {
                            name: member.name,
                        })}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'You will become an admin. Enter your password to confirm.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={transfer({
                        workspace: currentWorkspace?.slug ?? '',
                        member: member.id,
                    })}
                    resetOnError
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id={`transfer-${member.id}`}
                                label={t('Password')}
                                error={errors.password}
                            >
                                <PasswordInput
                                    id={`transfer-${member.id}`}
                                    name="password"
                                    autoComplete="current-password"
                                    required
                                />
                            </FormField>
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {t('Transfer ownership')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function Members({
    members,
    invitations,
    blogs,
    roles,
    isOwner,
    currentUserId,
}: Props) {
    const { t, formatDate } = useTranslation();
    const labels = useLabels();
    const { currentWorkspace, errors } = usePage().props;
    const workspace = currentWorkspace?.slug ?? '';

    return (
        <>
            <PageTitle title={t('Members')} />
            <SettingsSection
                title={t('Members')}
                description={t('People in this workspace and their access.')}
            >
                <div>
                    <InviteDialog blogs={blogs} roles={roles} />
                </div>
                {errors.member && (
                    <p className="text-sm text-destructive">{errors.member}</p>
                )}
                <div className="overflow-x-auto rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Workspace role')}</TableHead>
                                <TableHead>{t('Blogs')}</TableHead>
                                <TableHead className="w-24" />
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
                                        {member.workspace_role === 'owner' ? (
                                            <Badge>
                                                {labels.workspaceRole.owner}
                                            </Badge>
                                        ) : (
                                            <Select
                                                value={member.workspace_role}
                                                onValueChange={(value) =>
                                                    router.patch(
                                                        updateMember({
                                                            workspace,
                                                            member: member.id,
                                                        }),
                                                        {
                                                            workspace_role:
                                                                value,
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    className="w-32"
                                                    aria-label={t(
                                                        'Workspace role',
                                                    )}
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="member">
                                                        {
                                                            labels.workspaceRole
                                                                .member
                                                        }
                                                    </SelectItem>
                                                    <SelectItem value="admin">
                                                        {
                                                            labels.workspaceRole
                                                                .admin
                                                        }
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {member.workspace_role === 'member' ? (
                                            <div className="flex flex-wrap gap-1">
                                                {member.blogs.length === 0 && (
                                                    <span className="text-sm text-muted-foreground">
                                                        {t('No blogs yet')}
                                                    </span>
                                                )}
                                                {member.blogs.map((access) => (
                                                    <Badge
                                                        key={access.blog}
                                                        variant="outline"
                                                    >
                                                        {access.blog} ·{' '}
                                                        {labels.role[
                                                            access.role
                                                        ] ?? access.role}
                                                    </Badge>
                                                ))}
                                            </div>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                {t('All blogs')}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {member.workspace_role !== 'owner' && (
                                            <div className="flex justify-end gap-1">
                                                {isOwner && (
                                                    <TransferDialog
                                                        member={member}
                                                    />
                                                )}
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={t(
                                                                'Remove',
                                                            )}
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    }
                                                    title={
                                                        member.id ===
                                                        currentUserId
                                                            ? t(
                                                                  'Leave this workspace?',
                                                              )
                                                            : t(
                                                                  'Remove :name?',
                                                                  {
                                                                      name: member.name,
                                                                  },
                                                              )
                                                    }
                                                    description={t(
                                                        'They lose access to every blog in this workspace.',
                                                    )}
                                                    confirmLabel={t('Remove')}
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            destroyMember({
                                                                workspace,
                                                                member: member.id,
                                                            }),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </SettingsSection>

            {invitations.length > 0 && (
                <SettingsSection title={t('Pending invitations')}>
                    <ul className="divide-y rounded-lg border">
                        {invitations.map((invitation) => (
                            <li
                                key={invitation.id}
                                className="flex flex-wrap items-center justify-between gap-4 p-4 text-sm"
                            >
                                <div>
                                    <p className="font-medium">
                                        {invitation.email}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {
                                            labels.workspaceRole[
                                                invitation.workspace_role
                                            ]
                                        }
                                        {invitation.blog &&
                                            ` · ${invitation.blog} (${labels.role[invitation.role ?? ''] ?? invitation.role})`}
                                        {' · '}
                                        {invitation.expired
                                            ? t('Expired')
                                            : t('Expires :date', {
                                                  date: formatDate(
                                                      invitation.expires_at,
                                                  ),
                                              })}
                                    </p>
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(
                                                resend({
                                                    workspace,
                                                    invitation: invitation.id,
                                                }),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <RotateCw />
                                        {t('Resend')}
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" size="sm">
                                                {t('Revoke')}
                                            </Button>
                                        }
                                        title={t('Revoke this invitation?')}
                                        confirmLabel={t('Revoke')}
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                destroyInvitation({
                                                    workspace,
                                                    invitation: invitation.id,
                                                }),
                                                {
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                </SettingsSection>
            )}
        </>
    );
}

Members.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'Members' }],
};
