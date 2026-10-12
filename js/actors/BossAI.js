import { COMPONENTS, DOOR, STATIC } from '../config.js';
import { clamp, pick, rand } from '../util/math.js';

/**
 * Les attaques spéciales des boss (config/levels.php) : charge, lancer, téléportation, appel de sbires,
 * neige télé, salve de composants ; plusieurs attaques se jouent en alternance. Et l'entrée par une porte.
 */
export class BossAI {
    static START = { charge: 'charge-wind', throw: 'throw', teleport: 'vanish', summon: 'summon', static: 'static-wind', components: 'spew' };

    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /** Renvoie true si le boss est occupé par son attaque spéciale. */
    update(e) {
        switch (e.state) {
            case 'charge-wind': return this.#windUp(e);
            case 'charge': return this.#charge(e);
            case 'vanish': return this.#teleport(e);
            case 'summon': return this.#summon(e);
            case 'static-wind':
            case 'static': return this.#static(e);
            case 'spew': return this.#spew(e);
            case 'emerge': return this.#emerge(e);
        }
        return this.#trigger(e);
    }

    #trigger(e) {
        const p = this.state.nearestPlayer(e.x, e.y);
        e.special--;
        if (e.special > 0 || p.isDown) return false;
        e.special = e.cfg.every;

        const specials = [e.cfg.special].flat();
        const special = specials[(e.specialCount = (e.specialCount ?? -1) + 1) % specials.length];
        e.setState(BossAI.START[special]);
        if (special === 'static') this.game.shout(e.cfg.staticLine ?? e.cfg.line, e.x, e.y - 50 * e.scale, '#ffffff');
        else if (special === 'summon' || Math.random() < 0.3) this.game.shout(e.cfg.line, e.x, e.y - 50 * e.scale, '#ffffff');
        return true;
    }

    #windUp(e) {
        e.t++;
        e.faceTowards(this.state.nearestPlayer(e.x, e.y).x);
        if (e.t > 34) {
            e.setState('charge');
            e.landed = false;
            this.game.sfx('skate');
        }
        return true;
    }

    /** Fonce à travers l'écran jusqu'au bord. */
    #charge(e) {
        const { state } = this;
        const W = this.game.data.width;
        e.t++;
        e.x += e.dir * 3.4;
        // la charge renverse tous ceux qu'elle traverse
        for (const p of state.players) {
            if (Math.abs(p.x - e.x) < 14 * e.scale && Math.abs(p.y - e.y) < 6 + e.scale * 3) {
                this.game.combat.damagePlayer(p, Math.round(e.cfg.damage * 1.5), e.x - e.dir * 10);
            }
        }
        const atEdge = e.x < state.cam + 12 || e.x > state.cam + W - 12;
        if (e.t > 80 || atEdge) {
            e.x = clamp(e.x, state.cam + 12, state.cam + W - 12);
            e.setState('idle');
        }
        return true;
    }

    /** Disparaît et réapparaît dans le dos du héros le plus proche, prêt à frapper. */
    #teleport(e) {
        const { state } = this;
        const p = state.nearestPlayer(e.x, e.y);
        e.t++;
        if (e.t === 26) {
            e.x = clamp(p.x - p.dir * 24, state.cam + 12, state.cam + this.game.data.width - 12);
            e.y = p.y;
            e.faceTowards(p.x);
            e.setState('attack');
            e.landed = false;
            this.game.sfx('teleport');
        }
        return true;
    }

    /** Appelle jusqu'à deux sbires, sans dépasser quatre à l'écran. */
    #summon(e) {
        const { game, state } = this;
        const { width: W, floor } = game.data;
        e.t++;
        if (e.t === 20) {
            const minions = state.enemies.filter((m) => !m.boss && !m.isDown).length;
            for (let i = 0; i < Math.min(2, 4 - minions); i++) {
                const fromRight = i === 0;
                const x = fromRight ? state.cam + W + 16 : state.cam - 16;
                const minion = game.spawn(pick(['skinhead', 'masculinist']), x, rand(floor.min, floor.max));
                minion.dir = fromRight ? -1 : 1;
                state.enemies.push(minion);
            }
        }
        if (e.t > 40) e.setState('idle');
        return true;
    }

    /**
     * L'écran grésille (il clignote, on a le temps de s'écarter ou de sauter), puis une onde
     * de neige part en anneau : le héros qu'elle traverse reste étourdi.
     */
    #static(e) {
        const { game, state } = this;
        e.t++;
        if (e.state === 'static-wind') {
            if (e.t > STATIC.wind) {
                e.setState('static');
                e.hits.clear();
                state.statics.push({ x: e.x, y: e.y, t: 0 });
                game.sfx('static');
                state.shake = 3;
            }
            return true;
        }

        const r = e.t * STATIC.speed;
        for (const p of state.players) {
            // l'anneau est aplati par la perspective : la profondeur compte triple
            const distance = Math.hypot(p.x - e.x, (p.y - e.y) * 3);
            if (e.hits.has(p) || Math.abs(distance - r) > STATIC.speed * 2) continue;
            e.hits.add(p);
            if (game.combat.damagePlayer(p, game.enemyAI.damage(e), e.x)) {
                p.t = -STATIC.stun; // il titube plus longtemps qu'un coup normal
                game.shout('ZZZT!', p.x, p.y - 40, '#ffffff');
            }
        }
        if (r >= STATIC.reach) e.setState('idle');
        return true;
    }

    /** Crache trois composants en éventail autour du héros ; ils explosent en retombant (Projectiles). */
    #spew(e) {
        const { game, state } = this;
        const p = state.nearestPlayer(e.x, e.y);
        e.t++;
        e.faceTowards(p.x);
        const shot = e.t / COMPONENTS.every - 1;
        if (Number.isInteger(shot) && shot >= 0 && shot < COMPONENTS.sprites.length) {
            // visée en cloche : il retombe pile sur sa cible au bout de « flight » images
            const z = 26 * e.scale;
            const T = COMPONENTS.flight;
            const tx = p.x + (shot - 1) * COMPONENTS.spread;
            state.projectiles.push({
                sprite: COMPONENTS.sprites[shot], x: e.x + e.dir * 8 * e.scale, y: e.y, z,
                vx: (tx - e.x) / T, vy: (p.y - e.y) / T, vz: (0.08 * T) / 2 - z / T,
                damage: game.enemyAI.damage(e), spin: true, pling: true,
            });
            game.sfx('throw');
        }
        if (e.t > COMPONENTS.every * (COMPONENTS.sprites.length + 1)) e.setState('idle');
        return true;
    }

    /** Entrée par la porte : il cogne (elle tremble), la porte vole en éclats, il vient vers nous. */
    #emerge(e) {
        const { game, state } = this;
        const { door } = state;
        const { floor } = game.data;
        e.t++;
        if (e.t < DOOR.knocks) {
            if (e.t % DOOR.knockEvery === 0) {
                door.shake = 8;
                state.shake = 2;
                game.sfx('heavyHit');
            }
            return true;
        }
        if (e.t === DOOR.knocks) {
            door.broken = true;
            state.shake = 7;
            game.sfx('heavyHit');
            game.sfx('glass');
            for (let i = 0; i < 18; i++) {
                state.debris.push({
                    x: door.x + rand(-12, 12), y: door.y + rand(0, 6), z: rand(4, 50),
                    vx: rand(-2, 2), vy: rand(0.2, 1.4), vz: rand(0.5, 2.5),
                    size: pick([1, 2, 2, 3]), color: pick(['#c79a62', '#8a6538', '#2e2c28']), t: 0,
                });
            }
        }
        const p = state.nearestPlayer(e.x, e.y);
        e.faceTowards(p.x);
        const target = (floor.min + floor.max) / 2;
        e.y = Math.min(target, e.y + DOOR.walk);
        if (e.y >= target) {
            e.setState('idle');
            game.shout(e.cfg.line, e.x - 40, e.y - 50 * e.scale, '#ffffff');
        }
        return true;
    }
}
