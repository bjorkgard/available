import { useCallback, useEffect, useRef, useState } from 'react';

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

/**
 * Returns a reactive view mode that updates when the screen crosses
 * breakpoints: <768px → 'day', 768–1023px → 'week', ≥1024px → 'month'.
 *
 * When the user explicitly picks a view mode via setViewMode, it takes
 * precedence until the next breakpoint change resets it.
 *
 * Starts with 'month' on both server and client to avoid hydration
 * mismatches, then syncs to the actual viewport after mount.
 */
export function useResponsiveViewMode(): {
    viewMode: ViewMode;
    setViewMode: (mode: ViewMode) => void;
} {
    const [viewMode, setViewModeState] = useState<ViewMode>('month');
    const manualRef = useRef(false);

    // Sync to viewport on mount and listen for breakpoint changes
    useEffect(() => {
        const mdQuery = window.matchMedia('(min-width: 768px)');
        const lgQuery = window.matchMedia('(min-width: 1024px)');

        function syncToViewport() {
            manualRef.current = false;
            setViewModeState(getViewModeFromWidth(window.innerWidth));
        }

        // Initial sync after hydration
        syncToViewport();

        function handleChange() {
            // Reset manual override on breakpoint change
            syncToViewport();
        }

        mdQuery.addEventListener('change', handleChange);
        lgQuery.addEventListener('change', handleChange);

        return () => {
            mdQuery.removeEventListener('change', handleChange);
            lgQuery.removeEventListener('change', handleChange);
        };
    }, []);

    const setViewMode = useCallback((mode: ViewMode) => {
        manualRef.current = true;
        setViewModeState(mode);
    }, []);

    return { viewMode, setViewMode };
}
