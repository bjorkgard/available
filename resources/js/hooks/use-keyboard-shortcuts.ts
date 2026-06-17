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
    if (event.ctrlKey || event.metaKey || event.altKey) {
        return true;
    }

    return isTextInputElement(document.activeElement);
}

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
