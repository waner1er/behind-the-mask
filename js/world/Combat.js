import { DEMO, HEART_EVERY_KILLS } from '../config.js';
import { pick } from '../util/math.js';

/** Les coups : ceux du héros, ceux qu'il encaisse, et les K.O. */
export class Combat {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /**
     * Le héros frappe devant lui entre minReach et maxReach : ennemis, otages (liens coupés) et caisse mystère.
     * Chaque cible n'est touchée qu'une fois par attaque (p.hits).
     */
    heroHits(p, minReach, maxReach, knockback, damage) {
        const { state } = this;
        if (state.demo) damage *= DEMO.damage;

        for (const e of state.enemies) {
            if (e.isUntouchable || p.hits.has(e.id)) continue;
            const bulk = (e.scale - 1) * 12; // les gros boss sont plus faciles à toucher
            const reach = (e.x - p.x) * p.dir;
            if (reach < minReach - bulk || reach > maxReach + bulk || Math.abs(e.y - p.y) > 7 + bulk / 4) continue;

            p.hits.add(e.id);
            e.hp -= damage;
            state.score += 10 * damage;
            state.shake = 3;
            state.target(e);
            state.sparks.push({ x: e.x - p.dir * 4 * e.scale, y: e.y - 22 * e.scale, t: 0 });

            if (e.hp <= 0) {
                this.killEnemy(e, p.dir);
            } else if (e.boss && e.state === 'charge') {
                this.game.sfx('heavyHit'); // le boss encaisse sans s'arrêter
            } else {
                e.dir = -p.dir;
                e.setState('hurt');
                e.vx = p.dir * (e.boss ? knockback * 0.45 : knockback);
                this.game.sfx(e.boss ? 'heavyHit' : 'hit');
            }
        }

        const inReach = (x) => {
            const reach = (x - p.x) * p.dir;
            return reach >= minReach && reach <= maxReach;
        };
        for (const pow of state.pows) {
            if (!pow.freed && inReach(pow.x) && Math.abs(pow.y - p.y) < 10) this.game.hostages.free(pow);
        }
        for (const box of state.boxes) {
            if (box.hp > 0 && !p.hits.has(box) && inReach(box.x) && Math.abs(box.y - p.y) < 12) {
                p.hits.add(box);
                this.game.boxes.hit(box);
            }
        }
    }

    /** K.O. : l'ennemi tombe à la renverse et lâche parfois quelque chose. Un boss K.O. emporte tout le monde. */
    killEnemy(e, fromDir) {
        const { game, state } = this;
        e.dir = -fromDir;
        e.setState('dead');
        e.vx = fromDir * 2.4;
        state.score += e.boss ? 5000 : e.cfg.score;
        game.sfx(e.boss ? 'heavyHit' : 'hit');

        if (e.boss) {
            game.sfx('bossDeath', e.scale);
            state.flash = 12;
            state.shake = 8;
            game.shout('K.O.!', e.x, e.y - 50 * e.scale);
            for (const other of state.enemies) {
                if (other !== e && !other.isDown) this.killEnemy(other, other.x < e.x ? -1 : 1);
            }
            state.projectiles = [];
            return;
        }

        game.sfx('death', e.type);
        if (state.level.shouts.length) game.shout(pick(state.level.shouts), e.x, e.y - 46);
        this.#loot(e, fromDir);
    }

    /** Le héros p encaisse un coup venu de fromX. Renvoie false s'il l'esquive (invulnérable, en l'air...). */
    damagePlayer(p, amount, fromX) {
        const { game, state } = this;
        if (state.demo) return false; // en démo, Pete ne prend pas de coups
        if (p.invuln || p.isDown || p.z > 10) return false;

        const dir = p.x > fromX ? 1 : -1;
        p.hp -= amount;
        p.invuln = 75;
        p.dir = -dir;
        state.shake = 4;
        state.sparks.push({ x: p.x - dir * 4, y: p.y - 24, t: 0 });

        if (p.hp <= 0) {
            p.hp = 0;
            p.weapon = null;
            state.lives[p.slot]--;
            p.setState('dead');
            p.vx = dir * 2;
            game.sfx('heavyHit');
            game.sfx('heroDeath');
        } else {
            p.setState('hurt');
            p.vx = dir * 2.5;
            game.sfx('hurt');
        }
        return true;
    }

    /** L'arme de l'ennemi lui échappe ; sinon une bière ou des vinyles, et un cœur tous les 10 K.O. */
    #loot(e, fromDir) {
        const { pickups } = this.game;
        if (e.cfg.weapon) {
            pickups.spawn('weapon', e.x, e.y, { fly: true, weapon: e.cfg.weapon, dir: fromDir });
        } else if (Math.random() < 0.15) {
            pickups.spawn('beer', e.x, e.y, { fly: true });
        }
        if (Math.random() < 0.07) pickups.spawn('vinyls', e.x, e.y, { fly: true });

        this.state.kills++;
        if (this.state.kills % HEART_EVERY_KILLS === 0) pickups.spawn('life', e.x, e.y, { fly: true });
    }
}
