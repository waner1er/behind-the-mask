import { PICKUP_LIFETIME, VINYL } from '../config.js';
import { clamp, pick } from '../util/math.js';

/**
 * Les bonus au sol : beer (+30), life (+50 % de vie), vinyls (munitions), weapon (arme ramassée),
 * wod (un Wall of Death en réserve).
 */
export class Pickups {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.floor = game.data.floor;
    }

    /** Pose un bonus ; avec fly, il est d'abord projeté en l'air en tournoyant. */
    spawn(kind, x, y, { fly = false, weapon = null, dir = pick([-1, 1]) } = {}) {
        this.state.pickups.push({
            kind, weapon, x, y: clamp(y, this.floor.min, this.floor.max), t: 0,
            z: fly ? 14 : 0, vz: fly ? 2.6 : 0, vx: fly ? dir * 1.3 : 0,
        });
    }

    update() {
        this.state.pickups = this.state.pickups.filter((item) => {
            item.t++;
            if (item.z > 0 || item.vz > 0) {
                item.x += item.vx;
                item.z += item.vz;
                item.vz -= 0.18;
                if (item.z <= 0) Object.assign(item, { z: 0, vz: 0, vx: 0 });
            }
            return item.t < (PICKUP_LIFETIME[item.kind] ?? PICKUP_LIFETIME.default);
        });
    }

    /** Le héros ramasse ce qui est à ses pieds. */
    collect(p) {
        this.state.pickups = this.state.pickups.filter((item) => {
            if (item.z !== 0 || Math.abs(item.x - p.x) >= 10 || Math.abs(item.y - p.y) >= 6) return true;
            this.#apply(item, p);
            this.game.sfx('pickup');
            return false;
        });
    }

    #apply(item, p) {
        const shout = (text, color) => this.game.shout(text, p.x, p.y - 48, color);
        switch (item.kind) {
            case 'wod':
                p.wods++;
                shout('WALL OF DEATH ! (ESPACE + V)', '#e8203a');
                break;
            case 'life':
                p.hp = Math.min(p.maxHp, p.hp + Math.round(p.maxHp / 2));
                shout('+50% VIE !', '#ff3ea5');
                break;
            case 'vinyls':
                p.vinyls = Math.min(VINYL.max, p.vinyls + VINYL.perCrate);
                shout(`VINYL x${VINYL.perCrate}!`, '#ffd23f');
                break;
            case 'weapon':
                p.weapon = item.weapon;
                p.weaponUntil = this.state.tick + this.game.data.weaponDuration;
                shout(`${this.game.data.weapons[item.weapon].name}!`, '#ffd23f');
                break;
            default:
                p.hp = Math.min(p.maxHp, p.hp + 30);
                shout('+30', '#ffd23f');
        }
    }
}
