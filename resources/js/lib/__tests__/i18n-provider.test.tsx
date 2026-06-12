import '@testing-library/jest-dom/vitest';
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { mockRouterOn, mockChangeLanguage } = vi.hoisted(() => ({
    mockRouterOn: vi.fn(),
    mockChangeLanguage: vi.fn(),
}));

// Mock @inertiajs/react
vi.mock('@inertiajs/react', () => ({
    router: {
        on: mockRouterOn,
    },
}));

// Mock i18n
vi.mock('@/lib/i18n', () => ({
    default: {
        language: 'sv',
        changeLanguage: mockChangeLanguage,
        use: () => ({ use: () => ({ init: () => {} }) }),
        init: () => {},
        t: (key: string) => key,
        on: () => {},
        off: () => {},
        options: {},
        isInitialized: true,
        services: { resourceStore: { data: {} } },
        store: { on: () => {} },
        modules: { external: [] },
        hasLoadedNamespace: () => true,
        loadNamespaces: () => Promise.resolve(),
    },
}));

import { I18nProvider } from '@/lib/i18n-provider';

describe('I18nProvider', () => {
    let navigateHandler: ((event: unknown) => void) | null = null;
    let unsubscribe: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        unsubscribe = vi.fn();
        mockRouterOn.mockImplementation(
            (_event: string, handler: (event: unknown) => void) => {
                navigateHandler = handler;

                return unsubscribe;
            },
        );
        mockChangeLanguage.mockClear();
    });

    afterEach(() => {
        cleanup();
        navigateHandler = null;
        mockRouterOn.mockReset();
    });

    it('renders children', () => {
        render(
            <I18nProvider>
                <div data-testid="child">Hello</div>
            </I18nProvider>,
        );

        expect(screen.getByTestId('child')).toBeInTheDocument();
    });

    it('registers a navigate event listener on mount', () => {
        render(
            <I18nProvider>
                <div>Test</div>
            </I18nProvider>,
        );

        expect(mockRouterOn).toHaveBeenCalledWith(
            'navigate',
            expect.any(Function),
        );
    });

    /**
     * Validates: Requirements 2.4
     */
    it('calls changeLanguage when navigate event fires with a different locale', () => {
        render(
            <I18nProvider>
                <div>Test</div>
            </I18nProvider>,
        );

        expect(navigateHandler).not.toBeNull();

        // Simulate a navigate event with a different locale
        navigateHandler!({
            detail: {
                page: {
                    props: { locale: 'en' },
                },
            },
        });

        expect(mockChangeLanguage).toHaveBeenCalledWith('en');
    });

    /**
     * Validates: Requirements 2.5
     */
    it('does not call changeLanguage when locale matches current language', () => {
        render(
            <I18nProvider>
                <div>Test</div>
            </I18nProvider>,
        );

        // Simulate a navigate event with the same locale as i18n.language ('sv')
        navigateHandler!({
            detail: {
                page: {
                    props: { locale: 'sv' },
                },
            },
        });

        expect(mockChangeLanguage).not.toHaveBeenCalled();
    });

    it('does not call changeLanguage when locale prop is undefined', () => {
        render(
            <I18nProvider>
                <div>Test</div>
            </I18nProvider>,
        );

        navigateHandler!({
            detail: {
                page: {
                    props: {},
                },
            },
        });

        expect(mockChangeLanguage).not.toHaveBeenCalled();
    });
});
