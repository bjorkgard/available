import { Head, Link, usePage } from '@inertiajs/react';
import {
    motion,
    useInView,
    useReducedMotion,
    useScroll,
    useTransform,
} from 'motion/react';
import { useMemo, useRef } from 'react';
import { useTranslation } from 'react-i18next';

import AppLogoIcon from '@/components/app-logo-icon';
import { LanguageSelector } from '@/components/language-selector';
import { calendar, login, register } from '@/routes';

// Simple seeded pseudo-random for deterministic booking positions
function seededRandom(seed: number): number {
    const x = Math.sin(seed * 9301 + 49297) * 49271;

    return x - Math.floor(x);
}

function CalendarCard({
    variant,
    delay = 0,
}: {
    variant: 'month' | 'week' | 'day';
    delay?: number;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const isInView = useInView(ref, { once: true, amount: 0.3 });
    const reduce = useReducedMotion();

    const labels = {
        month: { title: 'Månad', cols: 7, rows: 5 },
        week: { title: 'Vecka', cols: 7, rows: 1 },
        day: { title: 'Dag', cols: 3, rows: 1 },
    };

    const config = labels[variant];

    const bookings = useMemo(
        () =>
            Array.from(
                {
                    length:
                        variant === 'month' ? 12 : variant === 'week' ? 8 : 5,
                },
                (_, i) => ({
                    id: i,
                    col: Math.floor(seededRandom(i * 7 + 1) * config.cols),
                    row: Math.floor(seededRandom(i * 7 + 2) * config.rows),
                    color: [
                        'bg-emerald-400',
                        'bg-sky-400',
                        'bg-amber-400',
                        'bg-rose-400',
                    ][i % 4],
                    width:
                        variant === 'day'
                            ? 'w-full'
                            : seededRandom(i * 7 + 3) > 0.5
                              ? 'w-3/4'
                              : 'w-1/2',
                }),
            ),
        [variant, config.cols, config.rows],
    );

    return (
        <motion.div
            ref={ref}
            initial={reduce ? false : { opacity: 0, y: 40, rotateX: 12 }}
            animate={
                isInView
                    ? { opacity: 1, y: 0, rotateX: 0 }
                    : reduce
                      ? { opacity: 1 }
                      : undefined
            }
            transition={{
                duration: 0.8,
                delay: reduce ? 0 : delay,
                ease: [0.16, 1, 0.3, 1],
            }}
            className="group relative overflow-hidden rounded-xl border border-border bg-card p-4 shadow-lg shadow-black/5 dark:shadow-black/20"
            style={{ perspective: '1000px' }}
        >
            {/* Header */}
            <div className="mb-3 flex items-center justify-between">
                <span className="text-xs font-medium text-muted-foreground">
                    {config.title}
                </span>
                <div className="flex gap-1">
                    <div className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                    <div className="h-1.5 w-1.5 rounded-full bg-sky-400" />
                    <div className="h-1.5 w-1.5 rounded-full bg-amber-400" />
                </div>
            </div>

            {/* Grid */}
            <div
                className="grid gap-1"
                style={{
                    gridTemplateColumns: `repeat(${config.cols}, 1fr)`,
                    gridTemplateRows: `repeat(${variant === 'month' ? 5 : variant === 'week' ? 8 : 10}, 1fr)`,
                }}
            >
                {variant === 'month' &&
                    Array.from({ length: 35 }, (_, i) => (
                        <div
                            key={i}
                            className="relative flex aspect-square flex-col items-start rounded-sm bg-muted/50 p-0.5"
                        >
                            <span className="text-[6px] text-muted-foreground">
                                {(i % 28) + 1}
                            </span>
                            {bookings.some(
                                (b) =>
                                    b.col === i % 7 &&
                                    b.row === Math.floor(i / 7),
                            ) && (
                                <div
                                    className={`mt-auto h-1 w-full rounded-full ${bookings.find((b) => b.col === i % 7 && b.row === Math.floor(i / 7))?.color}`}
                                />
                            )}
                        </div>
                    ))}
                {variant === 'week' &&
                    Array.from({ length: 56 }, (_, i) => (
                        <div
                            key={i}
                            className="relative h-3 rounded-sm bg-muted/30"
                        >
                            {bookings.some(
                                (b) =>
                                    b.col === i % 7 &&
                                    Math.floor(i / 7) >= b.row * 2 &&
                                    Math.floor(i / 7) < b.row * 2 + 2,
                            ) && (
                                <div
                                    className={`absolute inset-0 rounded-sm ${bookings.find((b) => b.col === i % 7 && Math.floor(i / 7) >= b.row * 2 && Math.floor(i / 7) < b.row * 2 + 2)?.color} opacity-80`}
                                />
                            )}
                        </div>
                    ))}
                {variant === 'day' &&
                    Array.from({ length: 30 }, (_, i) => (
                        <div
                            key={i}
                            className="relative h-4 rounded-sm bg-muted/30"
                        >
                            {bookings.some(
                                (b) =>
                                    b.col === i % 3 &&
                                    Math.floor(i / 3) >= b.row * 2 &&
                                    Math.floor(i / 3) < b.row * 2 + 3,
                            ) && (
                                <div
                                    className={`absolute inset-0 rounded-sm ${bookings.find((b) => b.col === i % 3 && Math.floor(i / 3) >= b.row * 2 && Math.floor(i / 3) < b.row * 2 + 3)?.color} opacity-80`}
                                />
                            )}
                        </div>
                    ))}
            </div>
        </motion.div>
    );
}

function StepCard({
    number,
    title,
    description,
    delay = 0,
}: {
    number: string;
    title: string;
    description: string;
    delay?: number;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const isInView = useInView(ref, { once: true, amount: 0.4 });
    const reduce = useReducedMotion();

    return (
        <motion.div
            ref={ref}
            initial={reduce ? false : { opacity: 0, y: 24 }}
            animate={
                isInView
                    ? { opacity: 1, y: 0 }
                    : reduce
                      ? { opacity: 1 }
                      : undefined
            }
            transition={{
                duration: 0.6,
                delay: reduce ? 0 : delay,
                ease: [0.16, 1, 0.3, 1],
            }}
            className="flex flex-col gap-3"
        >
            <div className="flex size-10 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground">
                {number}
            </div>
            <h3 className="text-lg font-semibold text-foreground">{title}</h3>
            <p className="text-sm leading-relaxed text-muted-foreground">
                {description}
            </p>
        </motion.div>
    );
}

export default function Welcome() {
    const { t } = useTranslation();
    const { auth, currentCongregation } = usePage().props;
    const calendarUrl = currentCongregation
        ? calendar((currentCongregation as { slug: string }).slug)
        : '/';

    const heroRef = useRef<HTMLDivElement>(null);
    const reduce = useReducedMotion();

    const { scrollYProgress } = useScroll({
        target: heroRef,
        offset: ['start start', 'end start'],
    });

    const heroY = useTransform(scrollYProgress, [0, 1], [0, -60]);
    const heroOpacity = useTransform(scrollYProgress, [0, 0.8], [1, 0]);

    // Calendar 3D explosion on scroll
    const calendarSectionRef = useRef<HTMLDivElement>(null);
    const { scrollYProgress: calendarProgress } = useScroll({
        target: calendarSectionRef,
        offset: ['start end', 'center center'],
    });

    const card1RotateY = useTransform(calendarProgress, [0, 1], [-25, 0]);
    const card3RotateY = useTransform(calendarProgress, [0, 1], [25, 0]);
    const card1X = useTransform(calendarProgress, [0, 1], [-80, 0]);
    const card2Z = useTransform(calendarProgress, [0, 1], [-40, 0]);
    const card3X = useTransform(calendarProgress, [0, 1], [80, 0]);
    const cardsScale = useTransform(
        calendarProgress,
        [0, 0.5, 1],
        [0.85, 0.95, 1],
    );

    return (
        <>
            <Head title={t('Välkommen')} />
            <div className="min-h-screen bg-background text-foreground">
                {/* Navigation */}
                <nav className="fixed top-0 z-50 w-full border-b border-border/50 bg-background/80 backdrop-blur-xl">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                        <div className="flex items-center gap-2">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-primary">
                                <AppLogoIcon className="size-5 fill-current text-primary-foreground" />
                            </div>
                            <span className="font-semibold text-foreground">
                                Salbokning
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <LanguageSelector />
                            {auth.user ? (
                                <Link
                                    href={calendarUrl}
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-transform active:scale-[0.97]"
                                >
                                    {t('Kalender')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                                    >
                                        {t('Logga in')}
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-transform active:scale-[0.97]"
                                    >
                                        {t('Registrera')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero */}
                <motion.section
                    ref={heroRef}
                    style={
                        reduce ? undefined : { y: heroY, opacity: heroOpacity }
                    }
                    className="relative flex min-h-[100dvh] items-center pt-16"
                >
                    <div className="mx-auto grid w-full max-w-6xl gap-12 px-6 lg:grid-cols-2 lg:gap-16">
                        {/* Left: Copy */}
                        <div className="flex flex-col justify-center">
                            <motion.h1
                                initial={reduce ? false : { opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    duration: 0.7,
                                    ease: [0.16, 1, 0.3, 1],
                                }}
                                className="text-4xl font-bold tracking-tight text-foreground md:text-5xl lg:text-6xl"
                            >
                                {t('Boka rum i Rikets sal, utan krångel')}
                            </motion.h1>
                            <motion.p
                                initial={reduce ? false : { opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    duration: 0.7,
                                    delay: 0.1,
                                    ease: [0.16, 1, 0.3, 1],
                                }}
                                className="mt-5 max-w-[50ch] text-lg leading-relaxed text-muted-foreground"
                            >
                                {t(
                                    'Se tillgänglighet direkt, boka rum på sekunder. Alla församlingar som delar salen har full översikt.',
                                )}
                            </motion.p>
                            <motion.div
                                initial={reduce ? false : { opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    duration: 0.7,
                                    delay: 0.2,
                                    ease: [0.16, 1, 0.3, 1],
                                }}
                                className="mt-8 flex gap-3"
                            >
                                {auth.user ? (
                                    <Link
                                        href={calendarUrl}
                                        className="inline-flex items-center rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-sm transition-transform active:scale-[0.97]"
                                    >
                                        {t('Öppna kalender')}
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={register()}
                                            className="inline-flex items-center rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-sm transition-transform active:scale-[0.97]"
                                        >
                                            {t('Kom igång')}
                                        </Link>
                                        <Link
                                            href={login()}
                                            className="inline-flex items-center rounded-lg border border-border px-6 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                                        >
                                            {t('Logga in')}
                                        </Link>
                                    </>
                                )}
                            </motion.div>
                        </div>

                        {/* Right: Abstract calendar visualization */}
                        <motion.div
                            initial={
                                reduce ? false : { opacity: 0, scale: 0.95 }
                            }
                            animate={{ opacity: 1, scale: 1 }}
                            transition={{
                                duration: 0.9,
                                delay: 0.3,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="relative hidden lg:flex lg:items-center lg:justify-center"
                        >
                            <div className="relative w-full max-w-md">
                                {/* Floating calendar card */}
                                <div className="overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-2xl shadow-black/5 dark:shadow-black/30">
                                    {/* Calendar header */}
                                    <div className="mb-4 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <div className="h-2 w-2 rounded-full bg-emerald-400" />
                                            <span className="text-sm font-medium text-foreground">
                                                {t('Juni 2026')}
                                            </span>
                                        </div>
                                        <div className="flex gap-1.5">
                                            <div className="h-6 w-6 rounded-md bg-muted" />
                                            <div className="h-6 w-6 rounded-md bg-muted" />
                                        </div>
                                    </div>
                                    {/* Weekday headers */}
                                    <div className="mb-2 grid grid-cols-7 gap-1">
                                        {[
                                            'M',
                                            'T',
                                            'O',
                                            'T',
                                            'F',
                                            'L',
                                            'S',
                                        ].map((d, i) => (
                                            <div
                                                key={i}
                                                className="flex h-6 items-center justify-center text-[10px] font-medium text-muted-foreground"
                                            >
                                                {d}
                                            </div>
                                        ))}
                                    </div>
                                    {/* Calendar grid */}
                                    <div className="grid grid-cols-7 gap-1">
                                        {Array.from({ length: 35 }, (_, i) => {
                                            const day = i - 0; // June starts on Monday
                                            const hasBooking = [
                                                3, 5, 8, 12, 15, 19, 22, 26, 29,
                                            ].includes(i);
                                            const isToday = i === 12;
                                            const bookingColors = [
                                                'bg-emerald-400',
                                                'bg-sky-400',
                                                'bg-amber-400',
                                                'bg-rose-400',
                                            ];

                                            return (
                                                <div
                                                    key={i}
                                                    className={`flex aspect-square flex-col items-center justify-center rounded-lg p-0.5 ${isToday ? 'bg-primary text-primary-foreground' : 'text-foreground'}`}
                                                >
                                                    <span className="text-[10px]">
                                                        {day + 1}
                                                    </span>
                                                    {hasBooking && (
                                                        <div className="mt-0.5 flex gap-0.5">
                                                            <div
                                                                className={`h-1 w-1 rounded-full ${bookingColors[i % 4]}`}
                                                            />
                                                            {i % 3 === 0 && (
                                                                <div
                                                                    className={`h-1 w-1 rounded-full ${bookingColors[(i + 1) % 4]}`}
                                                                />
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                                {/* Decorative background glow */}
                                <div className="pointer-events-none absolute -inset-12 -z-10 rounded-full bg-primary/5 blur-3xl" />
                            </div>
                        </motion.div>
                    </div>
                </motion.section>

                {/* How it Works */}
                <section className="relative py-24 lg:py-32">
                    <div className="mx-auto max-w-6xl px-6">
                        <motion.h2
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                        >
                            {t('Tre steg till en bokad sal')}
                        </motion.h2>
                        <div className="mt-12 grid gap-10 md:grid-cols-3 md:gap-12">
                            <StepCard
                                number="1"
                                title={t('Skapa konto')}
                                description={t(
                                    'Registrera dig med din e-post. Det tar under en minut.',
                                )}
                                delay={0}
                            />
                            <StepCard
                                number="2"
                                title={t('Ställ in Rikets sal')}
                                description={t(
                                    'Lägg till salens adress och rum. Bjud in andra församlingar att dela samma sal.',
                                )}
                                delay={0.08}
                            />
                            <StepCard
                                number="3"
                                title={t('Boka direkt')}
                                description={t(
                                    'Öppna kalendern, se tillgänglighet i realtid och dra för att boka. Klart.',
                                )}
                                delay={0.16}
                            />
                        </div>
                    </div>
                </section>

                {/* Calendar Views Showcase - 3D on scroll */}
                <section
                    ref={calendarSectionRef}
                    className="relative overflow-hidden py-24 lg:py-32"
                >
                    <div className="mx-auto max-w-6xl px-6">
                        <motion.h2
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-center text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                        >
                            {t('En kalender som fungerar som du arbetar')}
                        </motion.h2>
                        <motion.p
                            initial={reduce ? false : { opacity: 0, y: 12 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                delay: 0.1,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="mx-auto mt-4 max-w-[50ch] text-center text-base text-muted-foreground"
                        >
                            {t(
                                'Byt smidigt mellan månad, vecka och dag. Dra och släpp för att boka eller flytta.',
                            )}
                        </motion.p>

                        {/* 3D Calendar Cards */}
                        <div
                            className="mt-16 grid gap-6 md:grid-cols-3"
                            style={{ perspective: '1200px' }}
                        >
                            <motion.div
                                style={
                                    reduce
                                        ? undefined
                                        : {
                                              rotateY: card1RotateY,
                                              x: card1X,
                                              scale: cardsScale,
                                          }
                                }
                            >
                                <CalendarCard variant="month" delay={0} />
                            </motion.div>
                            <motion.div
                                style={
                                    reduce
                                        ? undefined
                                        : {
                                              z: card2Z,
                                              scale: cardsScale,
                                          }
                                }
                            >
                                <CalendarCard variant="week" delay={0.1} />
                            </motion.div>
                            <motion.div
                                style={
                                    reduce
                                        ? undefined
                                        : {
                                              rotateY: card3RotateY,
                                              x: card3X,
                                              scale: cardsScale,
                                          }
                                }
                            >
                                <CalendarCard variant="day" delay={0.2} />
                            </motion.div>
                        </div>
                    </div>
                </section>

                {/* Registration & Invitation Flow */}
                <section className="relative py-24 lg:py-32">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="grid items-center gap-12 lg:grid-cols-5 lg:gap-16">
                            {/* Left: Visual */}
                            <motion.div
                                initial={
                                    reduce ? false : { opacity: 0, x: -20 }
                                }
                                whileInView={{ opacity: 1, x: 0 }}
                                viewport={{ once: true, amount: 0.4 }}
                                transition={{
                                    duration: 0.7,
                                    ease: [0.16, 1, 0.3, 1],
                                }}
                                className="relative lg:col-span-2"
                            >
                                {/* Invitation card mockup */}
                                <div className="overflow-hidden rounded-xl border border-border bg-card p-6 shadow-lg shadow-black/5 dark:shadow-black/20">
                                    <div className="mb-4 flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                            <svg
                                                className="size-5 text-emerald-600 dark:text-emerald-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={2}
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-foreground">
                                                {t('Inbjudan till församling')}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {t('Du har fått en inbjudan')}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="rounded-lg bg-muted/50 p-4">
                                        <p className="text-xs text-muted-foreground">
                                            {t(
                                                'Klicka på länken i din e-post för att gå med direkt.',
                                            )}
                                        </p>
                                        <div className="mt-3 flex gap-2">
                                            <div className="h-8 flex-1 rounded-md bg-primary/10" />
                                            <div className="flex h-8 items-center rounded-md bg-primary px-3">
                                                <span className="text-xs font-medium text-primary-foreground">
                                                    {t('Acceptera')}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Right: Copy */}
                            <motion.div
                                initial={reduce ? false : { opacity: 0, x: 20 }}
                                whileInView={{ opacity: 1, x: 0 }}
                                viewport={{ once: true, amount: 0.4 }}
                                transition={{
                                    duration: 0.7,
                                    delay: 0.1,
                                    ease: [0.16, 1, 0.3, 1],
                                }}
                                className="lg:col-span-3"
                            >
                                <h2 className="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                                    {t('Bjud in hela församlingen')}
                                </h2>
                                <p className="mt-4 max-w-[50ch] text-base leading-relaxed text-muted-foreground">
                                    {t(
                                        'Registrera dig själv först. Sedan kan du bjuda in kollegor via e-post. Varje person får en länk, klickar och är direkt inne i rätt församling med rätt behörigheter.',
                                    )}
                                </p>
                                <ul className="mt-6 space-y-3">
                                    {[
                                        t(
                                            'Rollbaserade behörigheter per församling',
                                        ),
                                        t(
                                            'Inbjudan med ett klick, ingen separat registrering',
                                        ),
                                        t(
                                            'Dela Rikets sal mellan flera församlingar',
                                        ),
                                    ].map((item, i) => (
                                        <li
                                            key={i}
                                            className="flex items-start gap-3 text-sm text-foreground"
                                        >
                                            <svg
                                                className="mt-0.5 size-4 shrink-0 text-emerald-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={2.5}
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M4.5 12.75l6 6 9-13.5"
                                                />
                                            </svg>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </motion.div>
                        </div>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="relative border-t border-border py-24 lg:py-32">
                    <div className="mx-auto max-w-6xl px-6">
                        <motion.h2
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                        >
                            {t('Allt du behöver för att koordinera salen')}
                        </motion.h2>
                        <div className="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                {
                                    icon: (
                                        <svg
                                            className="size-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    ),
                                    title: t('Realtidsuppdateringar'),
                                    desc: t(
                                        'Se ändringar direkt när andra bokar eller flyttar.',
                                    ),
                                },
                                {
                                    icon: (
                                        <svg
                                            className="size-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                            />
                                        </svg>
                                    ),
                                    title: t('Behörigheter'),
                                    desc: t(
                                        'Superadmin, admin och medlem med olika rättigheter.',
                                    ),
                                },
                                {
                                    icon: (
                                        <svg
                                            className="size-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"
                                            />
                                        </svg>
                                    ),
                                    title: t('Återkommande bokningar'),
                                    desc: t(
                                        'Dagliga, veckovisa eller månatliga bokningar med ett klick.',
                                    ),
                                },
                                {
                                    icon: (
                                        <svg
                                            className="size-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                                            />
                                        </svg>
                                    ),
                                    title: t('Flera församlingar'),
                                    desc: t(
                                        'Alla som delar salen ser varandras bokningar.',
                                    ),
                                },
                            ].map((feature, i) => (
                                <motion.div
                                    key={i}
                                    initial={
                                        reduce ? false : { opacity: 0, y: 20 }
                                    }
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true, amount: 0.4 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: reduce ? 0 : i * 0.06,
                                        ease: [0.16, 1, 0.3, 1],
                                    }}
                                    className="flex flex-col gap-3"
                                >
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-muted text-foreground">
                                        {feature.icon}
                                    </div>
                                    <h3 className="text-sm font-semibold text-foreground">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {feature.desc}
                                    </p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="relative border-t border-border py-24 lg:py-32">
                    <div className="mx-auto max-w-6xl px-6 text-center">
                        <motion.h2
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                        >
                            {t('Redo att förenkla era bokningar?')}
                        </motion.h2>
                        <motion.p
                            initial={reduce ? false : { opacity: 0, y: 12 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                delay: 0.1,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="mx-auto mt-4 max-w-[45ch] text-base text-muted-foreground"
                        >
                            {t(
                                'Skapa ett konto kostnadsfritt och bjud in din församling idag.',
                            )}
                        </motion.p>
                        <motion.div
                            initial={reduce ? false : { opacity: 0, y: 12 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                delay: 0.2,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="mt-8"
                        >
                            {auth.user ? (
                                <Link
                                    href={calendarUrl}
                                    className="inline-flex items-center rounded-lg bg-primary px-8 py-3.5 text-sm font-medium text-primary-foreground shadow-sm transition-transform active:scale-[0.97]"
                                >
                                    {t('Öppna kalender')}
                                </Link>
                            ) : (
                                <Link
                                    href={register()}
                                    className="inline-flex items-center rounded-lg bg-primary px-8 py-3.5 text-sm font-medium text-primary-foreground shadow-sm transition-transform active:scale-[0.97]"
                                >
                                    {t('Kom igång')}
                                </Link>
                            )}
                        </motion.div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-8">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-6">
                        <div className="flex items-center gap-2">
                            <AppLogoIcon className="size-4 fill-current text-muted-foreground" />
                            <span className="text-xs text-muted-foreground">
                                Salbokning
                            </span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            &copy; {new Date().getFullYear()}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
