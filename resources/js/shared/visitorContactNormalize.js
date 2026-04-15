/**
 * Зеркало App\ContactChannels\VisitorContactNormalizer для публичных форм (без PHP round-trip).
 */

/** @param {string} id */
function isValidVkPathSegment(id) {
    if (!id) {
        return false;
    }
    if (id.toLowerCase() === 'vk') {
        return false;
    }
    return /^[a-zA-Z0-9._-]+$/.test(id);
}

/**
 * Сообщения валидации (RU), синхронно с PreferredContactValueMessages (PHP).
 * @param {string} channelId
 */
export function preferredContactValueEmptyMessageRu(channelId) {
    switch (channelId) {
        case 'vk':
            return 'Укажите контакт VK, чтобы мы могли связаться с вами этим способом.';
        case 'telegram':
            return 'Укажите контакт Telegram, чтобы мы могли связаться с вами этим способом.';
        case 'max':
            return 'Укажите контакт MAX, чтобы мы могли связаться с вами этим способом.';
        default:
            return 'Укажите контакт для выбранного способа связи.';
    }
}

/**
 * @param {string} channelId
 */
export function preferredContactValueInvalidMessageRu(channelId) {
    switch (channelId) {
        case 'vk':
            return 'Укажите ссылку на профиль VK или короткое имя (ник), например vk.com/username.';
        case 'telegram':
            return 'Укажите корректный Telegram (username или ссылка https://t.me/…).';
        case 'max':
            return 'Укажите контакт MAX (текст или ссылку).';
        default:
            return 'Проверьте контакт для выбранного способа связи.';
    }
}

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
        if (!isValidVkPathSegment(m[1])) {
            return null;
        }
        return 'https://vk.com/' + m[1];
    }
    m = s.match(/^vk\.com\/([a-zA-Z0-9._-]+)$/i);
    if (m) {
        if (!isValidVkPathSegment(m[1])) {
            return null;
        }
        return 'https://vk.com/' + m[1];
    }
    if (/^[a-zA-Z0-9._-]{2,}$/.test(s) && !s.includes('://')) {
        if (!isValidVkPathSegment(s)) {
            return null;
        }
        return 'https://vk.com/' + s;
    }

    return null;
}
