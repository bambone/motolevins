/**
 * Mirrors tenant money display rules from PHP ({@see App\Money\MoneyFormatter}).
 * Expects {@code window.__tenantMoneyConfig} set in Blade before app.js loads.
 */

function pow10(exp) {
    return Math.pow(10, exp);
}

function storageToLogicalMajor(storage, storageMode, decimalPlaces) {
    const s = Number(storage);
    if (storageMode === 'minor_integer') {
        return s / pow10(decimalPlaces);
    }
    return s;
}

function logicalToDisplayAmount(logical, applyScale, displayScaleExponent) {
    if (!applyScale || displayScaleExponent <= 0) {
        return logical;
    }
    return logical / pow10(displayScaleExponent);
}

/** @param {number} n */
function formatIntGrouped(n) {
    const neg = n < 0;
    const v = Math.abs(Math.trunc(n));
    const g = String(v).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    return (neg ? '-' : '') + g;
}

function formatMoneyFromStorage(storage, bindingKey) {
    const cfg = window.__tenantMoneyConfig;
    if (!cfg || storage === null || storage === undefined) {
        return String(storage ?? '');
    }
    const meta = cfg.bindings?.[bindingKey] ?? { applyScale: false, storageMode: 'major_integer' };
    const logical = storageToLogicalMajor(Number(storage), meta.storageMode, cfg.decimalPlaces);
    const display = logicalToDisplayAmount(logical, meta.applyScale, cfg.displayScaleExponent);
    let mode = cfg.fractionDisplayMode;
    if (cfg.decimalPlaces === 0) {
        mode = 'never';
    }
    const suffix = (cfg.displayUnitSuffix || '').trim();
    const suf = suffix === '' ? '' : ' ' + suffix;
    if (mode === 'never') {
        return formatIntGrouped(Math.round(display)) + suf;
    }
    const fracDigits = cfg.decimalPlaces;
    const fixed = display.toFixed(fracDigits);
    const dot = fixed.indexOf('.');
    const ip = Number(fixed.slice(0, dot));
    const fp = fixed.slice(dot + 1);
    if (mode === 'auto') {
        const f = fp.replace(/0+$/, '');
        if (!f) {
            return formatIntGrouped(ip) + suf;
        }
        return formatIntGrouped(ip) + ',' + f + suf;
    }
    return formatIntGrouped(ip) + ',' + fp + suf;
}

if (typeof window !== 'undefined') {
    window.TenantMoneyFormat = {
        formatStorage: formatMoneyFromStorage,
    };
}
