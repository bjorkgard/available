import { useHttp } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';
import { reschedule } from '@/actions/App/Http/Controllers/Congregations/BookingController';
import type { BookingResource } from '@/types/bookings';

export type DragState = {
    /** The booking currently being dragged, or null if idle */
    draggedBooking: BookingResource | null;
    /** Whether a drag operation is in progress */
    isDragging: boolean;
    /** The ID of the booking being dragged, for quick lookup */
    draggedBookingId: string | null;
};

export type DropTarget = {
    /** ISO date string for the target date */
    date: string;
    /** Hour component (0–23) */
    hour: number;
    /** Minute component snapped to 15-min boundary (0, 15, 30, 45) */
    minute: number;
};

type RescheduleData = {
    starts_at: string;
    ends_at: string;
    scope: 'this_only' | 'this_and_future' | '';
};

type UseDragBookingOptions = {
    /** Current congregation slug for API calls */
    congregationSlug: string;
    /** Callback to show RecurrenceEditPrompt and resolve with scope */
    onRecurrencePrompt: (
        bookingId: string,
    ) => Promise<'this_only' | 'this_and_future' | null>;
    /** Callback when reschedule succeeds (optimistic update) */
    onRescheduleSuccess: (
        bookingId: string,
        newStartsAt: string,
        newEndsAt: string,
    ) => void;
    /** Callback when reschedule fails/reverts */
    onRevert: (bookingId: string) => void;
};

type DragHandlers = {
    /** Create props to spread onto a draggable booking element */
    getDragProps: (booking: BookingResource) => {
        draggable: boolean;
        onDragStart: (event: React.DragEvent) => void;
        onDragEnd: (event: React.DragEvent) => void;
    };
    /** Props to spread onto a valid drop target element */
    getDropZoneProps: (target: DropTarget) => {
        onDragOver: (event: React.DragEvent) => void;
        onDragLeave: (event: React.DragEvent) => void;
        onDrop: (event: React.DragEvent) => void;
    };
    /** Current drag state */
    state: DragState;
    /** The current drop target being hovered, for ghost preview rendering */
    activeDropTarget: DropTarget | null;
};

/**
 * Snap a minute value to the nearest 15-minute grid boundary.
 * Examples: 0→0, 7→0, 8→15, 22→15, 23→30, 37→30, 38→45, 52→45, 53→0 (next hour)
 */
export function snapToGrid(minute: number): number {
    return Math.round(minute / 15) * 15;
}

/**
 * Compute the new start and end times after a drop, preserving the original duration.
 */
function computeNewTimes(
    booking: BookingResource,
    target: DropTarget,
): { newStartsAt: string; newEndsAt: string } {
    const originalStart = new Date(booking.starts_at);
    const originalEnd = new Date(booking.ends_at);
    const durationMs = originalEnd.getTime() - originalStart.getTime();

    const snappedMinute = snapToGrid(target.minute);
    const extraHours = Math.floor(snappedMinute / 60);
    const finalMinute = snappedMinute % 60;

    const newStart = new Date(target.date);
    newStart.setHours(target.hour + extraHours, finalMinute, 0, 0);

    const newEnd = new Date(newStart.getTime() + durationMs);

    return {
        newStartsAt: newStart.toISOString(),
        newEndsAt: newEnd.toISOString(),
    };
}

export function useDragBooking({
    congregationSlug,
    onRecurrencePrompt,
    onRescheduleSuccess,
    onRevert,
}: UseDragBookingOptions): DragHandlers {
    const http = useHttp<RescheduleData>({
        starts_at: '',
        ends_at: '',
        scope: '',
    });

    const [state, setState] = useState<DragState>({
        draggedBooking: null,
        isDragging: false,
        draggedBookingId: null,
    });

    const [activeDropTarget, setActiveDropTarget] = useState<DropTarget | null>(
        null,
    );

    const pendingRef = useRef(false);

    const getDragProps = useCallback((booking: BookingResource) => {
        const canDrag = booking.can_edit;

        return {
            draggable: canDrag,
            onDragStart: (event: React.DragEvent) => {
                if (!canDrag) {
                    event.preventDefault();

                    return;
                }

                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData(
                    'application/x-booking-id',
                    booking.id,
                );

                setState({
                    draggedBooking: booking,
                    isDragging: true,
                    draggedBookingId: booking.id,
                });
            },
            onDragEnd: () => {
                setState({
                    draggedBooking: null,
                    isDragging: false,
                    draggedBookingId: null,
                });
                setActiveDropTarget(null);
            },
        };
    }, []);

    const getDropZoneProps = useCallback(
        (target: DropTarget) => ({
            onDragOver: (event: React.DragEvent) => {
                if (!state.isDragging) {
                    return;
                }

                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                setActiveDropTarget(target);
            },
            onDragLeave: () => {
                setActiveDropTarget((current) => {
                    if (
                        current?.date === target.date &&
                        current?.hour === target.hour &&
                        current?.minute === target.minute
                    ) {
                        return null;
                    }

                    return current;
                });
            },
            onDrop: async (event: React.DragEvent) => {
                event.preventDefault();

                const booking = state.draggedBooking;

                if (!booking || pendingRef.current) {
                    return;
                }

                pendingRef.current = true;

                try {
                    const { newStartsAt, newEndsAt } = computeNewTimes(
                        booking,
                        target,
                    );

                    // Check if it's a recurring booking and prompt for scope
                    let scope: 'this_only' | 'this_and_future' | '' = '';

                    if (booking.recurrence_pattern_id) {
                        const result = await onRecurrencePrompt(booking.id);

                        if (result === null) {
                            // User cancelled — revert
                            onRevert(booking.id);

                            return;
                        }

                        scope = result;
                    }

                    // Optimistic update
                    onRescheduleSuccess(booking.id, newStartsAt, newEndsAt);

                    // Set data and call the reschedule endpoint
                    http.setData({
                        starts_at: newStartsAt,
                        ends_at: newEndsAt,
                        scope,
                    });

                    const url = reschedule.url({
                        current_congregation: congregationSlug,
                        booking: booking.id,
                    });

                    await http.patch(url, {
                        onError: () => {
                            // 422 validation/conflict — revert
                            onRevert(booking.id);
                            toast.error(
                                'Time conflicts with an existing booking.',
                            );
                        },
                        onHttpException: () => {
                            onRevert(booking.id);
                            toast.error('Failed to reschedule booking.');
                        },
                        onNetworkError: () => {
                            onRevert(booking.id);
                            toast.error(
                                'Network error. Booking was not moved.',
                            );
                        },
                    });
                } finally {
                    pendingRef.current = false;
                    setState({
                        draggedBooking: null,
                        isDragging: false,
                        draggedBookingId: null,
                    });
                    setActiveDropTarget(null);
                }
            },
        }),
        [
            state.isDragging,
            state.draggedBooking,
            congregationSlug,
            onRecurrencePrompt,
            onRescheduleSuccess,
            onRevert,
            http,
        ],
    );

    return {
        getDragProps,
        getDropZoneProps,
        state,
        activeDropTarget,
    };
}
