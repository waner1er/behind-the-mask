import { Mode } from './Mode.js';

/** La descente en skate (voir js/race/). */
export class RaceMode extends Mode {
    update() {
        this.game.race.update();
    }
}
