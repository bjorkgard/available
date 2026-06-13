import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const translationCache: Record<string, Record<string, string>> = {};

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
        lng: 'sv',
        fallbackLng: 'sv',
        supportedLngs: ['sv', 'en'],
        ns: ['translation'],
        defaultNS: 'translation',
        interpolation: { escapeValue: false },
        react: { useSuspense: false },
    });

export { translationCache };
export default i18n;
