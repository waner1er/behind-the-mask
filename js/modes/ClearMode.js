import { Mode } from './Mode.js';

/** MISSION COMPLETE : les héros soufflent, puis le niveau bonus s'il y en a un, sinon le niveau suivant. */
export class ClearMode extends Mode {
    static DURATION = 260;

    update() {
        const { game, state } = this;
        state.players.forEach((p) => game.player.update(p));
        for (const e of state.enemies) e.t++;
        if (state.modeTimer <= ClearMode.DURATION) return;
        if (state.level.bonus) game.bonus.start();
        else game.campaign.nextLevel();
    }
}
