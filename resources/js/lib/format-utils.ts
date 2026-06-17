type TFunction = (key: string, options?: Record<string, unknown>) => string;

/**
 * Formats time remaining until an expiration date as a human-readable string.
 */
export function formatTimeLeft(expiresAt: string, t: TFunction): string {
    const now = Date.now();
    const expires = new Date(expiresAt).getTime();
    const diff = expires - now;

    if (diff <= 0) {
        return t('utgången');
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (days > 0) {
        return t('utgår om {{days}}d {{hours}}h', { days, hours: hours % 24 });
    }

    if (hours > 0) {
        return t('utgår om {{hours}}h', { hours });
    }

    const minutes = Math.floor(diff / (1000 * 60));

    return t('utgår om {{minutes}}m', { minutes });
}

/**
 * Formats time since last activity as a human-readable relative string.
 */
export function formatLastSeen(
    lastActiveAt: string | null,
    t: TFunction,
): string {
    if (!lastActiveAt) {
        return t('Aldrig');
    }

    const now = Date.now();
    const active = new Date(lastActiveAt).getTime();
    const diff = now - active;

    const minutes = Math.floor(diff / (1000 * 60));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const weeks = Math.floor(days / 7);
    const months = Math.floor(days / 30);

    if (minutes < 5) {
        return t('Online nu');
    }

    if (minutes < 60) {
        return t('{{minutes}} min sedan', { minutes });
    }

    if (hours < 24) {
        return t('{{hours}} tim sedan', { hours });
    }

    if (days < 7) {
        return t('{{days}} dagar sedan', { days });
    }

    if (weeks < 5) {
        return t('{{weeks}} veckor sedan', { weeks });
    }

    return t('{{months}} månader sedan', { months });
}
