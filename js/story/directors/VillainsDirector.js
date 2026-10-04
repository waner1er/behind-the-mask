import { Director } from './Director.js';

const TAUNTS = [[150, 'HÉ HÉ', 0], [190, 'ALPHA !', 1], [230, 'PROFIT !', 2], [330, 'SALE PUNK', 0]];

/** Intro : les méchants entrent un par un en roulant des mécaniques. */
export class VillainsDirector extends Director {
    enter() {
        const W = this.game.data.width;
        this.state.cast = [];
        this.cast.add('hero', 70, 160, { scale: 1.5, dir: 1 });
        // ils attendent hors champ leur moment (delay) pour entrer jusqu'à leur place (waitX)
        this.cast.add('skinhead', W + 20, 152, { waitX: 185, face: -1, scale: 1.5, delay: 20 });
        this.cast.add('masculinist', W + 40, 166, { waitX: 228, face: -1, scale: 1.5, delay: 60 });
        this.cast.add('manager', W + 60, 156, { waitX: 272, face: -1, scale: 1.5, delay: 100 });
    }

    update() {
        const [pete, ...villains] = this.state.cast;
        villains.forEach((v) => {
            if (this.t === v.delay) v.targetX = v.waitX;
        });
        for (const [at, text, i] of TAUNTS) {
            if (this.t === at) this.game.shout(text, villains[i].x, villains[i].y - 70, '#ffffff');
        }
        if (this.t === 280) {
            this.game.shout('...', pete.x, pete.y - 70, '#ffffff');
            pete.setState('attack');
        }
    }
}
