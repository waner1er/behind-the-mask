/** Petites primitives de dessin « pixel » sur le canvas du jeu. */
export class PixelBrush {
    constructor(ctx) {
        this.ctx = ctx;
    }

    /** Disque plein (explosions, éclairs au loin). */
    disc(cx, cy, radius, color) {
        this.ctx.fillStyle = color;
        for (let dy = -radius; dy <= radius; dy++) {
            const half = Math.floor(Math.sqrt(radius * radius - dy * dy));
            this.ctx.fillRect(Math.round(cx - half), Math.round(cy + dy), half * 2 + 1, 1);
        }
    }

    /** Ombre au sol sous un personnage ou un objet. */
    shadow(x, y, size = 10) {
        const { ctx } = this;
        ctx.fillStyle = 'rgba(0,0,0,0.4)';
        ctx.fillRect(Math.round(x) - size, Math.round(y) - 1, size * 2, 2);
        ctx.fillRect(Math.round(x) - size + 3, Math.round(y) + 1, size * 2 - 6, 1);
    }

    heart(x, y) {
        const { ctx } = this;
        ctx.fillStyle = '#ff3ea5';
        x = Math.round(x);
        y = Math.round(y);
        ctx.fillRect(x, y, 2, 1);
        ctx.fillRect(x + 3, y, 2, 1);
        ctx.fillRect(x, y + 1, 5, 1);
        ctx.fillRect(x + 1, y + 2, 3, 1);
        ctx.fillRect(x + 2, y + 3, 1, 1);
    }

    /** Image centrée, en rotation par huitièmes de tour (plus « pixel » qu'une rotation continue). */
    spinning(image, x, y, angle) {
        const { ctx } = this;
        ctx.save();
        ctx.translate(Math.round(x), Math.round(y));
        if (angle) ctx.rotate(Math.round(angle / (Math.PI / 4)) * (Math.PI / 4));
        ctx.drawImage(image, -Math.round(image.width / 2), -Math.round(image.height / 2));
        ctx.restore();
    }

    /** Image posée au sol, centrée sur x, éventuellement retournée et sautillante. */
    standing(image, x, y, { flip = false, hop = 0 } = {}) {
        const { ctx } = this;
        ctx.save();
        ctx.translate(Math.round(x), Math.round(y - hop));
        if (flip) ctx.scale(-1, 1);
        ctx.drawImage(image, -Math.round(image.width / 2), -image.height + 1);
        ctx.restore();
    }

    text(content, x, y, { color, font = 8, align = 'center' } = {}) {
        const { ctx } = this;
        ctx.font = `${font}px "Press Start 2P"`;
        ctx.textAlign = align;
        ctx.fillStyle = color;
        ctx.fillText(content, x, y);
    }
}
