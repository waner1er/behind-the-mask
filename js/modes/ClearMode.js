import { Mode } from './Mode.js';

/** MISSION COMPLETE : le héros souffle, puis niveau suivant. */
export class ClearMode extends Mode {
    static DURATION = 260;

    update() {
        const { game, state } = this;
        game.player.update(state.player);
        for (const e of state.enemies) e.t++;
        if (state.modeTimer > ClearMode.DURATION) game.campaign.nextLevel();
    }
}
