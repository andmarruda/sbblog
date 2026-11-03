import { Form, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { appearanceOptions } from '@/components/app/appearance-menu';
import { FormField } from '@/components/app/form-field';
import { localeName } from '@/components/app/locale-menu';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { edit, destroy, update } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    currentLocale: string;
    ownsWorkspaces: boolean;
    status?: string;
};

export default function Profile({
    mustVerifyEmail,
    currentLocale,
    ownsWorkspaces,
    status,
}: Props) {
    const { t } = useTranslation();
    const { auth, locales } = usePage().props;
    const { appearance, updateAppearance } = useAppearance();
    const [locale, setLocale] = useState(currentLocale);
    const [email, setEmail] = useState(auth.user?.email ?? '');
    const emailChanged =
        email.trim().toLowerCase() !== (auth.user?.email ?? '').toLowerCase();

    return (
        <>
            <PageTitle title={t('Profile')} />

            <SettingsSection
                title={t('Profile')}
                description={t('Your name, email address and language.')}
            >
                <Form
                    action={update()}
                    options={{ preserveScroll: true }}
                    className="grid gap-4"
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
                                    defaultValue={auth.user?.name}
                                    autoComplete="name"
                                    required
                                />
                            </FormField>
                            <FormField
                                id="email"
                                label={t('Email')}
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={auth.user?.email}
                                    onChange={(event) =>
                                        setEmail(event.target.value)
                                    }
                                    autoComplete="email"
                                    required
                                />
                            </FormField>
                            {emailChanged && (
                                <FormField
                                    id="profile-current-password"
                                    label={t('Current password')}
                                    hint={t(
                                        'Needed to change your email address.',
                                    )}
                                    error={errors.current_password}
                                >
                                    <PasswordInput
                                        id="profile-current-password"
                                        name="current_password"
                                        autoComplete="current-password"
                                        required
                                    />
                                </FormField>
                            )}
                            {mustVerifyEmail && (
                                <Alert>
                                    <AlertDescription>
                                        <span>
                                            {t(
                                                'Your email address is unverified.',
                                            )}{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="underline underline-offset-4"
                                            >
                                                {t('Resend verification email')}
                                            </Link>
                                        </span>
                                        {status ===
                                            'verification-link-sent' && (
                                            <span className="block text-green-600 dark:text-green-400">
                                                {t(
                                                    'A new verification link has been sent to your email.',
                                                )}
                                            </span>
                                        )}
                                    </AlertDescription>
                                </Alert>
                            )}
                            <FormField
                                id="locale"
                                label={t('Language')}
                                error={errors.locale}
                            >
                                <Select
                                    name="locale"
                                    value={locale}
                                    onValueChange={setLocale}
                                >
                                    <SelectTrigger
                                        id="locale"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {locales.map((code) => (
                                            <SelectItem key={code} value={code}>
                                                {localeName(code)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <div>
                                <Button type="submit" disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </SettingsSection>

            <SettingsSection
                title={t('Appearance')}
                description={t('Saved in this browser.')}
            >
                <div className="inline-flex gap-1 rounded-lg bg-muted p-1">
                    {appearanceOptions.map(({ value, label, icon: Icon }) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => updateAppearance(value)}
                            aria-pressed={appearance === value}
                            className={cn(
                                'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors',
                                appearance === value
                                    ? 'bg-background shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <Icon className="size-4" />
                            {t(label)}
                        </button>
                    ))}
                </div>
            </SettingsSection>

            <SettingsSection
                title={t('Delete account')}
                description={t(
                    'Your account will be deactivated and you will be logged out.',
                )}
            >
                {ownsWorkspaces ? (
                    <Alert>
                        <AlertDescription>
                            {t(
                                'Transfer or delete the workspaces you own before deleting your account.',
                            )}
                        </AlertDescription>
                    </Alert>
                ) : (
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">
                                {t('Delete account')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('Delete your account?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('Enter your password to confirm.')}
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                action={destroy()}
                                options={{ preserveScroll: true }}
                                resetOnError
                                className="grid gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
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
                                                {t('Delete account')}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                )}
            </SettingsSection>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        { title: 'Account settings', href: edit().url },
        { title: 'Profile' },
    ],
};
