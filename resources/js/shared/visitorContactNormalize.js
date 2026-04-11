/**
 * Зеркало App\ContactChannels\VisitorContactNormalizer для публичных форм (без PHP round-trip).
 */

/** Telegram / VK: в поле допускаем только печатный ASCII (ник и URL). */
export function preferredChannelNeedsAsciiValue(channelId) {
    return channelId === 'telegram' || channelId === 'vk';
}

export function stripToAsciiContactTyping(raw) {
    return String(raw ?? '').replace(/[^\x20-\x7E]/g, '');
}

/**
 * @param {string} raw
 * @returns {string|null} username без @ в нижнем регистре или null
 */
export function normalizeTelegramVisitorInput(raw) {
    const s = String(raw ?? '').trim();
    if (s === '') {
        return null;
    }
    const m = s.match(/^(?:https?:\/\/)?(?:t\.me|telegram\.me)\/([a-zA-Z0-9_]+)/i);
    if (m) {
        return m[1].toLowerCase();
    }
    const u = s.replace(/^@+/, '');
    if (/^[a-zA-Z0-9_]{5,32}$/.test(u)) {
        return u.toLowerCase();
    }

    return null;
}

/**
 * @param {string} raw
 * @returns {string|null} канонический https://vk.com/… или null
 */
export function normalizeVkVisitorInput(raw) {
    const s = String(raw ?? '').trim();
    if (s === '') {
        return null;
    }
    let m = s.match(/^https?:\/\/(?:m\.)?vk\.com\/([a-zA-Z0-9._-]+)\/?$/i);
    if (m) {
        return 'https://vk.com/' + m[1];
    }
    m = s.match(/^vk\.com\/([a-zA-Z0-9._-]+)$/i);
    if (m) {
        return 'https://vk.com/' + m[1];
    }
    if (/^[a-zA-Z0-9._-]{2,}$/.test(s) && !s.includes('://')) {
        return 'https://vk.com/' + s;
    }

    return null;
}
