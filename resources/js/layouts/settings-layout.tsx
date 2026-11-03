import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { SectionLayout } from '@/layouts/section-layout';
import { edit as editProfile } from '@/routes/profile';
import { index as sessions } from '@/routes/sessions';
import { edit as editPassword } from '@/routes/user-password';

export default function SettingsLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();

    return (
        <SectionLayout
            title={t('Account settings')}
            description={t('Manage your profile, password and sessions.')}
            items={[
                { title: 'Profile', href: editProfile().url },
                { title: 'Password', href: editPassword().url },
                { title: 'Sessions', href: sessions().url },
            ]}
        >
            <div className="max-w-2xl space-y-10">{children}</div>
        </SectionLayout>
    );
}
