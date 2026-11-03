import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { SectionLayout } from '@/layouts/section-layout';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as sessions } from '@/routes/sessions';
import { index as tokens } from '@/routes/tokens';
import { edit as editPassword } from '@/routes/user-password';

export default function SettingsLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();

    return (
        <SectionLayout
            title={t('Account settings')}
            description={t(
                'Manage your profile, password, security and sessions.',
            )}
            items={[
                { title: 'Profile', href: editProfile().url },
                { title: 'Password', href: editPassword().url },
                { title: 'Security', href: editSecurity().url },
                { title: 'Sessions', href: sessions().url },
                { title: 'API tokens', href: tokens().url },
            ]}
        >
            <div className="max-w-2xl space-y-10">{children}</div>
        </SectionLayout>
    );
}
