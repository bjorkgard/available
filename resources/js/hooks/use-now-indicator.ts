import { useEffect, useState } from 'react';

interface NowIndicator {
    /** Current time as a percentage of the day (0–100). */
    nowPercent: number;
    /** Today's date as "YYYY-MM-DD" (updates at midnight). */
    todayDate: string;
}

function getTodayDate(): string {
    const now = new Date();
    const y = now.getFullYear();
    const m = (now.getMonth() + 1).toString().padStart(2, '0');
    const d = now.getDate().toString().padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function getNowPercent(): number {
    const now = new Date();

    return ((now.getHours() * 60 + now.getMinutes()) / (24 * 60)) * 100;
}

/**
 * Returns the current time as a percentage of the day (0–100) and today's
 * date string, both updating every minute. Handles midnight rollover so
 * the "today" column stays correct if the page is left open overnight.
 */
export function useNowIndicator(): NowIndicator {
    const [nowPercent, setNowPercent] = useState<number>(getNowPercent);
    const [todayDate, setTodayDate] = useState<string>(getTodayDate);

    useEffect(() => {
        const update = () => {
            setNowPercent(getNowPercent());
            setTodayDate(getTodayDate());
        };

        const interval = setInterval(update, 60_000);

        return () => clearInterval(interval);
    }, []);

    return { nowPercent, todayDate };
}
