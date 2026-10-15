import { ATTACK, JUMP, RACE, SKATE } from '../config.js';
import { clamp } from '../util/math.js';

/**
 * Un héros sur sa planche : GAUCHE/DROITE pour se faufiler, ESPACE pour un coup de katana
 * (du côté de l'ennemi le plus proche, comme dans Street Racer), B pour un ollie,
 * C pour une poussée qui renverse tout, V pour un vinyle lancé devant, ESPACE + V : Wall of Death.
 */
export class SkaterController {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    update(p) {
        const { game } = this;
        p.anim++;
        p.invuln = Math.max(0, p.invuln - 1);
        p.skateCooldown = Math.max(0, p.skateCooldown - 1);
        this.#airborne(p);

        switch (p.state) {
            case 'dead':
                p.t++;
                p.x += p.vx;
                p.vx *= 0.9;
                if (p.t > 120) game.campaign.respawn(p);
                return;
            case 'hurt':
                p.t++;
                p.x += p.vx;
                p.vx *= 0.85;
                if (p.t > 20) p.setState('idle');
                return this.#keepOnRoad(p);
            case 'attack': return this.#slashing(p);
            case 'skate': return this.#pushing(p);
            case 'vinyl':
                p.t++;
                if (p.t === 6) game.brawl.throwVinyl(p);
                if (p.t >= 16) p.setState('idle');
                return this.#steer(p, 1);
            default: return this.#control(p);
        }
    }

    /** L'ollie : la planche décolle, rien ne touche Pete tant qu'il est en l'air. */
    #airborne(p) {
        if (p.z <= 0 && p.vz <= 0) return;
        p.z += p.vz;
        p.vz -= JUMP.gravity;
        if (p.z > 0) return;
        Object.assign(p, { z: 0, vz: 0 });
        if (p.state === 'jump') p.setState('idle');
        this.game.sfx('land');
    }

    #control(p) {
        const { game } = this;
        const input = game.inputOf(p);

        const combo = input.isHeld('Space') && input.isHeld('KeyV') && input.wasPressed('Space', 'KeyV');
        if (combo && p.wods > 0) {
            game.brawl.wallOfDeath(p);
            return;
        }
        if (input.wasPressed('Space', 'KeyX')) {
            p.setState('attack');
            p.hits.clear();
            p.dir = game.brawl.sideOf(p, input.axisX);
            return;
        }
        if (input.wasPressed('KeyB') && p.z === 0) {
            p.setState('jump');
            Object.assign(p, { vz: JUMP.impulse, z: 0.1 });
            game.sfx('kick');
        } else if (input.wasPressed('KeyV')) {
            this.#reachForVinyl(p);
        } else if (input.wasPressed('KeyC') && p.skateCooldown === 0) {
            p.setState('skate');
            p.hits.clear();
            p.invuln = Math.max(p.invuln, SKATE.duration);
            game.sfx('skate');
        }
        this.#steer(p, 1);
    }

    #reachForVinyl(p) {
        if (p.vinyls > 0) {
            p.vinyls--;
            p.setState('vinyl');
        } else {
            this.game.shout('NO VINYL', p.x, p.y - 56, '#ffffff');
            this.game.sfx('empty');
        }
    }

    #slashing(p) {
        p.t++;
        if (p.t === ATTACK.hero.hitFrom) this.game.sfx('slash');
        if (p.t >= ATTACK.hero.hitFrom && p.t <= ATTACK.hero.hitTo) this.game.brawl.heroSlash(p);
        if (p.t >= ATTACK.hero.end) p.setState('idle');
        this.#steer(p, 0.6);
    }

    /** Poussée : un coup d'accélérateur, et tout ce qu'il frôle tombe. */
    #pushing(p) {
        p.t++;
        this.game.brawl.boostRam(p);
        if (p.t >= SKATE.duration) {
            p.setState('idle');
            p.skateCooldown = SKATE.cooldown;
        }
        this.#steer(p, 1);
    }

    #steer(p, factor) {
        const dx = this.game.inputOf(p).axisX;
        p.x += dx * RACE.steer * factor;
        this.#keepOnRoad(p);
    }

    /** On peut mordre sur le trottoir (ça freine), pas sortir de l'écran. */
    #keepOnRoad(p) {
        const { race } = this.game;
        p.x = clamp(p.x, race.centerX - race.halfRoad * 1.15, race.centerX + race.halfRoad * 1.15);
    }
}
