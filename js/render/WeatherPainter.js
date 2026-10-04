/** Par-dessus tout, sans suivre la caméra : explosions au loin, braises, cendres, pluie, brouillard. */
export class WeatherPainter {
    constructor(ctx, brush, state, width) {
        this.ctx = ctx;
        this.brush = brush;
        this.state = state;
        this.width = width;
    }

    draw() {
        const { ctx, state } = this;

        for (const f of state.flashes) {
            ctx.globalAlpha = Math.max(0, 1 - f.t / 30) * 0.7;
            this.brush.disc(f.x, f.y, Math.round(2 + f.t * 0.6), f.t < 6 ? '#fff6b0' : '#ff8a1e');
            ctx.globalAlpha = 1;
        }
        for (const pt of state.particles) {
            ctx.fillStyle = pt.kind === 'ember' ? (pt.t % 20 < 10 ? '#ffd23f' : '#ff5a1e') : 'rgba(60,56,52,0.7)';
            ctx.fillRect(Math.round(pt.x), Math.round(pt.y), 1, 1);
        }
        if (state.level.rain) {
            ctx.fillStyle = 'rgba(210,220,235,0.45)';
            for (const drop of state.rain) ctx.fillRect(Math.round(drop.x), Math.round(drop.y), 1, 4);
        }
        if (state.level.fog) {
            ctx.fillStyle = 'rgba(155,77,255,0.08)';
            for (let i = 0; i < 3; i++) {
                const offset = (state.tick * (0.2 + i * 0.15) + i * 90) % (this.width + 120) - 120;
                ctx.fillRect(Math.round(offset), 118 + i * 14, 160, 30);
            }
        }
    }
}
