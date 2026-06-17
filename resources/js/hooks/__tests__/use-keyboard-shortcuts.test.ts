import { cleanup, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { useKeyboardShortcuts } from '@/hooks/use-keyboard-shortcuts';

/**
 * Unit tests for the useKeyboardShortcuts hook.
 * Tests integration behavior including event dispatch, cleanup, and suppression.
 */

afterEach(() => {
    cleanup();

    // Reset activeElement to body
    Object.defineProperty(document, 'activeElement', {
        value: document.body,
        writable: true,
        configurable: true,
    });

    // Remove any appended elements
    while (document.body.firstChild) {
        document.body.removeChild(document.body.firstChild);
    }
});

function dispatchKeydown(
    key: string,
    options: {
        ctrlKey?: boolean;
        metaKey?: boolean;
        altKey?: boolean;
        shiftKey?: boolean;
    } = {},
): KeyboardEvent {
    const event = new KeyboardEvent('keydown', {
        key,
        ctrlKey: options.ctrlKey ?? false,
        metaKey: options.metaKey ?? false,
        altKey: options.altKey ?? false,
        shiftKey: options.shiftKey ?? false,
        bubbles: true,
        cancelable: true,
    });

    document.dispatchEvent(event);

    return event;
}

describe('useKeyboardShortcuts', () => {
    /**
     * Validates: Requirements 1.1
     */
    it('ArrowLeft calls the registered handler', () => {
        const onPreviousMonth = vi.fn();
        const onNextMonth = vi.fn();

        renderHook(() =>
            useKeyboardShortcuts({
                arrowleft: onPreviousMonth,
                arrowright: onNextMonth,
            }),
        );

        dispatchKeydown('ArrowLeft');

        expect(onPreviousMonth).toHaveBeenCalledTimes(1);
        expect(onNextMonth).not.toHaveBeenCalled();
    });

    /**
     * Validates: Requirements 1.2
     */
    it('ArrowRight calls the registered handler', () => {
        const onPreviousMonth = vi.fn();
        const onNextMonth = vi.fn();

        renderHook(() =>
            useKeyboardShortcuts({
                arrowleft: onPreviousMonth,
                arrowright: onNextMonth,
            }),
        );

        dispatchKeydown('ArrowRight');

        expect(onNextMonth).toHaveBeenCalledTimes(1);
        expect(onPreviousMonth).not.toHaveBeenCalled();
    });

    /**
     * Validates: Requirements 1.3
     */
    it('removes the event listener on unmount', () => {
        const handler = vi.fn();

        const { unmount } = renderHook(() =>
            useKeyboardShortcuts({ d: handler }),
        );

        dispatchKeydown('d');
        expect(handler).toHaveBeenCalledTimes(1);

        unmount();

        dispatchKeydown('d');
        expect(handler).toHaveBeenCalledTimes(1);
    });

    /**
     * Validates: Requirements 3.1
     */
    it('does not fire shortcut when an input[type="text"] is focused', () => {
        const handler = vi.fn();

        renderHook(() => useKeyboardShortcuts({ d: handler }));

        const input = document.createElement('input');
        input.type = 'text';
        document.body.appendChild(input);
        Object.defineProperty(document, 'activeElement', {
            value: input,
            writable: true,
            configurable: true,
        });

        dispatchKeydown('d');

        expect(handler).not.toHaveBeenCalled();
    });

    /**
     * Validates: Requirements 4.1
     */
    it('does not fire shortcut when Ctrl modifier is pressed', () => {
        const handler = vi.fn();

        renderHook(() => useKeyboardShortcuts({ d: handler }));

        dispatchKeydown('d', { ctrlKey: true });

        expect(handler).not.toHaveBeenCalled();
    });

    /**
     * Validates: Requirements 2.1, 2.2
     */
    it('"l" key toggles appearance from light to dark', () => {
        const updateAppearance = vi.fn();
        const appearance = 'light';

        renderHook(() =>
            useKeyboardShortcuts({
                l: () =>
                    updateAppearance(appearance === 'light' ? 'dark' : 'light'),
            }),
        );

        dispatchKeydown('l');

        expect(updateAppearance).toHaveBeenCalledWith('dark');
    });

    /**
     * Validates: Requirements 2.1, 2.3
     */
    it('"l" key toggles appearance from dark to light', () => {
        const updateAppearance = vi.fn();
        const appearance: string = 'dark';

        renderHook(() =>
            useKeyboardShortcuts({
                l: () =>
                    updateAppearance(appearance === 'light' ? 'dark' : 'light'),
            }),
        );

        dispatchKeydown('l');

        expect(updateAppearance).toHaveBeenCalledWith('light');
    });

    /**
     * Validates: Requirements 2.1, 2.3
     */
    it('"l" key toggles appearance from system to light', () => {
        const updateAppearance = vi.fn();
        const appearance: string = 'system';

        renderHook(() =>
            useKeyboardShortcuts({
                l: () =>
                    updateAppearance(appearance === 'light' ? 'dark' : 'light'),
            }),
        );

        dispatchKeydown('l');

        expect(updateAppearance).toHaveBeenCalledWith('light');
    });
});
