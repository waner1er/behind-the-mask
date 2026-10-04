import { clamp, rand } from '../util/math.js';

/**
 * Comportement des ennemis. Les plus proches du héros attaquent (2, puis 3 à partir du niveau 6),
 * les autres tournent autour en attendant leur tour. Attaques spéciales : ruée et lancer.
 */
export class EnemyAI {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /** @param rank place dans la file d'attente (0 = le plus proche du héros, les boss d'abord) */
    update(e, rank) {
        e.anim++;
        switch (e.state) {
            case 'dead': return this.#lyingDown(e);
            case 'hurt': return this.#reeling(e);
            case 'attack': return this.#striking(e);
            case 'throw': return this.#throwing(e);
            case 'lunge-wind':
            case 'lunge': return this.#lunging(e);
        }
        if (e.boss && this.game.bossAI.update(e)) return;
        this.#approach(e, rank);
    }

    /** Les dégâts des ennemis augmentent au fil de l'album. */
    damage(e) {
        return Math.round(e.cfg.damage * 0.75 * (1 + this.state.levelIndex * 0.03));
    }

    /** Écarte un ennemi de ses voisins pour qu'ils ne se superposent pas. */
    separate(e) {
        const { floor } = this.game.data;
        for (const other of this.state.enemies) {
            if (other === e || other.isDown) continue;
            if (Math.abs(other.x - e.x) < 12 && Math.abs(other.y - e.y) < 5) {
                e.y += e.y < other.y ? -0.4 : 0.4;
                e.x += e.x < other.x ? -0.3 : 0.3;
            }
        }
        e.y = clamp(e.y, floor.min, floor.max);
    }

    #approach(e, rank) {
        const { state } = this;
        const p = state.player;
        const W = this.game.data.width;

        e.cooldown--;
        if (p.isDown) {
            e.setState('idle');
            return;
        }

        const side = e.x < p.x ? -1 : 1;
        const far = Math.abs(p.x - e.x);
        const attackers = state.levelIndex >= 5 ? 3 : 2;

        if (!e.boss && e.cooldown <= 0 && Math.abs(p.y - e.y) < 6) {
            const move = this.#chooseMove(e, far, rank < attackers + 1);
            if (move === 'lunge') {
                e.setState('lunge-wind');
                e.landed = false;
                return;
            }
            if (move === 'throw') {
                e.setState('throw');
                return;
            }
        }

        // les purs lanceurs (hooligans) gardent leurs distances
        if (e.cfg.projectile && !e.boss) rank = Math.max(rank, attackers) + (far < 50 ? 2 : 0);

        const closeRange = (e.cfg.reach ?? 26) * e.scale - 6;
        const distance = rank < attackers ? closeRange : (e.cfg.projectile ? 100 : 56);
        // jamais posté hors de l'écran : il serait impossible à atteindre
        const targetX = clamp(p.x + side * distance, state.cam + 14, state.cam + W - 14);
        const targetY = p.y + (rank < attackers ? 0 : (rank % 2 ? -8 : 8));
        const dx = targetX - e.x;
        const dy = targetY - e.y;
        e.faceTowards(p.x);

        if (rank < attackers && Math.abs(dx) < 4 && Math.abs(p.y - e.y) < 5 && e.cooldown <= 0) {
            e.setState('attack');
            e.landed = false;
            return;
        }

        if (Math.abs(dx) > 1.5 || Math.abs(dy) > 1) {
            e.setState('walk');
            e.x += Math.sign(dx) * Math.min(e.cfg.speed, Math.abs(dx));
            e.y += Math.sign(dy) * Math.min(e.cfg.speed * 0.7, Math.abs(dy));
        } else {
            e.setState('idle');
        }
        this.separate(e);
    }

    /**
     * Attaque spéciale selon la distance et les attaques connues de l'ennemi (cfg.moves = poids).
     * Le poids de strike est la chance de continuer simplement à approcher.
     */
    #chooseMove(e, far, allowed) {
        const moves = e.cfg.moves ?? {};
        const options = [];
        if (allowed && moves.lunge && far > 34 && far < 80) options.push(['lunge', moves.lunge]);
        if ((moves.throw || e.cfg.projectile) && far > 50 && far < 170) options.push(['throw', moves.throw ?? 3]);
        if (!options.length) return null;

        const total = options.reduce((sum, [, weight]) => sum + weight, 0) + (moves.strike ?? 2);
        let roll = Math.random() * total;
        for (const [move, weight] of options) {
            roll -= weight;
            if (roll < 0) return move;
        }
        e.cooldown = 20; // pas cette fois
        return null;
    }

    #lyingDown(e) {
        e.t++;
        if (e.t < 24) {
            e.x += e.vx;
            e.vx *= 0.9;
        }
    }

    #reeling(e) {
        e.t++;
        e.x += e.vx;
        e.vx *= 0.8;
        if (e.t > (e.boss ? 10 : 18)) {
            e.setState('idle');
            e.cooldown = rand(20, 50);
        }
    }

    #striking(e) {
        const { state } = this;
        const p = state.player;
        const timing = e.attackTiming;
        e.t++;
        if (e.t >= timing.hitFrom && e.t <= timing.hitTo && !e.landed) {
            const reach = (p.x - e.x) * e.dir;
            if (reach > 2 && reach < (e.cfg.reach ?? 26) * e.scale && Math.abs(p.y - e.y) < 6 + e.scale * 2) {
                e.landed = this.game.combat.damagePlayer(this.damage(e), e.x);
            }
        }
        if (e.t >= timing.end) {
            e.setState('idle');
            e.cooldown = e.boss ? rand(40, 80) : rand(55, 120) * (1 - state.levelIndex * 0.02);
        }
    }

    /** Ruée : l'ennemi prend son élan (il clignote) puis fonce. */
    #lunging(e) {
        const p = this.state.player;
        e.t++;
        if (e.state === 'lunge-wind') {
            e.faceTowards(p.x);
            if (e.t > 14) {
                e.setState('lunge');
                this.game.sfx('lunge');
            }
            return;
        }
        e.x += e.dir * 3.2;
        if (!e.landed && Math.abs(p.x - e.x) < 12 && Math.abs(p.y - e.y) < 6) {
            e.landed = this.game.combat.damagePlayer(this.damage(e), e.x - e.dir * 10);
        }
        if (e.t > 18) {
            e.setState('idle');
            e.cooldown = rand(50, 110);
        }
        this.separate(e);
    }

    /** Lance un projectile vers le héros (boss, hooligans, armes lancées). */
    #throwing(e) {
        const { game } = this;
        const p = this.state.player;
        e.t++;
        e.faceTowards(p.x);
        if (e.t === 18) {
            const flight = Math.max(30, Math.abs(p.x - e.x)) / 2.4;
            const sprite = e.cfg.throws ?? e.cfg.projectile;
            this.state.projectiles.push({
                sprite, x: e.x + e.dir * 10 * e.scale, y: e.y, z: 26 * e.scale,
                vx: Math.sign(p.x - e.x) * 2.4, vy: (p.y - e.y) / flight, vz: 1.2, damage: this.damage(e),
                spin: Boolean(game.sprites.weaponIcons[sprite]) || sprite === 'bottle',
            });
            game.sfx('throw');
        }
        if (e.t > 32) {
            e.setState('idle');
            e.cooldown = e.cfg.every ?? 90;
        }
    }
}
