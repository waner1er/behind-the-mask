import { Mode } from './Mode.js';

/** Le niveau bonus (voir js/bonus/BonusStage.js) : les héros frappent le conteneur, le chrono tourne. */
export class BonusMode extends Mode {
    update() {
        const { game, state } = this;
        state.players.forEach((p) => game.player.update(p));
        game.bonus.update();
    }
}
