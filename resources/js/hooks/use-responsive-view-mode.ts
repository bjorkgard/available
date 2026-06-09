import { useCallback, useRef, useSyncExternalStore } from 'react';

import type { ViewMode } from '@/components/calendar-header';

function getViewModeFromWidth(width: number): ViewMode {
    if (width < 768) {
        return 'day';
    }

    if (width < 1024) {
        return 'week';
    }

    return 'month';
}

function getServerSnapshot(): ViewMode {
    return 'month';
}

/**
 * Returns a reactive view mode that updates when the screen crosses
 * breakpoints: <768px → 'day', 768–1023px → 'week', ≥1024px → 'month'.
 *
 * When the user explicitly picks a view mode via setViewMode, it takes
 * precedence until the next breakpoint change resets it.
 */
export function useResponsiveViewMode(): {
    viewMode: ViewMode;
    setViewMode: (mode: ViewMode) => void;
} {
    const manualModeRef = useRef<ViewMode | null>(null);
    const notifyRef = useRef<(() => void) | null>(null);

    const subscribe = useCallback((callback: () => void) => {
        notifyRef.current = callback;

        const mdQuery = window.matchMedia('(min-width: 768px)');
        const lgQuery = window.matchMedia('(min-width: 1024px)');

        function handleChange() {
            // Reset manual override on breakpoint change
            manualModeRef.current = null;
            callback();
        }

        mdQuery.addEventListener('change', handleChange);
        lgQuery.addEventListener('change', handleChange);

        return () => {
            notifyRef.current = null;
            mdQuery.removeEventListener('change', handleChange);
            lgQuery.removeEventListener('change', handleChange);
        };
    }, []);

    const getSnapshot = useCallback((): ViewMode => {
        if (manualModeRef.current !== null) {
            return manualModeRef.current;
        }

        return getViewModeFromWidth(window.innerWidth);
    }, []);

    const viewMode = useSyncExternalStore(
        subscribe,
        getSnapshot,
        getServerSnapshot,
    );

    const setViewMode = useCallback((mode: ViewMode) => {
        manualModeRef.current = mode;
        notifyRef.current?.();
    }, []);

    return { viewMode, setViewMode };
}
