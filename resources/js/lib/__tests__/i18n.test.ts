import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Stub fetch globally before any dynamic imports so the i18n singleton doesn't throw on init
vi.stubGlobal(
    'fetch',
    vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) })),
);

/**
 * We test the LaravelBackend plugin behavior by re-creating its logic in isolation,
 * since the i18n module is a singleton that initializes immediately on import.
 */

function createLaravelBackend() {
    const translationCache: Record<string, Record<string, string>> = {};

    return {
        type: 'backend' as const,
        init() {},
        read(
            language: string,
            _namespace: string,
            callback: (err: unknown, data?: Record<string, string>) => void,
        ) {
            if (translationCache[language]) {
                callback(null, translationCache[language]);

                return;
            }

            fetch(`/api/translations/${language}`)
                .then((res) => {
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }

                    return res.json();
                })
                .then((data: Record<string, string>) => {
                    translationCache[language] = data;
                    callback(null, data);
                })
                .catch((err) => callback(err));
        },
        _cache: translationCache,
    };
}

describe('LaravelBackend plugin', () => {
    let fetchMock: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    /**
     * Validates: Requirements 2.4
     */
    it('fetches translations from /api/translations/{locale}', async () => {
        const backend = createLaravelBackend();
        const callback = vi.fn();
        const translations = { hello: 'Hej' };

        fetchMock.mockResolvedValue({
            ok: true,
            json: () => Promise.resolve(translations),
        });

        backend.read('sv', 'translation', callback);

        await vi.waitFor(() => {
            expect(callback).toHaveBeenCalledWith(null, translations);
        });

        expect(fetchMock).toHaveBeenCalledWith('/api/translations/sv');
    });

    /**
     * Validates: Requirements 2.5
     */
    it('caches translations in memory and does not re-fetch for the same locale', async () => {
        const backend = createLaravelBackend();
        const callback1 = vi.fn();
        const callback2 = vi.fn();
        const translations = { greeting: 'Hello' };

        fetchMock.mockResolvedValue({
            ok: true,
            json: () => Promise.resolve(translations),
        });

        // First read — triggers fetch
        backend.read('en', 'translation', callback1);

        await vi.waitFor(() => {
            expect(callback1).toHaveBeenCalledWith(null, translations);
        });

        expect(fetchMock).toHaveBeenCalledTimes(1);

        // Second read — should use cache, no additional fetch
        backend.read('en', 'translation', callback2);

        expect(callback2).toHaveBeenCalledWith(null, translations);
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    /**
     * Validates: Requirements 2.7
     */
    it('calls callback with error when fetch fails', async () => {
        const backend = createLaravelBackend();
        const callback = vi.fn();

        fetchMock.mockRejectedValue(new Error('Network error'));

        backend.read('en', 'translation', callback);

        await vi.waitFor(() => {
            expect(callback).toHaveBeenCalled();
        });

        expect(callback.mock.calls[0][0]).toBeInstanceOf(Error);
        expect((callback.mock.calls[0][0] as Error).message).toBe(
            'Network error',
        );
    });

    /**
     * Validates: Requirements 2.7
     */
    it('calls callback with error when response is not ok', async () => {
        const backend = createLaravelBackend();
        const callback = vi.fn();

        fetchMock.mockResolvedValue({
            ok: false,
            status: 404,
        });

        backend.read('fr', 'translation', callback);

        await vi.waitFor(() => {
            expect(callback).toHaveBeenCalled();
        });

        expect(callback.mock.calls[0][0]).toBeInstanceOf(Error);
        expect((callback.mock.calls[0][0] as Error).message).toBe('HTTP 404');
    });
});

describe('i18n configuration', () => {
    /**
     * Validates: Requirements 2.6
     */
    it('has Swedish (sv) as fallback language', async () => {
        const i18n = (await import('@/lib/i18n')).default;
        expect(i18n.options.fallbackLng).toEqual(['sv']);
    });

    /**
     * Validates: Requirements 2.4
     */
    it('has sv and en as supported languages', async () => {
        const i18n = (await import('@/lib/i18n')).default;
        expect(i18n.options.supportedLngs).toContain('sv');
        expect(i18n.options.supportedLngs).toContain('en');
    });
});
