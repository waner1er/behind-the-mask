import { pad } from '../util/math.js';
import { Mode } from './Mode.js';

/** Choix du morceau : ◀ ▶ pour changer (décor et musique suivent), START pour jouer, ▲ pour revenir. */
export class SelectMode extends Mode {
    update() {
        const { game, input } = this;
        this.walkInPlace();
        if (input.wasPressed('ArrowRight')) this.#select(1);
        if (input.wasPressed('ArrowLeft')) this.#select(-1);
        if (input.wasPressed('ArrowUp')) game.goHome();
        else if (input.wasPressed('Enter', 'Space')) game.campaign.startLevel(this.state.selected);
    }

    show() {
        const level = this.game.data.levels[this.state.selected];
        this.game.hud.message(
            '<span class="hud__small">SELECT TRACK</span><br>'
            + `<span class="hud__track">◀ ${pad(level.number, 2)} ${level.title} ▶</span>`
            + (level.feat ? `<br><span class="hud__small">FEAT. ${level.feat}</span>` : '')
            + '<br><br><span class="blink-text">PRESS START</span><br><span class="hud__small">▲ RETOUR</span>',
        );
    }

    #select(delta) {
        const { game, state } = this;
        const { levels } = game.data;
        state.selected = (state.selected + delta + levels.length) % levels.length;
        this.show();
        game.backdrop.load(state.selected);
        game.audio.playTrack(levels[state.selected].audio);
        game.sfx('select');
    }
}
