import { INK } from '../config.js';

const LEDS = ['#3ef0ff', '#7dff5a', '#e8203a', '#ffd23f'];

/** Les décors et figurants dessinés au canvas pendant l'intro et la fin. */
export class StoryPainter {
    constructor(ctx, brush, state) {
        this.ctx = ctx;
        this.brush = brush;
        this.state = state;
    }

    /** Plan du Docteur Mask : un mur de serveurs qui clignotent et un écran géant « AI ». */
    dataCenter() {
        const { ctx, state } = this;
        for (let col = 0; col < 8; col++) {
            const x = 96 + col * 27;
            ctx.fillStyle = '#3a3f4a';
            ctx.fillRect(x - 1, 39, 24, 103);
            ctx.fillStyle = '#0b0e14';
            ctx.fillRect(x, 40, 22, 101);
            for (let row = 0; row < 24; row++) {
                ctx.fillStyle = '#1a1f2a';
                ctx.fillRect(x + 1, 42 + row * 4, 20, 2);
                const blink = (col * 7 + row * 3 + Math.floor(state.tick / 6)) % 5;
                if (blink < 2) {
                    ctx.fillStyle = LEDS[(col + row) % LEDS.length];
                    ctx.fillRect(x + 3 + ((row * 5 + col) % 15), 42 + row * 4, 2, 1);
                }
            }
        }
        ctx.fillStyle = INK;
        ctx.fillRect(14, 64, 72, 44);
        ctx.fillStyle = state.tick % 40 < 34 ? '#e8203a' : '#ffffff';
        ctx.fillRect(17, 67, 66, 38);
        this.brush.text('AI', 50, 94, { color: INK, font: 16 });
    }

    /** Symboles qui s'envolent, citoyens qui sautillent avec des cœurs, oiseaux. */
    extras() {
        const { ctx, state } = this;
        ctx.font = '8px "Press Start 2P"';
        ctx.textAlign = 'center';
        for (const f of state.fx) {
            ctx.globalAlpha = Math.max(0, 1 - f.t / 90);
            ctx.fillStyle = f.color;
            ctx.fillText(f.text, Math.round(f.x), Math.round(f.y));
        }
        ctx.globalAlpha = 1;

        for (const c of state.citizens) {
            const hop = Math.abs(Math.sin(c.t / 6)) * 5;
            this.brush.shadow(c.x, c.y, 6);
            this.brush.standing(c.image, c.x, c.y, { flip: c.vx < 0, hop });
            if (Math.floor(c.t / 30) % 2) this.brush.heart(c.x - 2, c.y - c.image.height - 6 - hop);
        }

        ctx.fillStyle = INK;
        for (const bird of state.birds) {
            const x = Math.round(bird.x);
            const y = Math.round(bird.y + Math.sin(bird.t / 10) * 3);
            const up = Math.floor(bird.t / 8) % 2;
            ctx.fillRect(x - 3, y - up, 2, 1);
            ctx.fillRect(x - 1, y + 1, 3, 1);
            ctx.fillRect(x + 2, y - up, 2, 1);
        }
    }
}
