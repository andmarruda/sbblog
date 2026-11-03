import { Form } from '@inertiajs/react';
import { Monitor, Smartphone } from 'lucide-react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
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
import { useTranslation } from '@/hooks/use-translation';
import { edit as editProfile } from '@/routes/profile';
import { destroyOthers } from '@/routes/sessions';

type Session = {
    id: string;
    ip_address: string | null;
    agent: { browser: string | null; platform: string | null; mobile: boolean };
    is_current: boolean;
    last_active_at: string;
};

export default function Sessions({ sessions }: { sessions: Session[] }) {
    const { t, formatDate } = useTranslation();
    const hasOthers = sessions.some((session) => !session.is_current);

    return (
        <>
            <PageTitle title={t('Sessions')} />
            <SettingsSection
                title={t('Browser sessions')}
                description={t(
                    "Where you are signed in. Log out the ones you don't recognise.",
                )}
            >
                <ul className="divide-y rounded-lg border">
                    {sessions.map((session) => {
                        const Icon = session.agent.mobile
                            ? Smartphone
                            : Monitor;

                        return (
                            <li
                                key={session.id}
                                className="flex items-center gap-4 p-4"
                            >
                                <Icon className="size-6 shrink-0 text-muted-foreground" />
                                <div className="min-w-0 flex-1 text-sm">
                                    <p className="font-medium">
                                        {[
                                            session.agent.browser,
                                            session.agent.platform,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ') || t('Unknown device')}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {session.ip_address ?? '—'} ·{' '}
                                        {t('Last active :date', {
                                            date: formatDate(
                                                session.last_active_at,
                                                true,
                                            ),
                                        })}
                                    </p>
                                </div>
                                {session.is_current && (
                                    <Badge variant="secondary">
                                        {t('This device')}
                                    </Badge>
                                )}
                            </li>
                        );
                    })}
                </ul>

                {hasOthers && (
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="outline">
                                {t('Log out other sessions')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('Log out other sessions')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('Enter your password to confirm.')}
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                action={destroyOthers()}
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                                resetOnError
                                className="grid gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <FormField
                                            id="sessions-password"
                                            label={t('Password')}
                                            error={errors.password}
                                        >
                                            <PasswordInput
                                                id="sessions-password"
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
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {t('Log out other sessions')}
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

Sessions.layout = {
    breadcrumbs: [
        { title: 'Account settings', href: editProfile().url },
        { title: 'Sessions' },
    ],
};
