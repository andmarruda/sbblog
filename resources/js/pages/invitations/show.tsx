import { Form, Link } from '@inertiajs/react';
import { PageTitle } from '@/components/app/page-title';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { login, logout, register } from '@/routes';
import { accept } from '@/routes/invitations';
import { usePage } from '@inertiajs/react';

type Props = {
    token: string;
    invitation: {
        email: string;
        workspace: string | null;
        inviter: string | null;
        blog: string | null;
        valid: boolean;
    } | null;
    emailMatches: boolean;
};

export default function ShowInvitation({
    token,
    invitation,
    emailMatches,
}: Props) {
    const { t } = useTranslation();
    const { auth } = usePage().props;

    if (!invitation || !invitation.valid) {
        return (
            <>
                <PageTitle title={t('Invitation')} />
                <Alert>
                    <AlertDescription>
                        {t(
                            'This invitation is no longer valid. Ask for a new one.',
                        )}
                    </AlertDescription>
                </Alert>
            </>
        );
    }

    return (
        <>
            <PageTitle title={t('Invitation')} />
            <div className="space-y-6 text-center">
                <p>
                    {invitation.inviter
                        ? t(':name invited you to join :workspace.', {
                              name: invitation.inviter,
                              workspace: invitation.workspace ?? '',
                          })
                        : t('You were invited to join :workspace.', {
                              workspace: invitation.workspace ?? '',
                          })}
                </p>

                {!auth.user && (
                    <div className="grid gap-2">
                        <p className="text-sm text-muted-foreground">
                            {t('Continue with :email.', {
                                email: invitation.email,
                            })}
                        </p>
                        <Button asChild>
                            <Link href={register()}>{t('Create account')}</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={login()}>{t('Log in')}</Link>
                        </Button>
                    </div>
                )}

                {auth.user && emailMatches && (
                    <Form action={accept(token)}>
                        {({ processing, errors }) => (
                            <div className="grid gap-2">
                                {errors.invitation && (
                                    <p className="text-sm text-destructive">
                                        {errors.invitation}
                                    </p>
                                )}
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {t('Accept invitation')}
                                </Button>
                            </div>
                        )}
                    </Form>
                )}

                {auth.user && !emailMatches && (
                    <div className="grid gap-2">
                        <Alert>
                            <AlertDescription>
                                {t(
                                    'This invitation was sent to :email. Log in with that account to accept it.',
                                    {
                                        email: invitation.email,
                                    },
                                )}
                            </AlertDescription>
                        </Alert>
                        <Button variant="outline" asChild>
                            <Link href={logout()} as="button">
                                {t('Log out')}
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

ShowInvitation.layout = {
    title: "You're invited",
};
