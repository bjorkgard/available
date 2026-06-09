import { cn } from '@/lib/utils';

interface WeekDay {
    day: number;
    month: number;
    year: number;
    label: string;
    isToday: boolean;
}

interface WeekGridProps {
    days: WeekDay[];
}

const HOURS = Array.from({ length: 12 }, (_, i) => i * 2); // 0, 2, 4, ..., 22

function formatHour(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

export function WeekGrid({ days }: WeekGridProps) {
    return (
        <div className="grid h-full grid-cols-[auto_repeat(7,1fr)] grid-rows-[auto_1fr]">
            {/* Header row: empty corner + day names */}
            <div className="border-b p-2" />
            {days.map((day) => (
                <div
                    key={`${day.year}-${day.month}-${day.day}`}
                    className={cn(
                        'flex flex-col items-center justify-center border-b p-2 text-sm font-medium',
                        day.isToday && 'font-semibold text-blue-500',
                    )}
                >
                    <span className="text-muted-foreground">{day.label}</span>
                    <span
                        className={cn(
                            'mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm',
                            day.isToday &&
                                'border-2 border-blue-500 font-semibold',
                        )}
                    >
                        {day.day}
                    </span>
                </div>
            ))}

            {/* Time grid: hour labels + day columns */}
            <div className="col-span-full grid grid-cols-[auto_repeat(7,1fr)] overflow-y-auto">
                {HOURS.map((hour) => (
                    <div
                        key={hour}
                        className="contents"
                    >
                        {/* Hour label */}
                        <div className="flex w-14 items-start justify-end border-t border-dashed pr-2 pt-1 text-xs text-muted-foreground">
                            {formatHour(hour)}
                        </div>
                        {/* Day cells for this hour */}
                        {days.map((day) => (
                            <div
                                key={`${day.year}-${day.month}-${day.day}-${hour}`}
                                className={cn(
                                    'min-h-16 border-l border-t border-dashed',
                                    day.isToday && 'bg-blue-50/30 dark:bg-blue-950/10',
                                )}
                            >
                                {/* Bookings will render here */}
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

export type { WeekDay };
