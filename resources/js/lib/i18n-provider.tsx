import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { I18nextProvider } from 'react-i18next';

import i18n from '@/lib/i18n';
import { translationCache } from '@/lib/i18n';

/**
 * Provides the i18next context and syncs the locale with Inertia's shared props.
 * Uses router.on('navigate') to read the locale from page props without needing
 * to be inside the Inertia component tree (usePage is not available in withApp).
 */
export function I18nProvider({ children }: { children: React.ReactNode }) {
    useEffect(() => {
        // The 'navigate' event fires on every page visit including the initial load.
        return router.on('navigate', (event) => {
            const locale = event.detail.page.props.locale as string | undefined;
            const translations = event.detail.page.props.translations as
                | Record<string, string>
                | undefined;

            // Seed the cache with fresh translations from the server
            if (locale && translations && typeof translations === 'object') {
                translationCache[locale] = translations;

                // If i18n already has this language loaded, add the resource bundle
                i18n.addResourceBundle(locale, 'translation', translations, true, true);
            }

            if (locale && i18n.language !== locale) {
                i18n.changeLanguage(locale);
            }
        });
    }, []);

    return <I18nextProvider i18n={i18n}>{children}</I18nextProvider>;
}
