import { STATIC } from '../config.js';
import { clamp, pick, rand } from '../util/math.js';

/** Les attaques spéciales des boss (config/levels.php) : charge, lancer, téléportation, appel de sbires, neige télé. */
export class BossAI {
    static START = { charge: 'charge-wind', throw: 'throw', teleport: 'vanish', summon: 'summon', static: 'static-wind' };

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
        }
        return this.#trigger(e);
    }

    #trigger(e) {
        const p = this.state.nearestPlayer(e.x, e.y);
        e.special--;
        if (e.special > 0 || p.isDown) return false;
        e.special = e.cfg.every;

        e.setState(BossAI.START[e.cfg.special]);
        if (e.cfg.special === 'static') this.game.shout(e.cfg.staticLine ?? e.cfg.line, e.x, e.y - 50 * e.scale, '#ffffff');
        else if (e.cfg.special === 'summon' || Math.random() < 0.3) this.game.shout(e.cfg.line, e.x, e.y - 50 * e.scale, '#ffffff');
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
}
