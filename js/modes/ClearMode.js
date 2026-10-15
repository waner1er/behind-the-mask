import { Mode } from './Mode.js';

/** MISSION COMPLETE : les héros soufflent, puis niveau suivant. */
export class ClearMode extends Mode {
    static DURATION = 260;

    update() {
        const { game, state } = this;
        state.players.forEach((p) => game.player.update(p));
        for (const e of state.enemies) e.t++;
        if (state.modeTimer > ClearMode.DURATION) game.campaign.nextLevel();
    }
}
