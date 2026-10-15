import { PlayingMode } from './PlayingMode.js';

/**
 * Un niveau de casse (voir js/bonus/BonusStage.js) : la partie tourne comme d'habitude
 * (héros, ennemis qui viennent embêter, projectiles, bonus), plus le conteneur et le chrono.
 */
export class BonusMode extends PlayingMode {
    update() {
        super.update();
        this.game.bonus.update();
    }
}
