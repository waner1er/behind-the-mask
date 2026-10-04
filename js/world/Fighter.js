import { ATTACK, VINYL } from '../config.js';
import { rand } from '../util/math.js';

let nextId = 1;

/**
 * Un personnage qui se bat : le héros, un ennemi, un boss, un acteur des scènes animées.
 *
 * state : idle | walk | attack | hurt | dead | jump | skate | vinyl (héros)
 *         lunge-wind | lunge | throw (ennemis), charge-wind | charge | vanish | summon (boss)
 * t : images écoulées dans l'état courant ; anim : compteur d'animation.
 */
export class Fighter {
    constructor(type, x, y, cfg) {
        this.id = nextId++;
        this.type = type;
        this.cfg = cfg;
        this.x = x;
        this.y = y;
        this.z = 0;
        this.vx = 0;
        this.vz = 0;
        this.dir = 1;
        this.scale = cfg.scale ?? 1;

        this.state = 'idle';
        this.t = 0;
        this.anim = 0;

        this.hp = cfg.hp;
        this.maxHp = cfg.hp;
        this.invuln = 0;
        this.boss = false;
        this.hits = new Set();
        this.landed = false;
        this.cooldown = rand(30, 90);
        this.special = cfg.every ?? 0;

        // héros
        this.skateCooldown = 0;
        this.weapon = null;
        this.weaponUntil = 0;
        this.wods = 0;
        this.vinyls = type === 'hero' ? VINYL.start : 0;
    }

    setState(name) {
        if (this.state === name) return;
        this.state = name;
        this.t = 0;
    }

    get attackTiming() {
        if (this.type === 'hero') return ATTACK.hero;
        return this.boss ? ATTACK.boss : ATTACK.enemy;
    }

    get isDown() {
        return this.state === 'dead';
    }

    /** Hors combat : au tapis, ou disparu (téléportation du Nightmare). */
    get isUntouchable() {
        return this.state === 'dead' || this.state === 'vanish';
    }

    faceTowards(x) {
        this.dir = x > this.x ? 1 : -1;
    }
}
