import { PAPER } from '../config.js';
import { clamp } from '../util/math.js';

const FIREBALL = ['#ffffff', '#fff6b0', '#ffd23f', '#ff8a1e', '#e8203a', '#5a2a1e', '#2e2b28'];

/** Explosions, étincelles d'impact, notes de musique et textes qui s'envolent. */
export class EffectPainter {
    constructor(ctx, brush, state, width) {
        this.ctx = ctx;
        this.brush = brush;
        this.state = state;
        this.width = width;
    }

    /** Boule de feu façon Metal Slug : elle gonfle, change de couleur et monte en fumant. */
    explosion(ex) {
        const { t } = ex;
        const radius = t < 8 ? 4 + t * 2 : Math.max(2, 20 - (t - 8) * 0.8);
        const color = FIREBALL[Math.min(FIREBALL.length - 1, Math.floor(t / 4))];
        const y = ex.y - radius - t * 0.6;
        this.brush.disc(ex.x, y, Math.round(radius), color);
        if (t < 16) this.brush.disc(ex.x, y, Math.round(radius * 0.5), FIREBALL[Math.max(0, Math.floor(t / 4) - 2)]);
    }

    /** Étoile d'impact (t < 0 : apparition différée). */
    spark(s) {
        if (s.t < 0) return;
        const { ctx } = this;
        const r = 2 + s.t;
        const x = Math.round(s.x);
        const y = Math.round(s.y);
        ctx.fillStyle = s.t % 2 ? '#ffffff' : this.state.level.accent;
        ctx.fillRect(x - r, y, r * 2 + 1, 1);
        ctx.fillRect(x, y - r, 1, r * 2 + 1);
        const d = Math.round(r * 0.6);
        for (const [sx, sy] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
            ctx.fillRect(x + sx * d, y + sy * d, 1, 1);
        }
    }

    note(note) {
        if (note.t > 50 && note.t % 4 < 2) return; // clignote avant de disparaître
        this.ctx.drawImage(note.image, Math.round(note.x), Math.round(note.y));
    }

    /** Cri ou bonus qui monte, détouré de noir, sans sortir de l'écran. */
    text(t) {
        const { ctx, state } = this;
        ctx.font = '8px "Press Start 2P"';
        ctx.textAlign = 'center';
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#000';
        const half = ctx.measureText(t.text).width / 2;
        const x = clamp(t.x, state.cam + half + 4, state.cam + this.width - half - 4);
        const y = Math.max(12, Math.round(t.y - t.t * 0.3));
        if (t.t > 70 && t.t % 4 < 2) return;
        ctx.strokeText(t.text, x, y);
        ctx.fillStyle = t.color ?? (t.t % 8 < 4 ? state.level.accent : PAPER);
        ctx.fillText(t.text, x, y);
    }
}
