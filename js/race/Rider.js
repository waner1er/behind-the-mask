import { rand } from '../util/math.js';

let nextId = 1;

/**
 * Un ennemi de la descente (config/game.php → riders), vu de dos.
 *
 * x : position en travers (pixels d'écran à la profondeur de Pete) ; z : distance parcourue.
 * state : ride | wind (il arme son coup) | strike | ram (BMX qui se jette sur le côté) | hurt | crash
 */
export class Rider {
    constructor(type, cfg, x, z, speed) {
        this.id = nextId++;
        this.type = type;
        this.cfg = cfg;
        this.x = x;
        this.z = z;
        this.vx = 0;
        this.speed = speed;
        this.hp = cfg.hp;
        this.maxHp = cfg.hp;
        this.dir = 1;
        this.state = 'ride';
        this.t = 0;
        this.anim = Math.floor(rand(0, 60));
        this.cooldown = cfg.every;
        this.engaged = 0;
        /** Où il se cale par rapport à Pete, en profondeur : un peu devant ou un peu derrière. */
        this.offset = rand(-60, 90);
        this.spin = 0;
    }

    setState(name) {
        if (this.state === name) return;
        this.state = name;
        this.t = 0;
    }

    /** Hors course : il a chuté. */
    get isDown() {
        return this.state === 'crash';
    }
}
