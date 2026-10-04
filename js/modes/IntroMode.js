import { Mode } from './Mode.js';

/** Titre du niveau et premières paroles du morceau, 3,5 s ou jusqu'à START. */
export class IntroMode extends Mode {
    static DURATION = 210;

    update() {
        const { game, state, input } = this;
        state.player.anim++;
        const skipped = state.modeTimer > 30 && input.wasPressed('Enter', 'Space');
        if (state.modeTimer > IntroMode.DURATION || skipped) {
            game.hud.message('', 0);
            game.setMode('playing');
        }
    }
}
