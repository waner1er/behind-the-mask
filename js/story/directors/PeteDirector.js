import { Director } from './Director.js';

/** Intro : Pete arrive en marchant, salue et enchaîne les katas. */
export class PeteDirector extends Director {
    enter() {
        this.setStage(0);
        this.state.level = this.game.data.levels[0];
        this.cast.add('hero', -30, 160, { targetX: 150, face: 1, scale: 2, speed: 1.4 });
    }

    update() {
        const pete = this.state.cast[0];
        if (this.t === 140) this.game.shout('OSS !', pete.x, pete.y - 90, '#ffffff');
        if (this.t > 160 && this.t % 110 === 0) {
            pete.setState('attack');
            this.game.sfx('whoosh');
        }
    }
}
