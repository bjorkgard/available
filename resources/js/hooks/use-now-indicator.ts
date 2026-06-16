import { useEffect, useState } from 'react';

/**
 * Returns the current time as a percentage of the day (0–100),
 * updating every minute. Useful for rendering a "now" line on time grids.
 */
export function useNowIndicator(): number {
    const [nowPercent, setNowPercent] = useState<number>(() => {
        const now = new Date();

        return ((now.getHours() * 60 + now.getMinutes()) / (24 * 60)) * 100;
    });

    useEffect(() => {
        const update = () => {
            const now = new Date();
            setNowPercent(
                ((now.getHours() * 60 + now.getMinutes()) / (24 * 60)) * 100,
            );
        };

        const interval = setInterval(update, 60_000);

        return () => clearInterval(interval);
    }, []);

    return nowPercent;
}
