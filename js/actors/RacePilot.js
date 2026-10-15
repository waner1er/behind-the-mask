import { RACE } from '../config.js';

/**
 * Le « cerveau » de Pete pendant la descente, en mode démo : il pousse, frappe l'ennemi à côté de lui,
 * saute les obstacles d'un ollie, ramasse les bonus et revient au milieu de la route.
 */
export class RacePilot {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.input = game.input;
    }

    play(p) {
        const { game, state, input } = this;
        const { race } = game;
        input.held.add('ArrowUp');
        if (p.state !== 'idle' && p.state !== 'jump') return;

        const rider = state.race.riders.find((r) => !r.isDown && Math.abs(r.z - race.playerZ) < RACE.depthReach && Math.abs(r.x - p.x) < RACE.reach);
        if (rider && state.tick % 6 === 0) {
            input.pressed.add('Space');
            return;
        }
        if (this.#obstacleAhead(p) && p.z === 0) input.pressed.add('KeyB');

        const targetX = this.#pickupX() ?? race.centerX;
        if (Math.abs(targetX - p.x) > 6) input.held.add(targetX > p.x ? 'ArrowRight' : 'ArrowLeft');
    }

    /** Un obstacle dans sa trajectoire, à un ou deux segments : il est temps de sauter. */
    #obstacleAhead(p) {
        const { race } = this.game;
        const { road } = this.state.race;
        const index = Math.floor(race.playerZ / RACE.segment);
        for (let n = 1; n <= 2; n++) {
            for (const o of road.segments[index + n]?.obstacles ?? []) {
                if (!o.hit && Math.abs(race.centerX + o.x * race.halfRoad - p.x) < 34) return true;
            }
        }
        return false;
    }

    /** Le prochain bonus sur la route, s'il est assez proche pour qu'on aille le chercher. */
    #pickupX() {
        const { race } = this.game;
        const item = this.state.race.pickups.find((it) => !it.taken && it.at * RACE.segment > race.playerZ
            && it.at * RACE.segment - race.playerZ < 25 * RACE.segment);
        return item ? race.centerX + item.x * race.halfRoad : null;
    }
}
