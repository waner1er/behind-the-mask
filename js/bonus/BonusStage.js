import { pad, pick, rand } from '../util/math.js';

/**
 * Un niveau de casse (clé « bonus » de config/levels.php), comme la voiture de Street Fighter II :
 * à Beer Church, tout le niveau, c'est ça. Les héros (ivres) ont un temps limité pour démolir
 * le conteneur à verre. Prime s'ils y arrivent, puis MISSION COMPLETE et niveau suivant.
 *
 * state.bonus : { cfg, x, y, hp, maxHp, time (images restantes), shake, over (images depuis la fin), won }
 */
export class BonusStage {
    /** Après la casse (ou la fin du temps), on souffle avant le niveau suivant. */
    static OUTRO = 200;

    /** Demi-largeur du conteneur au sol : on ne passe pas au travers. */
    static HALF_WIDTH = 26;

    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    start() {
        const { game, state } = this;
        const cfg = state.level.bonus;
        const { floor, width } = game.data;
        const y = Math.round((floor.min + floor.max) / 2);
        // cfg.name et state = 'idle' : le HUD affiche sa barre de vie comme celle d'un boss
        state.bonus = {
            cfg: { ...cfg, name: cfg.target }, state: 'idle', x: state.cam + Math.round(width * 0.66), y,
            hp: cfg.hp, maxHp: cfg.hp, time: cfg.time * 60, shake: 0, over: 0, won: false,
            bottles: [], nextHeckler: 6 * 60,
        };
        Object.assign(state, { enemies: [], projectiles: [], pickups: [], pows: [], boxes: [], boss: state.bonus, locked: true });
        state.players.forEach((p) => {
            Object.assign(p, { x: state.cam + 70 - p.slot * 22, y: y + (state.players.length > 1 ? (p.slot ? 6 : -6) : 0), dir: 1, vx: 0, z: 0 });
            p.drunk = true;
            p.hiccup = Math.round(rand(60, 120));
            p.setState('idle');
        });
        game.hud.message(`<span class="hud__warning">${cfg.title}</span><br><br>SMASH THE ${cfg.target}!`, 150);
        game.sfx('start');
        setTimeout(() => state.players[0] && game.shout(cfg.line, state.players[0].x, state.players[0].y - 56, '#ffb020'), 1200);
        game.setMode('bonus');
    }

    update() {
        const { state } = this;
        const bonus = state.bonus;
        bonus.shake = Math.max(0, bonus.shake - 1);
        this.#flyingBottles();
        if (bonus.hp > 0) state.players.forEach((p) => this.#block(p));
        if (bonus.over) {
            if (++bonus.over > BonusStage.OUTRO) this.#leave();
            return;
        }
        this.#hecklers();
        if (--bonus.time <= 0) this.#end(false);
    }

    /** Des ennemis débarquent par les bords pour embêter Pete (config : hecklers). */
    #hecklers() {
        const { game, state } = this;
        const bonus = state.bonus;
        const { every, max, first = [], pool } = bonus.cfg.hecklers;
        if (--bonus.nextHeckler > 0) return;
        bonus.nextHeckler = every * 60;
        const standing = state.enemies.filter((e) => !e.isDown).length;
        const { floor, width } = game.data;
        // la première fois, ceux de « first » (un de chaque côté), ensuite au hasard
        const group = bonus.firstDone ? [pick(pool), pick(pool)] : first;
        bonus.firstDone = true;
        group.slice(0, Math.max(0, max - standing)).forEach((type, i) => {
            const fromRight = i % 2 === 0;
            const enemy = game.spawn(type, fromRight ? state.cam + width + 16 : state.cam - 16, rand(floor.min, floor.max));
            enemy.dir = fromRight ? -1 : 1;
            state.enemies.push(enemy);
        });
    }

    /** Les bouteilles éjectées tournoient, retombent et éclatent en morceaux. */
    #flyingBottles() {
        const { game, state } = this;
        const bonus = state.bonus;
        for (const bottle of bonus.bottles) {
            bottle.t++;
            bottle.x += bottle.vx;
            bottle.y += bottle.vy;
            bottle.z += bottle.vz;
            bottle.vz -= 0.2;
            if (bottle.z > 0) continue;
            bottle.broken = true;
            this.#shards(bottle.x, bottle.y, 8, 1.2);
            game.sfx('glass');
        }
        bonus.bottles = bonus.bottles.filter((bottle) => !bottle.broken);
    }

    /** Le conteneur est-il à portée d'un coup du héros ? */
    inReach(p, minReach, maxReach) {
        const bonus = this.state.bonus;
        if (!bonus || bonus.hp <= 0) return false;
        const reach = (bonus.x - p.x) * p.dir;
        return reach >= minReach - 14 && reach <= maxReach + 6 && Math.abs(bonus.y - p.y) < 14;
    }

    /**
     * Un coup dans le conteneur : il tremble, perd du verre, et finit en miettes.
     * Il encaisse : chaque coup ne lui enlève qu'un point, quelle que soit l'attaque (il doit durer tout le morceau).
     */
    hit(p) {
        const { game, state } = this;
        const bonus = state.bonus;
        const damage = 1;
        bonus.hp = Math.max(0, bonus.hp - damage);
        bonus.shake = 8;
        state.score += 100 * damage;
        state.shake = 2;
        state.sparks.push({ x: bonus.x - p.dir * 14, y: bonus.y - rand(10, 34), t: 0 });
        this.#shards(bonus.x - p.dir * 16, bonus.y, 14, 1);
        // de temps en temps, une bouteille entière jaillit du conteneur
        if (Math.random() < 0.3) {
            bonus.bottles.push({
                x: bonus.x + rand(-10, 10), y: bonus.y + rand(-2, 4), z: rand(30, 44),
                vx: rand(-3.2, 3.2), vy: rand(-0.6, 0.8), vz: rand(2, 4), t: 0, sprite: pick(['bottle', 'beer']),
            });
        }
        game.sfx('glass');
        game.haptics.pulse('hit', p);
        if (bonus.hp === 0) this.#end(true);
    }

    /** Le héros bute contre le conteneur et reste du côté d'où il vient. */
    #block(p) {
        const { x, y } = this.state.bonus;
        const dx = p.x - x;
        if (Math.abs(dx) >= BonusStage.HALF_WIDTH || Math.abs(p.y - y) >= 9) return;
        p.x = x + Math.sign(dx || -1) * BonusStage.HALF_WIDTH;
    }

    #end(won) {
        const { game, state } = this;
        const bonus = state.bonus;
        bonus.over = 1;
        bonus.won = won;
        state.boss = null;
        // la fête est finie : les gêneurs détalent K.O.
        for (const e of state.enemies) if (!e.isDown) game.combat.killEnemy(e, e.x < bonus.x ? -1 : 1);
        if (!won) {
            game.hud.message(`TIME OVER<br><br><span class="hud__small">BONUS ${pad(0)}</span>`, BonusStage.OUTRO);
            game.sfx('empty');
            return;
        }
        const prize = bonus.cfg.score + Math.ceil(bonus.time / 60) * 100;
        state.score += prize;
        state.flash = 10;
        state.shake = 8;
        this.#shards(bonus.x, bonus.y, 90, 1.6);
        for (let i = 0; i < 8; i++) {
            bonus.bottles.push({ x: bonus.x + rand(-16, 16), y: bonus.y, z: rand(20, 50), vx: rand(-4, 4), vy: rand(-0.8, 0.8), vz: rand(2, 5), t: 0, sprite: pick(['bottle', 'beer']) });
        }
        game.sfx('explosion');
        game.sfx('clear');
        game.hud.message(`<span class="hud__warning">PERFECT!</span><br><br><span class="hud__small">BONUS ${pad(prize)}</span>`, BonusStage.OUTRO);
    }

    /** Éclats de verre vert, brun et blanc projetés tout autour (force = jusqu'où ils volent). */
    #shards(x, y, count, force) {
        const { state } = this;
        for (let i = 0; i < count; i++) {
            state.debris.push({
                x: x + rand(-12, 12), y: y + rand(-4, 6), z: rand(6, 44),
                vx: rand(-4, 4) * force, vy: rand(-1, 1.4) * force, vz: rand(0.8, 4.5) * force,
                size: pick([1, 1, 1, 2, 2, 3]), color: pick(['#3f8a3a', '#a8e0a0', '#f4f4f4', '#7a8a40', '#c08a1a', '#8a6538']), t: 0,
            });
        }
    }

    /** Les héros dessoûlent : MISSION COMPLETE. */
    #leave() {
        const { game, state } = this;
        for (const p of state.players) p.drunk = false;
        state.bonus = null;
        game.campaign.levelClear();
    }
}
