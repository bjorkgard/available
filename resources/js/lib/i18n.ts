import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const translationCache: Record<string, Record<string, string>> = {};

/**
 * Detect initial locale and translations from the Inertia page props embedded in the HTML.
 */
function detectInitialPageData(): {
    locale: string;
    translations: Record<string, string> | null;
} {
    if (typeof document !== 'undefined') {
        const el = document.getElementById('app');
        const dataPage = el?.getAttribute('data-page');

        if (dataPage) {
            try {
                const page = JSON.parse(dataPage);
                const locale =
                    page?.props?.locale && typeof page.props.locale === 'string'
                        ? page.props.locale
                        : 'sv';
                const translations =
                    page?.props?.translations &&
                    typeof page.props.translations === 'object'
                        ? page.props.translations
                        : null;

                return { locale, translations };
            } catch {
                // Ignore parse errors
            }
        }
    }

    return { locale: 'sv', translations: null };
}

const { locale: initialLocale, translations: initialTranslations } =
    detectInitialPageData();

// Pre-seed the cache with translations from the server
if (initialTranslations) {
    translationCache[initialLocale] = initialTranslations;
}

const LaravelBackend = {
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
};

i18n.use(LaravelBackend)
    .use(initReactI18next)
    .init({
        lng: initialLocale,
        fallbackLng: 'sv',
        supportedLngs: ['sv', 'en'],
        ns: ['translation'],
        defaultNS: 'translation',
        interpolation: { escapeValue: false },
        react: { useSuspense: false },
    });

export { translationCache };
export default i18n;
