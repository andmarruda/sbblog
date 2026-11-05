import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, Quote } from 'lucide-react';
import { BrandMark } from '@/components/app/brand-mark';
import { PageTitle } from '@/components/app/page-title';
import { PreferencesMenu } from '@/components/app/preferences-menu';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, home, login } from '@/routes';

/**
 * Anderson's story, told in chapters. Every fact comes from his LinkedIn profile,
 * the Sysborg brand manual or the v1 README; keep it that way when editing.
 */
const chapters = [
    {
        title: 'It started with work done by hand',
        paragraphs: [
            'In 2009, in Ribeirão Preto, Anderson joined MC Administradora, a company that still ran much of its work by hand. He designed and built its first custom ERP and brought those scattered workflows into a single system.',
            'To help the team deliver faster, he wrote his own PHP framework. It was the first sign of a habit that still defines his work: when the tools get in the way, build better tools.',
        ],
    },
    {
        title: 'When the system is the business',
        paragraphs: [
            'In 2013 he moved to Grand Credit Cursos and took the lead on the ERP behind finance, sales and academic operations. There, a slow report or a failed payment was not just a bug. It was the business standing still.',
            'He rebuilt his framework, and new features started shipping an estimated 40 to 50% faster. He tuned PostgreSQL until the bottlenecks were gone. When attackers tried to brute-force their way in, he wrote Python scripts that read the logs and blocked them in real time.',
        ],
        quote: 'Every architecture looks good until it meets operational reality.',
    },
    {
        title: 'Building something of his own',
        paragraphs: [
            'In 2018 he founded Sysborg to turn that experience into products of his own. The first was Econorg Personal, a personal finance app built to help Brazilians organize their budget, get out of debt and reach their goals.',
            'He also started teaching. His flagship course is on event-driven architecture, with Laravel, React and Vue.js courses built on the same foundation, the same foundation that would later shape SBBlog.',
        ],
    },
    {
        title: 'Leading, and going back to school',
        paragraphs: [
            'As tech lead at Fairy, he built an e-commerce builder from scratch, reviewed code, interviewed candidates and mentored the team. He also assessed customer projects before they were built, pointing out risks while they were still cheap to fix. At Fahrenheit Marketing he worked on dynamic campaigns, Google Ads API integrations and AI that analyzes phone calls.',
            'And after more than a decade of practice, he went back to school, finishing degrees in Systems Analysis and Development and in Data Science in 2025. Today he is a Senior Full Stack Engineer at Tough Leaf, and he is still building Sysborg.',
        ],
    },
];

const positioning = [
    'Technical risk consulting',
    'Technical due diligence',
    'Strategic advisory',
    'Architecture and delivery',
];

const links = [
    {
        label: 'LinkedIn',
        href: 'https://www.linkedin.com/in/anderson-a-sborg/',
    },
    { label: 'GitHub', href: 'https://github.com/andmarruda' },
    { label: 'Blog', href: 'https://www.andersonarruda.com.br' },
    { label: 'Sysborg', href: 'https://www.sysborg.com.br' },
];

export default function About() {
    const { t } = useTranslation();
    const { auth, name } = usePage().props;

    return (
        <div className="min-h-svh bg-background">
            <PageTitle title={t('About')} />

            {/* Hero */}
            <section className="relative overflow-hidden bg-[oklch(0.13_0.02_275)] text-white">
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

                <header className="relative mx-auto flex max-w-6xl items-center justify-between px-4 py-5 md:px-8">
                    <Link href={home()} className="flex items-center gap-2.5">
                        <BrandMark className="size-9" />
                        <span className="text-lg font-semibold tracking-tight">
                            {name}
                        </span>
                    </Link>
                    <div className="flex items-center gap-2">
                        <div className="text-white [&_button:hover]:bg-white/10 [&_button:hover]:text-white">
                            <PreferencesMenu />
                        </div>
                        <Button
                            asChild
                            size="sm"
                            className="bg-white text-[oklch(0.13_0.02_275)] hover:bg-white/90"
                        >
                            <Link href={auth.user ? dashboard() : login()}>
                                {auth.user
                                    ? t('Open the console')
                                    : t('Log in')}
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </header>

                <div className="relative mx-auto max-w-6xl px-4 pt-16 pb-24 md:px-8 md:pt-24 md:pb-32">
                    <span className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-indigo-200 backdrop-blur">
                        <span className="size-1.5 rounded-full bg-amber-300" />
                        {t('About SBBlog')}
                    </span>
                    <h1 className="mt-6 max-w-4xl bg-gradient-to-br from-white via-white to-indigo-200/70 bg-clip-text text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-transparent md:text-6xl">
                        {t(
                            'SBBlog was born from years of watching software meet reality.',
                        )}
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-pretty text-white/60">
                        {t(
                            'This is the story of Anderson Arruda, the developer from Ribeirão Preto behind SBBlog, and of Sysborg, the company he founded to put what he learned to work.',
                        )}
                    </p>
                </div>
            </section>

            {/* Anderson's story */}
            <article className="mx-auto max-w-2xl px-4 py-20 md:py-28">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-400 to-violet-600 text-xl font-semibold text-white">
                        AA
                    </div>
                    <div>
                        <p className="text-sm font-medium tracking-wide text-indigo-600 uppercase dark:text-indigo-300">
                            {t('The story')}
                        </p>
                        <h2 className="text-2xl font-semibold tracking-tight">
                            Anderson Arruda
                        </h2>
                        <ul className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            {links.map((link) => (
                                <li key={link.href}>
                                    <a
                                        href={link.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-0.5 text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                    >
                                        {link.label}
                                        <ArrowUpRight className="size-3.5" />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                {chapters.map((chapter, index) => (
                    <section key={chapter.title} className="mt-16">
                        <p className="font-mono text-sm text-muted-foreground">
                            {String(index + 1).padStart(2, '0')}
                        </p>
                        <h3 className="mt-2 text-2xl font-semibold tracking-tight text-balance md:text-3xl">
                            {t(chapter.title)}
                        </h3>
                        {chapter.paragraphs.map((paragraph) => (
                            <p
                                key={paragraph}
                                className="mt-5 text-lg leading-relaxed text-pretty text-muted-foreground"
                            >
                                {t(paragraph)}
                            </p>
                        ))}
                        {chapter.quote && (
                            <figure className="mt-10 border-l-4 border-indigo-500 py-1 pl-6">
                                <blockquote className="text-2xl leading-snug font-semibold tracking-tight text-balance">
                                    {t(chapter.quote)}
                                </blockquote>
                                <figcaption className="mt-3 text-sm text-muted-foreground">
                                    {t(
                                        'The conviction those years left behind, and later the line that defines Sysborg.',
                                    )}
                                </figcaption>
                            </figure>
                        )}
                    </section>
                ))}

                <section className="mt-20 rounded-2xl border bg-muted/40 p-6 md:p-10">
                    <div className="flex items-center gap-3">
                        <BrandMark />
                        <h3 className="text-2xl font-semibold tracking-tight">
                            {t('Why SBBlog exists')}
                        </h3>
                    </div>
                    <p className="mt-5 text-lg leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'SBBlog was his first open-source Laravel project, written when there was still a lot to learn. Its first README says so, with the excitement of someone just getting started.',
                        )}
                    </p>
                    <p className="mt-4 text-lg leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'Version 2 is what happens when the same person comes back years later. It was rewritten from scratch around the ideas he teaches: the console only writes, every change becomes an event, and each blog keeps its own data on Cloudflare D1, R2 or a local SQLite. No single server has to carry the weight of every blog.',
                        )}
                    </p>
                    <p className="mt-4 text-lg leading-relaxed font-medium text-pretty text-foreground">
                        {t(
                            'It is not a demo of event-driven architecture. It is that architecture, built to carry real blogs.',
                        )}
                    </p>
                </section>
            </article>

            {/* Sysborg */}
            <section className="relative overflow-hidden bg-[#1A1A1A] text-white">
                <div
                    aria-hidden="true"
                    className="absolute -right-24 -bottom-24 hidden size-[28rem] rotate-45 bg-gradient-to-br from-[#CC1F1F] to-[#8B0000] opacity-90 md:block"
                />
                <div className="relative mx-auto max-w-6xl px-4 py-20 md:px-8 md:py-28">
                    <p className="text-2xl font-black tracking-[0.2em]">
                        SYS<span className="text-[#CC1F1F]">B</span>ORG
                    </p>
                    <p className="mt-1 text-sm text-white/50">
                        Sysborg Soluções Tecnológicas
                    </p>

                    <figure className="mt-12 max-w-3xl">
                        <Quote className="size-8 text-[#CC1F1F]" />
                        <blockquote className="mt-4 text-3xl leading-tight font-semibold tracking-tight text-balance md:text-4xl">
                            {t(
                                'Every architecture looks good until it meets operational reality.',
                            )}
                        </blockquote>
                    </figure>

                    <p className="mt-10 max-w-3xl text-lg leading-relaxed text-pretty text-white/70">
                        {t(
                            'Sysborg began in 2018 building products. Over time, the work Anderson did best became its trade: looking at a project before the money is spent, finding the risks no one wants to see, and saying so clearly.',
                        )}
                    </p>

                    <div className="mt-14 grid max-w-4xl gap-10 md:grid-cols-2">
                        <div>
                            <h3 className="text-sm font-semibold tracking-wide text-[#CC1F1F] uppercase">
                                {t('Who we are')}
                            </h3>
                            <p className="mt-3 leading-relaxed text-white/70">
                                {t(
                                    'A technical consultancy specialized in software architecture, systems integration and reducing technology risk. We act as independent advisors for companies that need to make critical technical decisions with confidence and clarity.',
                                )}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-semibold tracking-wide text-[#CC1F1F] uppercase">
                                {t('Mission')}
                            </h3>
                            <p className="mt-3 leading-relaxed text-white/70">
                                {t(
                                    'Protect companies from poorly grounded technology investments, with independent, careful analysis based on real evidence, before the problem becomes irreversible.',
                                )}
                            </p>
                        </div>
                    </div>

                    <ul className="mt-12 flex max-w-3xl flex-wrap gap-2">
                        {positioning.map((item) => (
                            <li
                                key={item}
                                className="rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-sm"
                            >
                                {t(item)}
                            </li>
                        ))}
                    </ul>

                    <Button
                        asChild
                        size="lg"
                        className="mt-12 bg-[#CC1F1F] text-white hover:bg-[#8B0000]"
                    >
                        <a
                            href="https://www.sysborg.com.br"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {t('Visit sysborg.com.br')}
                            <ArrowUpRight />
                        </a>
                    </Button>
                </div>
            </section>

            <footer className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between md:px-8">
                <p>
                    © {new Date().getFullYear()} {name} ·{' '}
                    {t('A Sysborg product')}
                </p>
                <p>{t('Made in Ribeirão Preto, Brazil.')}</p>
            </footer>
        </div>
    );
}
