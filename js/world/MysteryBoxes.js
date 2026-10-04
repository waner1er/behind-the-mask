import { rand } from '../util/math.js';

/** La caisse « ? » du niveau : deux coups pour la casser, elle libère le Wall of Death. */
export class MysteryBoxes {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    place(level, floor) {
        this.state.boxes = [{ x: level.wodBox.x, y: floor.min + level.wodBox.y, hp: 2, shake: 0 }];
    }

    hit(box) {
        const { game, state } = this;
        box.hp--;
        box.shake = 8;
        state.sparks.push({ x: box.x, y: box.y - 8, t: 0 });
        game.sfx('metal');
        if (box.hp > 0) return;

        for (let i = 0; i < 5; i++) state.sparks.push({ x: box.x + rand(-8, 8), y: box.y - rand(2, 14), t: -i });
        game.sfx('explosion');
        game.pickups.spawn('wod', box.x, box.y + 4, { fly: true });
        game.shout('?!', box.x, box.y - 24, '#ffd23f');
    }
}
