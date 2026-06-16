import { Head, Link, usePage } from '@inertiajs/react';
import { format, getDaysInMonth, getDay, startOfMonth } from 'date-fns';
import { sv } from 'date-fns/locale';
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
import Aurora from '@/components/aurora';
import { LanguageSelector } from '@/components/language-selector';
import StarBorder from '@/components/StarBorder';
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
    icon,
    title,
    description,
    delay = 0,
}: {
    icon: React.ReactNode;
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
            <div className="flex size-10 items-center justify-center rounded-xl bg-foreground/5 text-foreground">
                {icon}
            </div>
            <h3 className="text-base font-semibold text-foreground">{title}</h3>
            <p
                className="text-sm leading-relaxed text-muted-foreground"
                style={{ textWrap: 'pretty' }}
            >
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
                <nav className="fixed top-5 right-5 left-5 z-50 mx-auto max-w-3xl">
                    <div className="flex h-[52px] items-center justify-between rounded-2xl border border-white/[0.08] bg-white/[0.04] px-2 pl-5 ring-1 ring-white/[0.04] backdrop-blur-2xl ring-inset dark:bg-white/[0.03]">
                        <Link
                            href="/"
                            className="flex items-center gap-2.5 transition-opacity hover:opacity-80"
                        >
                            <div className="flex size-7 items-center justify-center rounded-md bg-primary">
                                <AppLogoIcon className="size-4 fill-current text-primary-foreground" />
                            </div>
                            <span className="text-sm font-semibold tracking-tight text-foreground">
                                Salbokning
                            </span>
                        </Link>
                        <div className="flex items-center gap-1.5">
                            <LanguageSelector />
                            {auth.user ? (
                                <Link
                                    href={calendarUrl}
                                    className="rounded-xl bg-white px-4 py-1.5 text-sm font-semibold text-neutral-900 shadow-sm shadow-black/10 transition-all duration-150 hover:shadow-md hover:shadow-black/15 active:scale-[0.96]"
                                >
                                    {t('Kalender')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="hidden rounded-xl px-3.5 py-1.5 text-sm font-medium whitespace-nowrap text-muted-foreground transition-colors duration-150 hover:text-foreground sm:inline-flex dark:text-white/70 dark:hover:text-white"
                                    >
                                        {t('Logga in')}
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="rounded-xl bg-white px-4 py-1.5 text-sm font-semibold text-neutral-900 shadow-sm shadow-black/10 transition-all duration-150 hover:shadow-md hover:shadow-black/15 active:scale-[0.96]"
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
                    className="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden px-6 pt-24 pb-16"
                >
                    {/* Aurora background */}
                    <div className="pointer-events-none absolute inset-0 z-0 opacity-40 dark:opacity-50">
                        <Aurora
                            colorStops={['#3b82f6', '#10b981', '#6366f1']}
                            amplitude={1.2}
                            blend={0.7}
                            speed={0.5}
                        />
                    </div>

                    <div className="relative z-10 flex max-w-3xl flex-col items-center text-center">
                        <motion.h1
                            initial={reduce ? false : { opacity: 0, y: 24 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.8,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-4xl font-bold tracking-tight text-foreground sm:text-5xl md:text-6xl"
                            style={{ textWrap: 'balance' }}
                        >
                            {t('Boka rum i Rikets sal, utan krångel')}
                        </motion.h1>
                        <motion.p
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.8,
                                delay: 0.1,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="mt-6 max-w-[52ch] text-lg leading-relaxed text-muted-foreground"
                            style={{ textWrap: 'pretty' }}
                        >
                            {t(
                                'Se tillgänglighet direkt, boka rum på sekunder. Alla församlingar som delar salen har full översikt.',
                            )}
                        </motion.p>
                        <motion.div
                            initial={reduce ? false : { opacity: 0, y: 16 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.8,
                                delay: 0.2,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="mt-10 flex gap-3"
                        >
                            {auth.user ? (
                                <Link
                                    href={calendarUrl}
                                    className="inline-flex items-center rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-all duration-150 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.97]"
                                >
                                    {t('Öppna kalender')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-all duration-150 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.97]"
                                    >
                                        {t('Kom igång')}
                                    </Link>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center rounded-xl border border-border/60 bg-background/50 px-7 py-3 text-sm font-medium text-foreground backdrop-blur-sm transition-colors duration-150 hover:bg-muted/80"
                                    >
                                        {t('Logga in')}
                                    </Link>
                                </>
                            )}
                        </motion.div>
                    </div>

                    {/* Floating calendar showcase below hero copy */}
                    <motion.div
                        initial={reduce ? false : { opacity: 0, y: 40 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: 1,
                            delay: 0.4,
                            ease: [0.16, 1, 0.3, 1],
                        }}
                        className="relative z-10 mt-16 w-full max-w-sm"
                    >
                        <StarBorder
                            as="div"
                            color="white"
                            speed="8s"
                            thickness={1}
                            className="w-full"
                        >
                            {(() => {
                                const now = new Date();
                                const today = now.getDate();
                                const monthStart = startOfMonth(now);
                                const startOffset =
                                    (getDay(monthStart) + 6) % 7;
                                const daysInMonth = getDaysInMonth(now);
                                const totalCells =
                                    Math.ceil((startOffset + daysInMonth) / 7) *
                                    7;
                                const monthLabel = format(now, 'MMMM yyyy', {
                                    locale: sv,
                                });
                                const bookingDays = [
                                    3, 5, 8, 12, 15, 19, 22, 26, 29,
                                ].filter((d) => d <= daysInMonth);
                                const bookingColors = [
                                    'bg-emerald-400',
                                    'bg-sky-400',
                                    'bg-amber-400',
                                    'bg-rose-400',
                                ];

                                return (
                                    <>
                                        <div className="mb-4 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <div className="h-2 w-2 rounded-full bg-emerald-400" />
                                                <span className="text-sm font-medium text-foreground capitalize">
                                                    {monthLabel}
                                                </span>
                                            </div>
                                            <div className="flex gap-1.5">
                                                <div className="h-6 w-6 rounded-md bg-muted" />
                                                <div className="h-6 w-6 rounded-md bg-muted" />
                                            </div>
                                        </div>
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
                                        <div className="grid grid-cols-7 gap-1">
                                            {Array.from(
                                                { length: totalCells },
                                                (_, i) => {
                                                    const dayNum =
                                                        i - startOffset + 1;
                                                    const isValidDay =
                                                        dayNum >= 1 &&
                                                        dayNum <= daysInMonth;
                                                    const isToday =
                                                        isValidDay &&
                                                        dayNum === today;
                                                    const hasBooking =
                                                        isValidDay &&
                                                        bookingDays.includes(
                                                            dayNum,
                                                        );

                                                    return (
                                                        <div
                                                            key={i}
                                                            className={`flex aspect-square flex-col items-center justify-center rounded-lg p-0.5 ${
                                                                isToday
                                                                    ? 'bg-primary text-primary-foreground'
                                                                    : isValidDay
                                                                      ? 'text-foreground'
                                                                      : 'text-transparent'
                                                            }`}
                                                        >
                                                            <span className="text-[10px]">
                                                                {isValidDay
                                                                    ? dayNum
                                                                    : ''}
                                                            </span>
                                                            {hasBooking && (
                                                                <div className="mt-0.5 flex gap-0.5">
                                                                    <div
                                                                        className={`h-1 w-1 rounded-full ${bookingColors[dayNum % 4]}`}
                                                                    />
                                                                    {dayNum %
                                                                        3 ===
                                                                        0 && (
                                                                        <div
                                                                            className={`h-1 w-1 rounded-full ${bookingColors[(dayNum + 1) % 4]}`}
                                                                        />
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                },
                                            )}
                                        </div>
                                    </>
                                );
                            })()}
                        </StarBorder>
                    </motion.div>
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
                            style={{ textWrap: 'balance' }}
                        >
                            {t('Tre steg till en bokad sal')}
                        </motion.h2>
                        <div className="mt-14 grid gap-10 md:grid-cols-3 md:gap-12">
                            <StepCard
                                icon={
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
                                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                        />
                                    </svg>
                                }
                                title={t('Skapa konto')}
                                description={t(
                                    'Registrera dig med din e-post. Det tar under en minut.',
                                )}
                                delay={0}
                            />
                            <StepCard
                                icon={
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
                                            d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"
                                        />
                                    </svg>
                                }
                                title={t('Ställ in Rikets sal')}
                                description={t(
                                    'Lägg till salens adress och rum. Bjud in andra församlingar att dela samma sal.',
                                )}
                                delay={0.08}
                            />
                            <StepCard
                                icon={
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
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                                        />
                                    </svg>
                                }
                                title={t('Boka direkt')}
                                description={t(
                                    'Öppna kalendern, se tillgänglighet i realtid och dra för att boka.',
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

                {/* Features */}
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
                            className="max-w-lg text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                            style={{ textWrap: 'balance' }}
                        >
                            {t('Allt du behöver för att koordinera salen')}
                        </motion.h2>
                        <div className="mt-14 grid gap-x-12 gap-y-10 sm:grid-cols-2">
                            {[
                                {
                                    title: t('Realtidsuppdateringar'),
                                    desc: t(
                                        'Ändringar syns omedelbart för alla. Ingen behöver ladda om sidan eller fråga om det redan är bokat.',
                                    ),
                                },
                                {
                                    title: t('Rollbaserade behörigheter'),
                                    desc: t(
                                        'Superadmin styr salen, admin hanterar sin församling, medlemmar bokar sina egna tider.',
                                    ),
                                },
                                {
                                    title: t('Återkommande bokningar'),
                                    desc: t(
                                        'Ställ in veckovisa möten eller månatliga samlingar en gång. De dyker upp automatiskt framåt i kalendern.',
                                    ),
                                },
                                {
                                    title: t('Dela mellan församlingar'),
                                    desc: t(
                                        'Alla som delar Rikets sal ser varandras bokningar. Färgkodade per församling för snabb överblick.',
                                    ),
                                },
                            ].map((feature, i) => (
                                <motion.div
                                    key={i}
                                    initial={
                                        reduce ? false : { opacity: 0, y: 16 }
                                    }
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true, amount: 0.4 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: reduce ? 0 : i * 0.05,
                                        ease: [0.16, 1, 0.3, 1],
                                    }}
                                    className="flex flex-col gap-2"
                                >
                                    <h3 className="text-sm font-semibold text-foreground">
                                        {feature.title}
                                    </h3>
                                    <p
                                        className="text-sm leading-relaxed text-muted-foreground"
                                        style={{ textWrap: 'pretty' }}
                                    >
                                        {feature.desc}
                                    </p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="relative overflow-hidden py-24 lg:py-32">
                    <div className="pointer-events-none absolute inset-0 z-0 opacity-20 dark:opacity-30">
                        <Aurora
                            colorStops={['#6366f1', '#10b981', '#3b82f6']}
                            amplitude={0.8}
                            blend={0.6}
                            speed={0.3}
                        />
                    </div>
                    <div className="relative z-10 mx-auto max-w-6xl px-6 text-center">
                        <motion.h2
                            initial={reduce ? false : { opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{
                                duration: 0.6,
                                ease: [0.16, 1, 0.3, 1],
                            }}
                            className="text-3xl font-bold tracking-tight text-foreground md:text-4xl"
                            style={{ textWrap: 'balance' }}
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
                            style={{ textWrap: 'pretty' }}
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
                            className="mt-10"
                        >
                            {auth.user ? (
                                <Link
                                    href={calendarUrl}
                                    className="inline-flex items-center rounded-xl bg-primary px-8 py-3.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-all duration-150 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.97]"
                                >
                                    {t('Öppna kalender')}
                                </Link>
                            ) : (
                                <Link
                                    href={register()}
                                    className="inline-flex items-center rounded-xl bg-primary px-8 py-3.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-all duration-150 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.97]"
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
