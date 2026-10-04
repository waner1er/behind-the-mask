import { pick, rand } from '../../util/math.js';
import { Director } from './Director.js';

/** Intro : le Docteur Mask ricane devant ses serveurs, l'argent et l'IA s'envolent. */
export class MaskDirector extends Director {
    enter() {
        this.setStage(8);
        this.cast.add('mask', 230, 165, { scale: 2.5, dir: -1 });
    }

    update() {
        const mask = this.state.cast[0];
        if (this.t % 100 === 30) {
            this.game.shout('HA HA HA !', mask.x - 20, mask.y - 120, '#e8203a');
            mask.setState('attack');
            mask.t = 0;
        }
        if (this.t % 12 === 0) {
            this.state.fx.push({
                text: pick(['$', 'AI', '€', '$$', '01']), x: rand(20, 180), y: rand(70, 140), t: 0,
                color: pick(['#ffd23f', '#3ef0ff', '#7dff5a']),
            });
        }
    }
}
