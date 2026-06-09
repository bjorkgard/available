import { cn } from '@/lib/utils';
import type { Room } from '@/types';
interface DayGridProps {
    date: { day: number; month: number; year: number };
    rooms: Room[];
    isToday: boolean;
}

const HOURS = Array.from({ length: 12 }, (_, i) => i * 2); // 0, 2, 4, ..., 22

function formatHour(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

export function DayGrid({ date, rooms, isToday }: DayGridProps) {
    const roomCount = Math.max(rooms.length, 1);
    const gridColsStyle = `auto repeat(${roomCount}, 1fr)`;

    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const formattedDate = dateFormatter.format(
        new Date(date.year, date.month, date.day),
    );

    return (
        <div className="flex h-full flex-col">
            {/* Day header with date and room names */}
            <div
                className="grid border-b"
                style={{ gridTemplateColumns: gridColsStyle }}
            >
                <div className="flex w-14 items-center justify-center p-2">
                    <span
                        className={cn(
                            'text-sm font-medium',
                            isToday && 'font-semibold text-blue-500',
                        )}
                    >
                        {formattedDate}
                    </span>
                </div>
                {rooms.map((room) => (
                    <div
                        key={room.id}
                        className="flex items-center justify-center border-l p-2 text-sm font-medium"
                    >
                        {room.name}
                    </div>
                ))}
                {rooms.length === 0 && (
                    <div className="flex items-center justify-center p-2 text-sm text-muted-foreground">
                        No rooms configured
                    </div>
                )}
            </div>

            {/* Time grid: hour labels + room columns */}
            <div className="min-h-0 flex-1">
                <div
                    className="grid h-full"
                    style={{
                        gridTemplateColumns: gridColsStyle,
                        gridTemplateRows: `repeat(${HOURS.length}, 1fr)`,
                    }}
                >
                    {HOURS.map((hour) => (
                        <div key={hour} className="contents">
                            {/* Hour label */}
                            <div className="flex w-14 items-start justify-end border-t border-dashed pr-2 pt-1 text-xs text-muted-foreground">
                                {formatHour(hour)}
                            </div>
                            {/* Room cells for this hour */}
                            {rooms.map((room) => (
                                <div
                                    key={`${room.id}-${hour}`}
                                    className="border-l border-t border-dashed"
                                >
                                    {/* Bookings will render here */}
                                </div>
                            ))}
                            {rooms.length === 0 && (
                                <div className="border-l border-t border-dashed" />
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
