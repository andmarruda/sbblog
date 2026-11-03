import { Form } from '@inertiajs/react';
import { DangerZone } from '@/components/app/danger-zone';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
import { SlugInput } from '@/components/app/slug-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
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
import { useLabels } from '@/hooks/use-labels';
import { useTranslation } from '@/hooks/use-translation';
import { useState } from 'react';
import { destroy, update } from '@/routes/workspaces/settings';

type Props = {
    workspace: {
        id: string;
        name: string;
        slug: string;
        plan: string;
        two_factor_policy: string;
    };
    twoFactorPolicies: string[];
    isOwner: boolean;
};

export default function WorkspaceGeneral({
    workspace,
    twoFactorPolicies,
    isOwner,
}: Props) {
    const { t } = useTranslation();
    const labels = useLabels();
    const [slug, setSlug] = useState(workspace.slug);

    return (
        <>
            <PageTitle title={t('Workspace settings')} />
            <SettingsSection
                title={t('General')}
                description={t('Name, address and security policy.')}
            >
                <Form
                    action={update(workspace.slug)}
                    options={{ preserveScroll: true }}
                    className="grid max-w-2xl gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={workspace.name}
                                    required
                                />
                            </FormField>
                            <FormField
                                id="slug"
                                label={t('Address')}
                                hint={t(
                                    'Used in console URLs. Changing it breaks old links.',
                                )}
                                error={errors.slug}
                            >
                                <SlugInput
                                    id="slug"
                                    name="slug"
                                    value={slug}
                                    onChange={setSlug}
                                    required
                                />
                            </FormField>
                            <FormField
                                id="two_factor_policy"
                                label={t('Two-factor authentication')}
                                hint={t(
                                    'Some permissions always require it, whatever this setting.',
                                )}
                                error={errors.two_factor_policy}
                            >
                                <Select
                                    name="two_factor_policy"
                                    defaultValue={workspace.two_factor_policy}
                                >
                                    <SelectTrigger
                                        id="two_factor_policy"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {twoFactorPolicies.map((policy) => (
                                            <SelectItem
                                                key={policy}
                                                value={policy}
                                            >
                                                {labels.twoFactorPolicy[
                                                    policy
                                                ] ?? policy}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                {t('Plan')}:{' '}
                                <Badge variant="secondary">
                                    {workspace.plan}
                                </Badge>
                            </div>
                            <div>
                                <Button type="submit" disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </SettingsSection>

            {isOwner && (
                <DangerZone
                    title={t('Delete workspace')}
                    description={t(
                        'Every blog in it stops being reachable from the console.',
                    )}
                >
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">
                                {t('Delete workspace')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('Delete workspace?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Type :text and your password to confirm.',
                                        { text: workspace.slug },
                                    )}
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                action={destroy(workspace.slug)}
                                resetOnError
                                className="grid gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <FormField
                                            id="confirmation"
                                            label={t('Workspace address')}
                                            error={errors.confirmation}
                                        >
                                            <Input
                                                id="confirmation"
                                                name="confirmation"
                                                autoComplete="off"
                                                required
                                            />
                                        </FormField>
                                        <FormField
                                            id="delete-password"
                                            label={t('Password')}
                                            error={errors.password}
                                        >
                                            <PasswordInput
                                                id="delete-password"
                                                name="password"
                                                autoComplete="current-password"
                                                required
                                            />
                                        </FormField>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button
                                                    variant="outline"
                                                    type="button"
                                                >
                                                    {t('Cancel')}
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {t('Delete workspace')}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </DangerZone>
            )}
        </>
    );
}

WorkspaceGeneral.layout = {
    breadcrumbs: [{ title: 'Workspace settings' }, { title: 'General' }],
};
