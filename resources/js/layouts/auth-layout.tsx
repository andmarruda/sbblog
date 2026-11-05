import { Link, usePage } from '@inertiajs/react';
import { CalendarClock, Layers, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/app/brand-mark';
import { FlashToaster } from '@/components/app/flash-toaster';
import { PreferencesMenu } from '@/components/app/preferences-menu';
import { useTranslation } from '@/hooks/use-translation';
import { about, home } from '@/routes';

type Props = {
    children: ReactNode;
    /** Translation key. */
    title?: string;
    /** Translation key. */
    description?: string;
};

const features = [
    {
        icon: Layers,
        title: 'Every blog in one workspace',
        description: 'Teams, roles and permissions for each publication.',
    },
    {
        icon: CalendarClock,
        title: 'Publishing on schedule',
        description: 'Write, review and schedule. It goes live on time.',
    },
    {
        icon: ShieldCheck,
        title: 'Security without friction',
        description: 'Passkeys, two-factor authentication and trusted devices.',
    },
];

export default function AuthLayout({ children, title, description }: Props) {
    const { t } = useTranslation();
    const { name } = usePage().props;

    return (
        <>
            <div className="grid min-h-svh lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                <aside className="relative hidden overflow-hidden bg-[oklch(0.13_0.02_275)] text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                    <div
                        aria-hidden="true"
                        className="auth-grid absolute inset-0"
                    />
                    <div
                        aria-hidden="true"
                        className="absolute -top-40 -left-32 size-[36rem] rounded-full bg-indigo-500/30 blur-[120px]"
                    />
                    <div
                        aria-hidden="true"
                        className="absolute -right-40 -bottom-48 size-[32rem] rounded-full bg-violet-600/25 blur-[120px]"
                    />

                    <Link
                        href={home()}
                        className="relative flex w-fit items-center gap-2.5"
                    >
                        <BrandMark className="size-9" />
                        <span className="text-lg font-semibold tracking-tight">
                            {name}
                        </span>
                    </Link>

                    <div className="relative max-w-lg space-y-10">
                        <div className="space-y-5">
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-indigo-200 backdrop-blur">
                                <span className="size-1.5 rounded-full bg-amber-300" />
                                {t('Content platform')}
                            </span>
                            <h1 className="bg-gradient-to-br from-white via-white to-indigo-200/70 bg-clip-text text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-transparent xl:text-5xl">
                                {t(
                                    'Publish with the power of a full newsroom.',
                                )}
                            </h1>
                            <p className="text-lg text-pretty text-white/60">
                                {t(
                                    'Blogs, teams and content in one console, built for people who take publishing seriously.',
                                )}
                            </p>
                        </div>

                        <ul className="space-y-5">
                            {features.map((feature) => (
                                <li key={feature.title} className="flex gap-4">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-indigo-200">
                                        <feature.icon className="size-5" />
                                    </span>
                                    <div>
                                        <p className="font-medium">
                                            {t(feature.title)}
                                        </p>
                                        <p className="text-sm text-white/55">
                                            {t(feature.description)}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative text-sm text-white/40">
                        © {new Date().getFullYear()} {name} ·{' '}
                        <Link
                            href={about()}
                            className="underline-offset-4 hover:text-white hover:underline"
                        >
                            {t('About')}
                        </Link>
                    </p>
                </aside>

                <main className="relative flex flex-col bg-background">
                    <header className="flex items-center justify-between p-4 md:p-6 lg:justify-end">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 lg:hidden"
                        >
                            <BrandMark />
                            <span className="font-semibold tracking-tight">
                                {name}
                            </span>
                        </Link>
                        <PreferencesMenu />
                    </header>

                    <div className="flex flex-1 items-center justify-center px-4 pb-16 md:px-10">
                        <div className="w-full max-w-sm space-y-8">
                            {(title || description) && (
                                <div className="space-y-2">
                                    {title && (
                                        <h2 className="text-2xl font-semibold tracking-tight">
                                            {t(title)}
                                        </h2>
                                    )}
                                    {description && (
                                        <p className="text-muted-foreground">
                                            {t(description)}
                                        </p>
                                    )}
                                </div>
                            )}
                            {children}
                        </div>
                    </div>
                </main>
            </div>
            <FlashToaster />
        </>
    );
}
