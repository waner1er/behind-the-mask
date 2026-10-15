import { Mode } from './Mode.js';

/** Titre du niveau et premières paroles du morceau, 3,5 s ou jusqu'à START. */
export class IntroMode extends Mode {
    static DURATION = 210;

    update() {
        const { game, state, input } = this;
        state.players.forEach((p) => p.anim++);
        const skipped = state.modeTimer > 30 && input.wasPressed('Enter', 'Space');
        if (state.modeTimer > IntroMode.DURATION || skipped) {
            game.hud.message('', 0);
            if (state.level.bonus) game.bonus.start(); // Beer Church : tout le niveau, c'est la casse du conteneur
            else game.setMode(state.race ? 'race' : 'playing');
        }
    }
}
