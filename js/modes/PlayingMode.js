import { Mode } from './Mode.js';

/** La partie : le héros, les ennemis (les boss et les plus proches d'abord), les objets, la caméra. */
export class PlayingMode extends Mode {
    /** Un ennemi K.O. disparaît au bout de 100 images. */
    static CORPSE_TIME = 100;

    update() {
        const { game, state } = this;
        const p = state.player;
        game.player.update(p);

        const queue = state.enemies
            .filter((e) => !e.isDown)
            .sort((a, b) => (b.boss - a.boss) || Math.abs(a.x - p.x) - Math.abs(b.x - p.x));
        for (const e of state.enemies) game.enemyAI.update(e, queue.indexOf(e));
        state.enemies = state.enemies.filter((e) => !(e.isDown && e.t > PlayingMode.CORPSE_TIME));

        game.projectiles.update();
        game.pickups.update();
        game.hostages.update();
        game.wallOfDeath.update();

        if (state.mode === 'playing') game.camera.update();
    }
}
