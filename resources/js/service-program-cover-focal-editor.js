/**
 * Cover preview geometry — same formulas as App\MediaPresentation\FocalCoverPreviewGeometry (PHP).
 */
const EPS = 1e-6;

export function coverDisplaySize(iw, ih, frameW, frameH) {
    if (iw <= 0 || ih <= 0 || frameW <= 0 || frameH <= 0) {
        return { scale: 1, dispW: frameW, dispH: frameH };
    }
    const scale = Math.max(frameW / iw, frameH / ih);
    return { scale, dispW: iw * scale, dispH: ih * scale };
}

export function translateFromFocal(px, py, frameW, frameH, iw, ih) {
    const { dispW, dispH } = coverDisplaySize(iw, ih, frameW, frameH);
    const tx = Math.abs(frameW - dispW) < EPS ? 0 : (px / 100 - 0.5) * (frameW - dispW);
    const ty = Math.abs(frameH - dispH) < EPS ? 0 : (py / 100 - 0.5) * (frameH - dispH);
    return { tx, ty };
}

export function focalFromTranslate(tx, ty, frameW, frameH, iw, ih) {
    const { dispW, dispH } = coverDisplaySize(iw, ih, frameW, frameH);
    let px = Math.abs(frameW - dispW) < EPS ? 50 : 50 + tx / (frameW - dispW) * 100;
    let py = Math.abs(frameH - dispH) < EPS ? 50 : 50 + ty / (frameH - dispH) * 100;
    px = Math.max(0, Math.min(100, px));
    py = Math.max(0, Math.min(100, py));
    return { x: px, y: py };
}

export function clampTranslate(tx, ty, frameW, frameH, iw, ih) {
    const f = focalFromTranslate(tx, ty, frameW, frameH, iw, ih);
    return translateFromFocal(f.x, f.y, frameW, frameH, iw, ih);
}

export function focalForCommit(x, y) {
    return {
        x: Math.round(Math.max(0, Math.min(100, x)) * 10) / 10,
        y: Math.round(Math.max(0, Math.min(100, y)) * 10) / 10,
    };
}

function getWire(el) {
    const root = el?.closest?.('[wire\\:id]');
    if (!root || !window.Livewire) {
        return null;
    }
    const id = root.getAttribute('wire:id');
    return id ? window.Livewire.find(id) : null;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('serviceProgramCoverFocalEditor', (config) => ({
        config,
        sync: config.syncDefault !== false,
        dragging: null,
        frameRefs: { mobile: null, desktop: null },
        ro: null,
        natural: { mobile: null, desktop: null },
        local: {
            mobile: { x: config.mobile.x, y: config.mobile.y },
            desktop: { x: config.desktop.x, y: config.desktop.y },
        },
        pointerId: null,
        _onWinUp: null,
        _onWinCancel: null,
        _onWinMove: null,
        _onVis: null,

        init() {
            this.sync = config.syncDefault !== false;
            this.local = {
                mobile: { ...config.mobile },
                desktop: { ...config.desktop },
            };
            this._onWinUp = (e) => this.endDrag(e);
            this._onWinCancel = (e) => this.cancelDrag(e);
            this._onWinMove = (e) => this.moveDrag(e);
            this._onVis = () => {
                if (document.visibilityState === 'hidden' && this.dragging) {
                    this.cancelDrag(new Event('pointercancel'));
                }
            };
            window.addEventListener('pointerup', this._onWinUp);
            window.addEventListener('pointercancel', this._onWinCancel);
            document.addEventListener('visibilitychange', this._onVis);
            this.$el.addEventListener('alpine:destroy', () => this.cleanup());
            this.$nextTick(() => this.setupResize());
        },

        onConfigUpdate(newConfig) {
            if (!newConfig) {
                return;
            }
            this.config = { ...this.config, ...newConfig };
            this.sync = newConfig.syncDefault !== false;
            this.local.mobile = { ...newConfig.mobile };
            this.local.desktop = { ...newConfig.desktop };
            this.natural = { mobile: null, desktop: null };
        },

        cleanup() {
            window.removeEventListener('pointerup', this._onWinUp);
            window.removeEventListener('pointercancel', this._onWinCancel);
            window.removeEventListener('pointermove', this._onWinMove);
            document.removeEventListener('visibilitychange', this._onVis);
            if (this.ro) {
                this.ro.disconnect();
                this.ro = null;
            }
        },

        getWire() {
            return getWire(this.$el);
        },

        wirePath() {
            return this.config.wirePathPrefix ?? 'data.cover_presentation.viewport_focal_map';
        },

        frameSize(key) {
            const el = this.frameRefs[key];
            if (!el) {
                return { w: 360, h: 200 };
            }
            const r = el.getBoundingClientRect();
            return { w: Math.max(1, r.width), h: Math.max(1, r.height) };
        },

        naturalFor(key) {
            return key === 'desktop' ? this.natural.desktop : this.natural.mobile;
        },

        setNatural(key, iw, ih) {
            if (key === 'desktop') {
                this.natural.desktop = { iw, ih };
            } else {
                this.natural.mobile = { iw, ih };
            }
        },

        objectPositionStyle(key) {
            const f = key === 'desktop' ? this.local.desktop : this.local.mobile;
            return `${f.x}% ${f.y}%`;
        },

        canDrag(key) {
            const n = this.naturalFor(key);
            return !!(n && n.iw > 0 && n.ih > 0);
        },

        startDrag(key, ev) {
            if (!this.canDrag(key) || ev.button === 2) {
                return;
            }
            ev.preventDefault();
            const frame = this.frameRefs[key];
            if (!frame) {
                return;
            }
            try {
                frame.setPointerCapture(ev.pointerId);
            } catch (_) {
                /* ignore */
            }
            this.pointerId = ev.pointerId;
            window.addEventListener('pointermove', this._onWinMove);
            const n = this.naturalFor(key);
            const { w, h } = this.frameSize(key);
            const focal = key === 'desktop' ? this.local.desktop : this.local.mobile;
            const { tx, ty } = translateFromFocal(focal.x, focal.y, w, h, n.iw, n.ih);
            this.dragging = {
                key,
                startX: ev.clientX,
                startY: ev.clientY,
                startTx: tx,
                startTy: ty,
            };
        },

        moveDrag(ev) {
            if (!this.dragging || ev.pointerId !== this.pointerId) {
                return;
            }
            const { key, startX, startY, startTx, startTy } = this.dragging;
            const n = this.naturalFor(key);
            if (!n) {
                return;
            }
            const { w, h } = this.frameSize(key);
            let tx = startTx + (ev.clientX - startX);
            let ty = startTy + (ev.clientY - startY);
            const c = clampTranslate(tx, ty, w, h, n.iw, n.ih);
            tx = c.tx;
            ty = c.ty;
            const f = focalFromTranslate(tx, ty, w, h, n.iw, n.ih);
            if (key === 'desktop') {
                this.local.desktop = { x: f.x, y: f.y };
                if (this.sync) {
                    this.local.mobile = { x: f.x, y: f.y };
                }
            } else {
                this.local.mobile = { x: f.x, y: f.y };
                if (this.sync) {
                    this.local.desktop = { x: f.x, y: f.y };
                }
            }
        },

        endDrag(ev) {
            if (!this.dragging) {
                return;
            }
            if (ev && ev.pointerId !== undefined && ev.pointerId !== this.pointerId) {
                return;
            }
            window.removeEventListener('pointermove', this._onWinMove);
            const frame = this.frameRefs[this.dragging.key];
            if (frame && this.pointerId != null) {
                try {
                    if (frame.hasPointerCapture?.(this.pointerId)) {
                        frame.releasePointerCapture(this.pointerId);
                    }
                } catch (_) {
                    /* ignore */
                }
            }
            this.dragging = null;
            this.pointerId = null;
            this.commitFocal();
        },

        cancelDrag(ev) {
            if (!this.dragging) {
                return;
            }
            if (ev && ev.pointerId !== undefined && ev.pointerId !== this.pointerId) {
                return;
            }
            window.removeEventListener('pointermove', this._onWinMove);
            const frame = this.frameRefs[this.dragging.key];
            if (frame && this.pointerId != null) {
                try {
                    if (frame.hasPointerCapture?.(this.pointerId)) {
                        frame.releasePointerCapture(this.pointerId);
                    }
                } catch (_) {
                    /* ignore */
                }
            }
            this.dragging = null;
            this.pointerId = null;
            this.resyncFromWire();
        },

        async commitFocal() {
            const wire = this.getWire();
            if (!wire) {
                return;
            }
            const base = this.wirePath();
            const m = focalForCommit(this.local.mobile.x, this.local.mobile.y);
            const d = focalForCommit(this.local.desktop.x, this.local.desktop.y);
            try {
                await wire.set(`${base}.mobile.x`, m.x);
                await wire.set(`${base}.mobile.y`, m.y);
                await wire.set(`${base}.desktop.x`, d.x);
                await wire.set(`${base}.desktop.y`, d.y);
            } catch (_) {
                this.resyncFromWire();
                if (typeof window.dispatchEvent === 'function') {
                    window.dispatchEvent(
                        new CustomEvent('service-program-focal-commit-error', { detail: { base } }),
                    );
                }
            }
        },

        resyncFromWire() {
            const wire = this.getWire();
            if (!wire || !wire.data) {
                return;
            }
            const map = wire.data.cover_presentation?.viewport_focal_map ?? {};
            const mx = parseFloat(map.mobile?.x ?? 50);
            const my = parseFloat(map.mobile?.y ?? 52);
            const dx = parseFloat(map.desktop?.x ?? 50);
            const dy = parseFloat(map.desktop?.y ?? 48);
            this.local.mobile = { x: mx, y: my };
            this.local.desktop = { x: dx, y: dy };
        },

        async nudge(key, dpx, dpy, shift) {
            const step = shift ? 5 : 1;
            const sx = dpx * step;
            const sy = dpy * step;
            const cur = key === 'desktop' ? this.local.desktop : this.local.mobile;
            let x = Math.max(0, Math.min(100, cur.x + sx));
            let y = Math.max(0, Math.min(100, cur.y + sy));
            if (key === 'desktop') {
                this.local.desktop = { x, y };
                if (this.sync) {
                    this.local.mobile = { x, y };
                }
            } else {
                this.local.mobile = { x, y };
                if (this.sync) {
                    this.local.desktop = { x, y };
                }
            }
            await this.commitFocal();
        },

        resetMobile() {
            const d = this.config.defaults.mobile;
            this.local.mobile = { x: d.x, y: d.y };
            if (this.sync) {
                this.local.desktop = { ...this.config.defaults.desktop };
            }
            this.commitFocal();
        },

        resetDesktop() {
            const d = this.config.defaults.desktop;
            this.local.desktop = { x: d.x, y: d.y };
            if (this.sync) {
                this.local.mobile = { ...this.config.defaults.mobile };
            }
            this.commitFocal();
        },

        resetBoth() {
            this.local.mobile = { ...this.config.defaults.mobile };
            this.local.desktop = { ...this.config.defaults.desktop };
            this.commitFocal();
        },

        copyToDesktop() {
            this.local.desktop = { ...this.local.mobile };
            this.commitFocal();
        },

        copyToMobile() {
            this.local.mobile = { ...this.local.desktop };
            this.commitFocal();
        },

        onImgLoad(key, ev) {
            const img = ev.target;
            this.setNatural(key, img.naturalWidth, img.naturalHeight);
            this.$nextTick(() => this.setupResize());
        },

        setupResize() {
            if (this.ro) {
                this.ro.disconnect();
            }
            this.ro = new ResizeObserver(() => {
                /* trigger Alpine re-eval for object-position (same focal, new frame size) */
            });
            ['mobile', 'desktop'].forEach((k) => {
                const el = this.frameRefs[k];
                if (el) {
                    this.ro.observe(el);
                }
            });
        },
    }));
});
