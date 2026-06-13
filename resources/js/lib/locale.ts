import i18n from '@/lib/i18n';

/**
 * Maps short app locale codes to BCP 47 locale tags for date/time formatting.
 */
const LOCALE_MAP: Record<string, string> = {
    sv: 'sv-SE',
    en: 'en-GB',
};

/**
 * Returns the BCP 47 locale tag for date/time formatting.
 * Reactively maps the current i18n language to the full tag.
 */
export function getAppLocale(): string {
    return LOCALE_MAP[i18n.language] ?? 'sv-SE';
}

/** @deprecated Use getAppLocale() for reactive locale. Kept for backward compat during migration. */
export const APP_LOCALE = 'sv-SE';
