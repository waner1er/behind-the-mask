import { RACE } from '../config.js';
import { clamp } from '../util/math.js';

/**
 * Les ennemis de la descente : ils remontent (ou rattrapent) les héros, se calent à leur hauteur,
 * d'un côté, et frappent quand ils sont assez près. Fatigués, ils décrochent.
 */
export class RiderAI {
    /** Images d'élan avant le coup : le temps de le voir venir. */
    static WIND = 22;

    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    update(r) {
        const { game } = this;
        r.t++;
        r.anim++;
        if (r.isDown) return this.#crashing(r);

        const target = this.#target(r);
        const dz = r.z - game.race.playerZ;
        const engaged = target && Math.abs(dz) < RACE.engage * RACE.segment;
        if (engaged) r.engaged++;
        this.#pace(r, dz, engaged);

        switch (r.state) {
            case 'hurt':
                r.x += r.vx;
                r.vx *= 0.85;
                if (r.t > 18) r.setState('ride');
                break;
            case 'wind':
                if (r.t >= RiderAI.WIND) r.setState(r.cfg.attack === 'ram' ? 'ram' : 'strike');
                break;
            case 'strike':
                if (r.t === 1) game.brawl.riderStrikes(r);
                if (r.t > 14) r.setState('ride');
                break;
            case 'ram':
                r.x += r.dir * 3.4;
                game.brawl.riderRams(r);
                if (r.t > 16) r.setState('ride');
                break;
            default:
                this.#ride(r, target, dz, engaged);
        }
        if (!r.isDown) r.x = clamp(r.x, game.race.centerX - game.race.halfRoad * 1.15, game.race.centerX + game.race.halfRoad * 1.15);
    }

    /** Le héros debout le plus proche en travers. */
    #target(r) {
        const riding = this.state.players.filter((p) => !p.isDown);
        return riding.sort((a, b) => Math.abs(a.x - r.x) - Math.abs(b.x - r.x))[0];
    }

    /** En profondeur : il colle aux héros quand il se bat, sinon il roule à son allure (ou décroche). */
    #pace(r, dz, engaged) {
        const { race } = this.state;
        let wanted = r.cfg.speed * RACE.cruise * RACE.maxSpeed;
        if (engaged && r.engaged < RACE.stamina) wanted = race.speed + clamp((r.offset - dz) * 0.05, -3, 3);
        else if (r.engaged >= RACE.stamina) wanted = race.speed * 0.6;
        r.speed += clamp(wanted - r.speed, -1, 1);
        r.z += r.speed;
    }

    /** En travers : il se cale à côté de sa cible, du côté où il est, et arme son coup quand elle est à portée. */
    #ride(r, target, dz, engaged) {
        if (!engaged || !target) {
            r.x += Math.sin(r.anim / 40) * 0.4;
            return;
        }
        const side = r.x < target.x ? -1 : 1;
        r.x += clamp(target.x + side * 34 - r.x, -1.2, 1.2);
        const inReach = Math.abs(dz) < RACE.depthReach && Math.abs(r.x - target.x) < 70;
        if (inReach && --r.cooldown <= 0) {
            r.dir = target.x > r.x ? 1 : -1;
            r.cooldown = r.cfg.every;
            r.setState('wind');
        }
    }

    /** Il part en tonneaux sur le côté et reste derrière. */
    #crashing(r) {
        r.x += r.vx;
        r.vx *= 0.97;
        r.speed *= 0.95;
        r.z += r.speed;
        r.spin += 0.35;
    }
}
