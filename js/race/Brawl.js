import { RACE, VINYL } from '../config.js';
import { pick } from '../util/math.js';

/** Taille (demi-largeur, en pixels à la profondeur des héros) des obstacles posés sur la chaussée. */
const OBSTACLE_HALF = { cone: 7, barrier: 18, trash: 9 };

/**
 * Tout ce qui se touche pendant la descente : coups de katana, coups des ennemis,
 * obstacles (évités d'un ollie), bonus ramassés en roulant, vinyles, Wall of Death.
 */
export class Brawl {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    update() {
        const { state } = this;
        for (const p of state.players) {
            if (p.isDown) continue;
            this.#obstacles(p);
            this.#pickups(p);
            this.#bump(p);
        }
        this.#shots();
    }

    /** Le côté du coup : vers l'ennemi le plus proche à portée, sinon celui de la flèche maintenue. */
    sideOf(p, axisX) {
        const near = this.#ridersAround(p, RACE.reach + 20).sort((a, b) => Math.abs(a.x - p.x) - Math.abs(b.x - p.x))[0];
        if (near) return near.x >= p.x ? 1 : -1;
        return axisX || p.dir || 1;
    }

    heroSlash(p) {
        for (const r of this.#ridersAround(p, RACE.reach)) {
            const ahead = (r.x - p.x) * p.dir;
            if (ahead < -8 || this.#struck(p, r)) continue;
            this.hitRider(r, p.weapon ? 2 : 1, p.dir);
        }
    }

    boostRam(p) {
        for (const r of this.#ridersAround(p, 26)) {
            if (!this.#struck(p, r)) this.hitRider(r, 2, r.x >= p.x ? 1 : -1);
        }
    }

    hitRider(r, damage, dir) {
        const { game, state } = this;
        r.hp -= damage;
        state.target(r);
        state.sparks.push({ x: r.x, y: game.race.playerY - 26, t: 0 });
        game.haptics.pulse('hit');
        if (r.hp > 0) {
            r.setState('hurt');
            r.vx = dir * 3;
            game.sfx('hit');
            return;
        }
        r.setState('crash');
        r.vx = dir * 4;
        state.score += r.cfg.score;
        game.sfx('heavyHit');
        if (state.level.shouts.length) game.shout(pick(state.level.shouts), r.x, game.race.playerY - 56);
    }

    riderStrikes(r) {
        for (const p of this.state.players) {
            if (this.#close(p, r, 70)) this.hurtSkater(p, r.cfg.damage, r.x);
        }
    }

    /** Le BMX se jette sur le côté : il s'arrête sur le premier héros qu'il percute. */
    riderRams(r) {
        for (const p of this.state.players) {
            if (!this.#close(p, r, 22)) continue;
            if (this.hurtSkater(p, r.cfg.damage, r.x)) p.vx = r.dir * 5;
            r.setState('ride');
        }
    }

    /** Un héros encaisse ; renvoie false s'il esquive (en l'air, invulnérable...). */
    hurtSkater(p, amount, fromX) {
        const { game, state } = this;
        if (state.demo || p.invuln || p.isDown || p.z > 8) return false;
        const dir = p.x > fromX ? 1 : -1;
        p.hp -= amount;
        p.invuln = 75;
        state.shake = 4;
        state.sparks.push({ x: p.x - dir * 4, y: p.y - 26, t: 0 });
        state.race.speed *= 0.7;
        if (p.hp > 0) {
            p.setState('hurt');
            p.vx = dir * 2.5;
            game.sfx('hurt');
            game.haptics.pulse('hurt', p);
            return true;
        }
        p.hp = 0;
        p.weapon = null;
        state.lives[p.slot]--;
        p.setState('dead');
        p.vx = dir * 2;
        game.sfx('heavyHit');
        game.sfx('heroDeath');
        game.haptics.pulse('ko', p);
        return true;
    }

    throwVinyl(p) {
        this.state.race.shots.push({ x: p.x, z: this.game.race.playerZ, y: 24, t: 0, hit: false });
        this.game.sfx('throw');
    }

    wallOfDeath(p) {
        const { game, state } = this;
        p.wods--;
        state.flash = 16;
        state.shake = 8;
        game.sfx('wod');
        game.haptics.pulse('wod');
        game.shout('WALL OF DEATH!', p.x, p.y - 60, '#e8203a');
        const z = game.race.playerZ;
        for (const r of state.race.riders) {
            if (!r.isDown && r.z > z - 6 * RACE.segment && r.z < z + 50 * RACE.segment) this.hitRider(r, r.hp, r.x >= p.x ? 1 : -1);
        }
    }

    /** Cônes, barrières et poubelles : on les saute d'un ollie, sinon c'est la chute. */
    #obstacles(p) {
        const { game, state } = this;
        const z = game.race.playerZ;
        const seg = state.race.road.at(z);
        for (const o of seg.obstacles) {
            if (o.hit) continue;
            const x = game.race.centerX + o.x * game.race.halfRoad;
            if (Math.abs(x - p.x) > OBSTACLE_HALF[o.sprite] + 8) continue;
            if (p.z > 6) {
                if (!o.cleared) {
                    o.cleared = true;
                    state.score += 200;
                    game.shout('OLLIE!', p.x, p.y - 60, '#7dff5a');
                }
                continue;
            }
            o.hit = true;
            game.sfx('metal');
            if (this.hurtSkater(p, 10, x)) state.race.speed *= 0.5;
        }
    }

    #pickups(p) {
        const { game, state } = this;
        const z = game.race.playerZ;
        for (const item of state.race.pickups) {
            if (item.taken || Math.abs(item.at * RACE.segment - z) > 120 || p.z > 20) continue;
            if (Math.abs(game.race.centerX + item.x * game.race.halfRoad - p.x) > 22) continue;
            item.taken = true;
            game.pickups.apply(item, p);
            game.sfx('pickup');
        }
    }

    /** On se frotte à un ennemi : chacun est repoussé de son côté. */
    #bump(p) {
        for (const r of this.#ridersAround(p, 16)) {
            if (Math.abs(r.z - this.game.race.playerZ) > 80) continue;
            const side = r.x >= p.x ? 1 : -1;
            r.x += side * 1.5;
            p.x -= side * 1.5;
        }
    }

    /** Les vinyles filent droit devant et renversent le premier qu'ils touchent. */
    #shots() {
        const { race } = this.state;
        for (const shot of race.shots) {
            shot.t++;
            shot.z += race.speed + 60;
            const r = race.riders.find((rider) => !rider.isDown && Math.abs(rider.z - shot.z) < 120 && Math.abs(rider.x - shot.x) < 24);
            if (r) {
                shot.hit = true;
                this.hitRider(r, VINYL.damage, r.x >= shot.x ? 1 : -1);
            }
        }
        race.shots = race.shots.filter((shot) => !shot.hit && shot.t < 70);
    }

    /** Les ennemis debout à côté d'un héros (en profondeur) et à moins de reach pixels en travers. */
    #ridersAround(p, reach) {
        const z = this.game.race.playerZ;
        return this.state.race.riders.filter((r) => !r.isDown && Math.abs(r.z - z) < RACE.depthReach && Math.abs(r.x - p.x) <= reach);
    }

    #close(p, r, reach) {
        return !p.isDown && Math.abs(r.z - this.game.race.playerZ) < RACE.depthReach && Math.abs(r.x - p.x) < reach;
    }

    /** Un coup ne touche qu'une fois le même ennemi. */
    #struck(p, r) {
        const key = `${p.state}:${r.id}`;
        if (p.hits.has(key)) return true;
        p.hits.add(key);
        return false;
    }
}
