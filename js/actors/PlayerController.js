import { ATTACK, JUMP, SKATE, VINYL } from '../config.js';
import { clamp } from '../util/math.js';

/** Le héros obéit aux commandes : marcher, katana, coup de pied sauté, vinyle, skate, Wall of Death. */
export class PlayerController {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.input = game.input;
    }

    update(p) {
        const { game, state } = this;
        p.anim++;
        p.invuln = Math.max(0, p.invuln - 1);
        p.skateCooldown = Math.max(0, p.skateCooldown - 1);

        if (p.weapon && state.tick >= p.weaponUntil) {
            p.weapon = null; // l'arme ramassée ne dure qu'un temps
            game.shout('KATANA!', p.x, p.y - 48, '#ffffff');
            game.sfx('select');
        }

        if (p.z > 0 && (p.isDown || p.state === 'hurt')) p.z = Math.max(0, p.z - 2); // touché en l'air : il retombe

        switch (p.state) {
            case 'dead': return this.#dying(p);
            case 'jump': return this.#jumpKick(p);
            case 'vinyl': return this.#throwing(p);
            case 'hurt': return this.#reeling(p);
            case 'attack': return this.#slashing(p);
            case 'skate': return this.#skating(p);
            default: return this.#control(p);
        }
    }

    keepOnScreen(p) {
        const { cam } = this.state;
        const { width, floor } = this.game.data;
        p.x = clamp(p.x, cam + 10, cam + width - 10);
        p.y = clamp(p.y, floor.min, floor.max);
    }

    #control(p) {
        const { game, input } = this;

        // ESPACE + V en même temps : WALL OF DEATH !
        const combo = input.isHeld('Space') && input.isHeld('KeyV') && input.wasPressed('Space', 'KeyV');
        if (combo && p.wods > 0) {
            game.wallOfDeath.launch(p);
            return;
        }

        if (input.wasPressed('Space', 'KeyX')) {
            p.setState('attack');
            p.hits.clear();
            return;
        }
        if (input.wasPressed('KeyB')) {
            p.setState('jump');
            p.vz = JUMP.impulse;
            p.z = 0.1;
            p.hits.clear();
            game.sfx('kick');
            return;
        }
        if (input.wasPressed('KeyV')) {
            this.#reachForVinyl(p);
            return;
        }
        if (input.wasPressed('KeyC') && p.skateCooldown === 0) {
            p.setState('skate');
            p.hits.clear();
            p.invuln = Math.max(p.invuln, SKATE.duration);
            game.sfx('skate');
            return;
        }

        const dx = input.axisX;
        const dy = input.axisY;
        if (dx || dy) {
            p.setState('walk');
            p.x += dx * 1.25;
            p.y += dy * 0.8;
            if (dx) p.dir = dx;
        } else {
            p.setState('idle');
        }
        this.keepOnScreen(p);
        game.pickups.collect(p);
    }

    #reachForVinyl(p) {
        if (p.vinyls > 0) {
            p.vinyls--;
            p.setState('vinyl');
        } else {
            this.game.shout('NO VINYL', p.x, p.y - 48, '#ffffff');
            this.game.sfx('empty');
        }
    }

    #dying(p) {
        p.t++;
        p.x += p.vx;
        p.vx *= 0.9;
        if (p.t > 120) this.game.campaign.respawn(p);
    }

    /** Coup de pied sauté : on décolle, on avance, la jambe tendue renverse tout. */
    #jumpKick(p) {
        p.t++;
        p.z += p.vz;
        p.vz -= JUMP.gravity;
        p.x += p.dir * JUMP.speed + this.input.axisX * 0.4;
        if (p.t > 5) this.game.combat.heroHits(p, -4, 30, 4.5, 2);
        if (p.z <= 0 && p.t > 2) {
            p.z = 0;
            p.setState('idle');
            this.game.sfx('land');
        }
        this.keepOnScreen(p);
    }

    /** Lance un vinyle : il tournoie, retombe à ~80 px et explose en notes de musique. */
    #throwing(p) {
        p.t++;
        if (p.t === 6) {
            this.state.projectiles.push({
                sprite: 'vinyl', owner: 'hero', x: p.x + p.dir * 10, y: p.y, z: 18,
                vx: p.dir * 2.8, vy: 0, vz: 1.6, gravity: 0.16, damage: VINYL.damage, spin: true,
            });
            this.game.sfx('throw');
        }
        if (p.t >= 16) p.setState('idle');
    }

    #reeling(p) {
        p.t++;
        p.x += p.vx;
        p.vx *= 0.85;
        if (p.t > 16) p.setState('idle');
        this.keepOnScreen(p);
    }

    #slashing(p) {
        p.t++;
        if (p.t === ATTACK.hero.hitFrom) this.game.sfx(p.weapon ? 'whoosh' : 'slash');
        if (p.t >= ATTACK.hero.hitFrom && p.t <= ATTACK.hero.hitTo) {
            const weapon = this.game.data.weapons[p.weapon ?? 'staff'];
            this.game.combat.heroHits(p, 4, weapon.reach, weapon.knockback, weapon.damage);
        }
        if (p.t >= ATTACK.hero.end) p.setState('idle');
    }

    /** Attaque en glisse : on fonce katana en avant en renversant tout le monde. */
    #skating(p) {
        p.t++;
        p.x += p.dir * SKATE.speed * (p.t < SKATE.duration - 8 ? 1 : 0.5);
        p.y += this.input.axisY * 0.6;
        this.game.combat.heroHits(p, -6, 36, 3.6, 1);
        this.keepOnScreen(p);
        if (p.t >= SKATE.duration) {
            p.setState('idle');
            p.skateCooldown = SKATE.cooldown;
        }
    }
}
