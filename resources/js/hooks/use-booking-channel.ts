import { useCallback, useEffect, useRef, useSyncExternalStore } from 'react';

import type { BookingResource } from '@/types/bookings';

export type BookingCreatedEvent = {
    bookings: BookingResource[];
};

export type BookingUpdatedEvent = {
    bookings: BookingResource[];
};

export type BookingDeletedEvent = {
    booking_ids: string[];
    user_name: string;
};

export type BookingEventHandlers = {
    onCreated: (event: BookingCreatedEvent) => void;
    onUpdated: (event: BookingUpdatedEvent) => void;
    onDeleted: (event: BookingDeletedEvent) => void;
};

export type ConnectionStatus = 'connected' | 'disconnected' | 'connecting';

const MAX_BACKOFF_MS = 30_000;
const BASE_BACKOFF_MS = 1_000;

function getBackoffDelay(attempt: number): number {
    const delay = BASE_BACKOFF_MS * Math.pow(2, attempt);

    return Math.min(delay, MAX_BACKOFF_MS);
}

type ConnectionStore = {
    subscribe: (listener: () => void) => () => void;
    getSnapshot: () => ConnectionStatus;
    set: (next: ConnectionStatus) => void;
};

function createConnectionStore(): ConnectionStore {
    let status: ConnectionStatus = 'disconnected';
    const listeners = new Set<() => void>();

    return {
        getSnapshot: () => status,
        subscribe: (listener: () => void) => {
            listeners.add(listener);

            return () => {
                listeners.delete(listener);
            };
        },
        set: (next: ConnectionStatus) => {
            if (next !== status) {
                status = next;
                listeners.forEach((l) => l());
            }
        },
    };
}

// Module-level store — shared across all instances.
// This is appropriate because there's only one WebSocket connection per page.
const connectionStore = createConnectionStore();

function subscribeToStore(listener: () => void) {
    return connectionStore.subscribe(listener);
}

function getStoreSnapshot() {
    return connectionStore.getSnapshot();
}

export function useBookingChannel(
    kingdomHallId: string | undefined,
    handlers: BookingEventHandlers,
): ConnectionStatus {
    const handlersRef = useRef(handlers);
    const reconnectAttemptRef = useRef(0);
    const reconnectTimerRef = useRef<ReturnType<typeof setTimeout> | null>(
        null,
    );

    useEffect(() => {
        handlersRef.current = handlers;
    });

    const connectionStatus = useSyncExternalStore(
        subscribeToStore,
        getStoreSnapshot,
        getStoreSnapshot,
    );

    const clearReconnectTimer = useCallback(() => {
        if (reconnectTimerRef.current) {
            clearTimeout(reconnectTimerRef.current);
            reconnectTimerRef.current = null;
        }
    }, []);

    useEffect(() => {
        if (!kingdomHallId || typeof window === 'undefined' || !window.Echo) {
            connectionStore.set('disconnected');

            return;
        }

        const channelName = `kingdom-hall.${kingdomHallId}`;

        connectionStore.set('connecting');

        const channel = window.Echo.private(channelName);

        channel
            .listen('.booking.created', (event: BookingCreatedEvent) => {
                handlersRef.current.onCreated(event);
            })
            .listen('.booking.updated', (event: BookingUpdatedEvent) => {
                handlersRef.current.onUpdated(event);
            })
            .listen('.booking.deleted', (event: BookingDeletedEvent) => {
                handlersRef.current.onDeleted(event);
            });

        // Track connection state via Echo connector events
        const connector = window.Echo.connector;

        function handleConnected() {
            connectionStore.set('connected');
            reconnectAttemptRef.current = 0;
        }

        function handleDisconnected() {
            connectionStore.set('disconnected');
            scheduleReconnect();
        }

        function handleConnecting() {
            connectionStore.set('connecting');
        }

        function scheduleReconnect() {
            clearReconnectTimer();

            const delay = getBackoffDelay(reconnectAttemptRef.current);

            reconnectTimerRef.current = setTimeout(() => {
                reconnectAttemptRef.current += 1;

                if (connector?.pusher) {
                    connector.pusher.connect();
                }
            }, delay);
        }

        if (connector?.pusher) {
            const pusher = connector.pusher;

            pusher.connection.bind('connected', handleConnected);
            pusher.connection.bind('disconnected', handleDisconnected);
            pusher.connection.bind('connecting', handleConnecting);

            // If already connected, update status immediately
            if (pusher.connection.state === 'connected') {
                connectionStore.set('connected');
            }
        } else {
            // If no pusher-like connector, assume connected once subscribed
            connectionStore.set('connected');
        }

        return () => {
            clearReconnectTimer();

            if (connector?.pusher) {
                connector.pusher.connection.unbind(
                    'connected',
                    handleConnected,
                );
                connector.pusher.connection.unbind(
                    'disconnected',
                    handleDisconnected,
                );
                connector.pusher.connection.unbind(
                    'connecting',
                    handleConnecting,
                );
            }

            window.Echo.leave(channelName);
            connectionStore.set('disconnected');
        };
    }, [kingdomHallId, clearReconnectTimer]);

    return connectionStatus;
}
