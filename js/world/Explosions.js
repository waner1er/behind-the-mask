import { VINYL } from '../config.js';
import { pick, rand } from '../util/math.js';

/** Explosions façon Metal Slug : boule de feu, débris et dégâts tout autour. */
export class Explosions {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /** byHero : le vinyle du héros explose en notes de musique et blesse les ennemis ; sinon c'est le héros qui trinque. */
    blast(x, y, byHero) {
        const { game, state } = this;
        state.explosions.push({ x, y, t: 0 });
        state.shake = 7;
        game.sfx('explosion');

        if (byHero) {
            game.sfx('scratch');
            for (let i = 0; i < 12; i++) {
                state.notes.push({
                    x: x + rand(-6, 6), y: y - rand(8, 20), vx: rand(-1.8, 1.8), vy: -rand(1.2, 3),
                    t: 0, image: pick(game.sprites.notes),
                });
            }
        }
        for (let i = 0; i < 6; i++) state.sparks.push({ x: x + rand(-14, 14), y: y - rand(4, 24), t: -i });

        if (byHero) {
            this.#vinylDamage(x, y);
        } else {
            for (const p of state.players) {
                if (Math.abs(p.x - x) < 20 && Math.abs(p.y - y) < 10) game.combat.damagePlayer(p, 14, x);
            }
        }
    }

    #vinylDamage(x, y) {
        const { game, state } = this;
        const inBlast = (target, radius) => Math.abs(target.x - x) <= radius && Math.abs(target.y - y) <= 14;

        for (const e of state.enemies) {
            if (e.isUntouchable || !inBlast(e, VINYL.radius + (e.scale - 1) * 10)) continue;
            const dir = e.x < x ? -1 : 1;
            e.hp -= VINYL.damage;
            state.score += 50;
            state.target(e);
            if (e.hp <= 0) {
                game.combat.killEnemy(e, dir);
            } else if (!e.boss || e.state !== 'charge') {
                e.setState('hurt');
                e.dir = -dir;
                e.vx = dir * (e.boss ? 1.5 : 4);
            }
        }
        for (const pow of state.pows) {
            if (!pow.freed && Math.abs(pow.x - x) < VINYL.radius && Math.abs(pow.y - y) < 14) game.hostages.free(pow);
        }
        for (const box of state.boxes) {
            if (box.hp > 0 && Math.abs(box.x - x) < VINYL.radius && Math.abs(box.y - y) < 14) {
                box.hp = 1; // un vinyle suffit à casser la caisse
                game.boxes.hit(box);
            }
        }
    }
}
