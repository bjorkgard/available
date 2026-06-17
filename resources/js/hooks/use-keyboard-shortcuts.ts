import { useEffect, useRef } from 'react';

const TEXT_INPUT_TYPES = new Set([
    'text',
    'search',
    'url',
    'tel',
    'email',
    'password',
    'number',
    'date',
    'datetime-local',
    'month',
    'week',
    'time',
]);

export function isTextInputElement(element: Element | null): boolean {
    if (!element) {
        return false;
    }

    const tagName = element.tagName.toLowerCase();

    if (tagName === 'textarea' || tagName === 'select') {
        return true;
    }

    if (tagName === 'input') {
        const type = (element as HTMLInputElement).type.toLowerCase();

        return TEXT_INPUT_TYPES.has(type);
    }

    if (element.getAttribute('contenteditable') === 'true') {
        return true;
    }

    return false;
}

export function shouldIgnoreEvent(event: KeyboardEvent): boolean {
    if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
        return true;
    }

    return isTextInputElement(document.activeElement);
}

/**
 * Keys that inherently require Shift to type (e.g. ? is Shift+/).
 * These are exempt from the Shift modifier guard.
 */
const SHIFT_EXEMPT_KEYS = new Set(['?']);

type ShortcutMap = Record<string, () => void>;

export function useKeyboardShortcuts(
    shortcuts: ShortcutMap,
    ctrlShortcuts?: ShortcutMap,
): void {
    const shortcutsRef = useRef<ShortcutMap>(shortcuts);
    const ctrlShortcutsRef = useRef<ShortcutMap>(ctrlShortcuts ?? {});

    useEffect(() => {
        shortcutsRef.current = shortcuts;
        ctrlShortcutsRef.current = ctrlShortcuts ?? {};
    });

    useEffect(() => {
        function handleKeyDown(event: KeyboardEvent) {
            const key = event.key.toLowerCase();

            // Handle Ctrl/Cmd shortcuts first
            if (
                (event.ctrlKey || event.metaKey) &&
                !event.altKey &&
                !event.shiftKey
            ) {
                const ctrlHandler = ctrlShortcutsRef.current[key];

                if (ctrlHandler) {
                    event.preventDefault();
                    ctrlHandler();

                    return;
                }

                return;
            }

            // Allow Shift-exempt keys (like ?) to bypass the Shift guard
            if (
                event.shiftKey &&
                SHIFT_EXEMPT_KEYS.has(event.key) &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey &&
                !isTextInputElement(document.activeElement)
            ) {
                const handler =
                    shortcutsRef.current[event.key.toLowerCase()];

                if (handler) {
                    event.preventDefault();
                    handler();
                }

                return;
            }

            if (shouldIgnoreEvent(event)) {
                return;
            }

            const handler = shortcutsRef.current[key];

            if (handler) {
                event.preventDefault();
                handler();
            }
        }

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);
}
