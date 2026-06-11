import * as fc from 'fast-check';
import { describe, expect, it } from 'vitest';
import { shouldIgnoreEvent } from '@/hooks/use-keyboard-shortcuts';

/**
 * Property-based tests for the pure guard functions in use-keyboard-shortcuts.
 *
 * These tests generate random combinations of keyboard events and DOM elements
 * to verify that shouldIgnoreEvent behaves correctly across all inputs.
 */

// --- Arbitraries ---

const TEXT_INPUT_TYPES = [
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
] as const;

const NON_TEXT_ELEMENTS = [
    'div',
    'span',
    'body',
    'button',
    'section',
    'article',
    'main',
    'nav',
] as const;

/** Arbitrary that generates a random printable key string */
const arbKey = fc.oneof(
    fc.constantFrom('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'),
    fc.constantFrom('ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'),
    fc.constantFrom('Enter', 'Escape', 'Tab', 'Backspace', 'Delete', ' '),
    fc.string({ minLength: 1, maxLength: 3 }),
);

/** Arbitrary that creates a text-input element and focuses it */
const arbTextInputElement = fc.oneof(
    // <input> with text-accepting type
    fc.constantFrom(...TEXT_INPUT_TYPES).map((type) => {
        const el = document.createElement('input');
        el.type = type;

        return el;
    }),
    // <textarea>
    fc.constant(null).map(() => document.createElement('textarea')),
    // <select>
    fc.constant(null).map(() => document.createElement('select')),
    // contenteditable element
    fc.constantFrom('div', 'span', 'p').map((tag) => {
        const el = document.createElement(tag);
        el.setAttribute('contenteditable', 'true');

        return el;
    }),
);

/** Arbitrary that creates a non-text-input element */
const arbNonTextElement = fc.oneof(
    fc
        .constantFrom(...NON_TEXT_ELEMENTS)
        .map((tag) => document.createElement(tag)),
    fc.constant(null as Element | null),
);

/** Arbitrary that generates modifier flags where at least one is true */
const arbAtLeastOneModifier = fc
    .record({
        ctrlKey: fc.boolean(),
        metaKey: fc.boolean(),
        altKey: fc.boolean(),
        shiftKey: fc.boolean(),
    })
    .filter(
        (mods) => mods.ctrlKey || mods.metaKey || mods.altKey || mods.shiftKey,
    );

// --- Helpers ---

function createKeyboardEvent(
    key: string,
    modifiers: {
        ctrlKey?: boolean;
        metaKey?: boolean;
        altKey?: boolean;
        shiftKey?: boolean;
    } = {},
): KeyboardEvent {
    return new KeyboardEvent('keydown', {
        key,
        ctrlKey: modifiers.ctrlKey ?? false,
        metaKey: modifiers.metaKey ?? false,
        altKey: modifiers.altKey ?? false,
        shiftKey: modifiers.shiftKey ?? false,
        bubbles: true,
    });
}

function focusElement(element: Element | null): void {
    if (element) {
        document.body.appendChild(element);
        (element as HTMLElement).focus();
        // Ensure activeElement is set via defineProperty as jsdom focus can be unreliable
        Object.defineProperty(document, 'activeElement', {
            value: element,
            writable: true,
            configurable: true,
        });
    } else {
        Object.defineProperty(document, 'activeElement', {
            value: document.body,
            writable: true,
            configurable: true,
        });
    }
}

function cleanupFocus(): void {
    Object.defineProperty(document, 'activeElement', {
        value: document.body,
        writable: true,
        configurable: true,
    });

    // Remove any appended elements
    while (document.body.firstChild) {
        document.body.removeChild(document.body.firstChild);
    }
}

// --- Property Tests ---

describe('Feature: keyboard-shortcuts, Property 1: Text-input element suppression', () => {
    /**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     *
     * For any keyboard event with no modifiers and for any active element that is
     * a text-input element, shouldIgnoreEvent SHALL return true.
     */
    it('shouldIgnoreEvent returns true when active element is a text-input element', () => {
        fc.assert(
            fc.property(arbTextInputElement, arbKey, (element, key) => {
                focusElement(element);
                const event = createKeyboardEvent(key, {
                    ctrlKey: false,
                    metaKey: false,
                    altKey: false,
                    shiftKey: false,
                });

                const result = shouldIgnoreEvent(event);
                cleanupFocus();

                expect(result).toBe(true);
            }),
            { numRuns: 100 },
        );
    });
});

describe('Feature: keyboard-shortcuts, Property 2: Non-text-input elements allow shortcuts', () => {
    /**
     * **Validates: Requirements 3.5**
     *
     * For any keyboard event with no modifier keys pressed and for any active element
     * that is NOT a text-input element, shouldIgnoreEvent SHALL return false.
     */
    it('shouldIgnoreEvent returns false when active element is not a text-input element and no modifiers pressed', () => {
        fc.assert(
            fc.property(arbNonTextElement, arbKey, (element, key) => {
                focusElement(element);
                const event = createKeyboardEvent(key, {
                    ctrlKey: false,
                    metaKey: false,
                    altKey: false,
                    shiftKey: false,
                });

                const result = shouldIgnoreEvent(event);
                cleanupFocus();

                expect(result).toBe(false);
            }),
            { numRuns: 100 },
        );
    });
});

describe('Feature: keyboard-shortcuts, Property 3: Modifier key suppression', () => {
    /**
     * **Validates: Requirements 4.1, 4.2, 4.3, 4.4**
     *
     * For any keyboard event where at least one modifier key is true,
     * shouldIgnoreEvent SHALL return true regardless of key or active element.
     */
    it('shouldIgnoreEvent returns true when any modifier key is pressed', () => {
        fc.assert(
            fc.property(
                fc.oneof(arbTextInputElement, arbNonTextElement),
                arbKey,
                arbAtLeastOneModifier,
                (element, key, modifiers) => {
                    focusElement(element);
                    const event = createKeyboardEvent(key, modifiers);

                    const result = shouldIgnoreEvent(event);
                    cleanupFocus();

                    expect(result).toBe(true);
                },
            ),
            { numRuns: 100 },
        );
    });
});

describe('Feature: keyboard-shortcuts, Property 4: No-modifier events are evaluated', () => {
    /**
     * **Validates: Requirements 4.5**
     *
     * For any keyboard event where all modifier keys are false and the active element
     * is not a text-input element, shouldIgnoreEvent SHALL return false.
     */
    it('shouldIgnoreEvent returns false when no modifiers pressed and element is not text-input', () => {
        fc.assert(
            fc.property(arbNonTextElement, arbKey, (element, key) => {
                focusElement(element);
                const event = createKeyboardEvent(key, {
                    ctrlKey: false,
                    metaKey: false,
                    altKey: false,
                    shiftKey: false,
                });

                const result = shouldIgnoreEvent(event);
                cleanupFocus();

                expect(result).toBe(false);
            }),
            { numRuns: 100 },
        );
    });
});
