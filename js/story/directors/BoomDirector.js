import { rand } from '../../util/math.js';
import { Director } from './Director.js';

/** Fin : le Docteur Mask tremble et explose en chaîne. */
export class BoomDirector extends Director {
    static BLOW_UP_AT = 200;

    enter() {
        const { boss } = this.state;
        this.cast.add('mask', boss ? boss.x : this.state.cam + 220, boss ? boss.y : 160, { scale: 3, dir: -1 });
        this.game.audio.stopMusic();
    }

    update() {
        const { state } = this;
        const mask = state.cast[0];
        if (!mask) return; // il a explosé

        mask.x += Math.sin(this.t) * 1.2;
        if (this.t % 6 === 0) mask.setState(mask.state === 'hurt' ? 'idle' : 'hurt');
        if (this.t % 7 === 0 && this.t < BoomDirector.BLOW_UP_AT) {
            state.explosions.push({ x: mask.x + rand(-30, 30), y: mask.y - rand(0, 60), t: 0 });
            state.shake = 6;
            if (this.t % 21 === 0) this.game.sfx('explosion');
        }
        if (this.t === BoomDirector.BLOW_UP_AT) {
            state.flash = 30;
            state.cast = [];
            this.game.sfx('bossDeath', 3);
        }
    }
}
