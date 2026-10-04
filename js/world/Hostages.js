import { pick } from '../util/math.js';

/** Les otages ligotés (comme les prisonniers de Metal Slug) : un coup les libère, ils lâchent un bonus. */
export class Hostages {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    place(level, floor) {
        this.state.pows = level.pows.map((x) => ({ x, y: floor.min + 1, freed: false, t: 0 }));
    }

    free(pow) {
        const { game, state } = this;
        pow.freed = true;
        pow.t = 0;
        state.score += 1000;
        game.shout('THANK YOU!', pow.x, pow.y - 30, '#7dff5a');
        game.sfx('freed');

        const at = [pow.x, pow.y + 6];
        const roll = Math.random();
        if (roll < 0.5) game.pickups.spawn('vinyls', ...at, { fly: true });
        else if (roll < 0.75) game.pickups.spawn('beer', ...at, { fly: true });
        else game.pickups.spawn('weapon', ...at, { fly: true, weapon: pick(['bat', 'chain', 'knife', 'dumbbell']) });
    }

    update() {
        const { state } = this;
        const width = this.game.data.width;
        for (const pow of state.pows) {
            pow.t++;
            if (pow.freed) {
                pow.x -= 2.2; // il détale vers la gauche
            } else if (pow.t % 240 === 60 && pow.x > state.cam && pow.x < state.cam + width) {
                this.game.shout('HELP!', pow.x, pow.y - 26, '#ffffff');
            }
        }
        state.pows = state.pows.filter((pow) => !pow.freed || pow.t < 150);
    }
}
