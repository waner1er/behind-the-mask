import { Mode } from './Mode.js';

/** Séquence animée (intro ou fin). */
export class StoryMode extends Mode {
    update() {
        this.game.story.update();
    }
}
