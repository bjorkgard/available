import { useCallback, useRef } from 'react';

type LongPressOptions = {
    /** Duration in milliseconds before triggering the long press. Default: 500 */
    delay?: number;
    /** Called when the long press is triggered. */
    onLongPress: (event: React.TouchEvent | React.PointerEvent) => void;
};

type LongPressHandlers = {
    onTouchStart: (event: React.TouchEvent) => void;
    onTouchEnd: () => void;
    onTouchMove: () => void;
    onContextMenu: (event: React.SyntheticEvent) => void;
};

/**
 * Hook that provides touch handlers for long-press detection on mobile.
 * Prevents the native context menu on touch devices and fires the callback
 * after the specified delay.
 */
export function useLongPress({
    delay = 500,
    onLongPress,
}: LongPressOptions): LongPressHandlers {
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isLongPressRef = useRef(false);

    const clear = useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const onTouchStart = useCallback(
        (event: React.TouchEvent) => {
            isLongPressRef.current = false;

            timerRef.current = setTimeout(() => {
                isLongPressRef.current = true;
                onLongPress(event);
            }, delay);
        },
        [delay, onLongPress],
    );

    const onTouchEnd = useCallback(() => {
        clear();
    }, [clear]);

    const onTouchMove = useCallback(() => {
        clear();
    }, [clear]);

    const onContextMenu = useCallback((event: React.SyntheticEvent) => {
        if (isLongPressRef.current) {
            event.preventDefault();
        }
    }, []);

    return {
        onTouchStart,
        onTouchEnd,
        onTouchMove,
        onContextMenu,
    };
}
