import { act, cleanup, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { useBookingChannel } from '@/hooks/use-booking-channel';
import type { BookingEventHandlers } from '@/hooks/use-booking-channel';

type ListenerMap = Record<string, (data: unknown) => void>;

function createMockChannel() {
    const listeners: ListenerMap = {};

    return {
        listen(event: string, callback: (data: unknown) => void) {
            listeners[event] = callback;

            return this;
        },
        stopListening(event: string) {
            delete listeners[event];

            return this;
        },
        emit(event: string, data: unknown) {
            listeners[event]?.(data);
        },
        listeners,
    };
}

function createMockEcho() {
    const channels: Record<string, ReturnType<typeof createMockChannel>> = {};
    const leftChannels: string[] = [];

    return {
        echo: {
            private(channel: string) {
                if (!channels[channel]) {
                    channels[channel] = createMockChannel();
                }

                return channels[channel];
            },
            leave(channel: string) {
                leftChannels.push(channel);
            },
            connector: {},
        },
        channels,
        leftChannels,
    };
}

function createHandlers(): BookingEventHandlers {
    return {
        onCreated: vi.fn(),
        onUpdated: vi.fn(),
        onDeleted: vi.fn(),
    };
}

describe('useBookingChannel', () => {
    let mockEcho: ReturnType<typeof createMockEcho>;

    beforeEach(() => {
        mockEcho = createMockEcho();
        (window as any).Echo = mockEcho.echo;
    });

    afterEach(() => {
        cleanup();
        delete (window as any).Echo;
    });

    /**
     * Validates: Requirements 15.6
     */
    it('subscribes to the private kingdom-hall channel on mount', () => {
        const handlers = createHandlers();
        const privateSpy = vi.spyOn(mockEcho.echo, 'private');

        renderHook(() => useBookingChannel('hall-123', handlers));

        expect(privateSpy).toHaveBeenCalledWith('kingdom-hall.hall-123');
    });

    /**
     * Validates: Requirements 15.3
     */
    it('calls onCreated handler when booking.created event fires', () => {
        const handlers = createHandlers();

        renderHook(() => useBookingChannel('hall-123', handlers));

        const channel = mockEcho.channels['kingdom-hall.hall-123'];
        const event = { booking: { id: 'b1', name: 'Test' } };

        act(() => {
            channel.emit('.booking.created', event);
        });

        expect(handlers.onCreated).toHaveBeenCalledWith(event);
    });

    /**
     * Validates: Requirements 15.4
     */
    it('calls onUpdated handler when booking.updated event fires', () => {
        const handlers = createHandlers();

        renderHook(() => useBookingChannel('hall-123', handlers));

        const channel = mockEcho.channels['kingdom-hall.hall-123'];
        const event = { booking: { id: 'b1', name: 'Updated' } };

        act(() => {
            channel.emit('.booking.updated', event);
        });

        expect(handlers.onUpdated).toHaveBeenCalledWith(event);
    });

    /**
     * Validates: Requirements 15.5
     */
    it('calls onDeleted handler when booking.deleted event fires', () => {
        const handlers = createHandlers();

        renderHook(() => useBookingChannel('hall-123', handlers));

        const channel = mockEcho.channels['kingdom-hall.hall-123'];
        const event = { booking_id: 'b1' };

        act(() => {
            channel.emit('.booking.deleted', event);
        });

        expect(handlers.onDeleted).toHaveBeenCalledWith(event);
    });

    /**
     * Validates: Requirements 15.6
     */
    it('leaves the channel on unmount', () => {
        const handlers = createHandlers();

        const { unmount } = renderHook(() =>
            useBookingChannel('hall-123', handlers),
        );

        unmount();

        expect(mockEcho.leftChannels).toContain('kingdom-hall.hall-123');
    });

    it('does not subscribe when kingdomHallId is undefined', () => {
        const handlers = createHandlers();
        const privateSpy = vi.spyOn(mockEcho.echo, 'private');

        renderHook(() => useBookingChannel(undefined, handlers));

        expect(privateSpy).not.toHaveBeenCalled();
    });

    it('resubscribes when kingdomHallId changes', () => {
        const handlers = createHandlers();

        const { rerender } = renderHook(
            ({ id }) => useBookingChannel(id, handlers),
            { initialProps: { id: 'hall-1' as string | undefined } },
        );

        expect(mockEcho.leftChannels).toHaveLength(0);

        rerender({ id: 'hall-2' });

        expect(mockEcho.leftChannels).toContain('kingdom-hall.hall-1');
        expect(mockEcho.channels['kingdom-hall.hall-2']).toBeDefined();
    });

    /**
     * Validates: Requirements 15.7
     */
    it('returns connected status when no pusher connector is available', () => {
        const handlers = createHandlers();

        const { result } = renderHook(() =>
            useBookingChannel('hall-123', handlers),
        );

        // Without a pusher connector, the hook assumes connected
        expect(result.current).toBe('connected');
    });

    it('returns disconnected when kingdomHallId is undefined', () => {
        const handlers = createHandlers();

        const { result } = renderHook(() =>
            useBookingChannel(undefined, handlers),
        );

        expect(result.current).toBe('disconnected');
    });
});
