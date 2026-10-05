/** Dessine les personnages : choisit l'image de l'animation selon leur état. */
export class FighterPainter {
    constructor(ctx, sprites) {
        this.ctx = ctx;
        this.sprites = sprites;
    }

    draw(f) {
        if (!this.#visible(f)) return;

        const frame = this.#frameOf(f);
        const winding = f.state === 'charge-wind' || f.state === 'lunge-wind';
        const flashing = (f.state === 'hurt' && f.t < 5) || (winding && Math.floor(f.t / 4) % 2);
        const image = flashing ? frame.flash : frame.normal;
        const { ctx } = this;

        ctx.save();
        ctx.translate(Math.round(f.x), Math.round(f.y - (f.z ?? 0)));
        ctx.scale(f.dir < 0 ? -f.scale : f.scale, f.scale);
        if (f.state === 'dead') {
            const fall = Math.min(1, f.t / 16); // tombe à la renverse
            ctx.translate(0, -6 * fall);
            ctx.rotate(-fall * Math.PI / 2);
        }
        ctx.drawImage(image, -this.sprites.anchor.x, -this.sprites.anchor.y);
        ctx.restore();

        this.#speedLines(f);
    }

    /** Clignotements : K.O. qui disparaît, invulnérabilité, téléportation. */
    #visible(f) {
        if (f.state === 'dead' && f.t > 60 && Math.floor(f.t / 4) % 2) return false;
        if (f.invuln && !['hurt', 'dead', 'skate'].includes(f.state) && Math.floor(f.invuln / 3) % 2) return false;
        return !(f.state === 'vanish' && Math.floor(f.t / 2) % 2);
    }

    #frameOf(f) {
        // un héros avec une arme ramassée a ses propres sprites (« hero-bat », « bapt-bat »...)
        const anims = this.sprites.fighters[f.weapon ? `${f.type}-${f.weapon}` : f.type];
        const timing = f.attackTiming;

        switch (f.state) {
            case 'walk':
                return anims.walk[Math.floor(f.anim / 8) % anims.walk.length];
            case 'attack':
                return anims.attack[f.t >= timing.hitFrom && f.t < timing.strikeUntil ? 1 : 0];
            case 'skate':
                return anims.skate[Math.floor(f.anim / 6) % 2];
            case 'jump':
                return f.t > 5 ? anims.kick[0] : anims.jump[0];
            case 'vinyl':
                return anims.attack[f.t < 6 ? 0 : 1];
            case 'lunge':
                return anims.attack[1];
            case 'charge':
                return anims.skate ? anims.skate[0] : anims.attack[1];
            case 'lunge-wind':
            case 'charge-wind':
            case 'summon':
                return anims.attack[0];
            case 'throw':
                return anims.attack[f.t >= 18 ? 1 : 0];
            case 'dead':
                return (anims.dead ?? anims.idle)[0];
            case 'hurt':
                return anims.idle[1];
            default:
                return anims.idle[Math.floor(f.anim / 30) % 2];
        }
    }

    /** Traînée du coup de katana, et de la glisse en skate ou des ruées. */
    #speedLines(f) {
        const timing = f.attackTiming;
        const swinging = f.isHero && f.state === 'attack' && f.t >= timing.hitFrom && f.t <= timing.hitTo + 2;
        const skating = f.state === 'skate' || f.state === 'charge' || f.state === 'lunge';
        if (!swinging && !skating) return;

        this.ctx.fillStyle = 'rgba(255,255,255,0.8)';
        const x = Math.round(f.x);
        const y = Math.round(f.y);
        for (const [offset, length] of [[-24, 14], [-20, 20], [-16, 10]]) {
            const start = swinging
                ? (f.dir > 0 ? x + 36 : x - 36 - length)
                : (f.dir > 0 ? x - 18 - length : x + 18);
            this.ctx.fillRect(start, y + offset + (skating ? 14 : 0), length, 1);
        }
    }
}
