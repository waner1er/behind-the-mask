import { Mode } from './Mode.js';

/** Écran d'accueil : JOUER (avec le scénario), MORCEAUX (choix du niveau) ou DÉMO. */
export class TitleMode extends Mode {
    static MENU = ['JOUER', 'MORCEAUX', 'DÉMO'];

    update() {
        const { game, state, input } = this;
        const size = TitleMode.MENU.length;
        this.walkInPlace();
        if (state.modeTimer === 1) this.show();

        if (input.wasPressed('ArrowUp', 'ArrowDown')) {
            state.menu = (state.menu + (input.wasPressed('ArrowUp') ? size - 1 : 1)) % size;
            this.show();
            game.sfx('select');
        }
        if (input.wasPressed('Enter', 'Space')) this.#choose();
    }

    show() {
        const { game, state } = this;
        const items = TitleMode.MENU.map((label, i) => (i === state.menu
            ? `<span class="hud__menu-item is-active">▶ ${label}</span>`
            : `<span class="hud__menu-item">${label}</span>`)).join('');
        game.hud.setLayout('home', true);
        game.hud.message(
            `<img class="hud__logo" src="${game.data.logo}" alt="Vigilante - Behind the Mask">`
            + `<div class="hud__menu">${items}</div>`
            + '<span class="blink-text hud__small">INSERT COIN · PRESS START</span>',
        );
        game.hud.setLevelLabel('HI-SCORE');
    }

    #choose() {
        const { game, state } = this;
        state.resetScore();
        game.sfx('start');
        game.hud.setLayout('home', false);
        switch (TitleMode.MENU[state.menu]) {
            case 'DÉMO':
                game.demo.start();
                break;
            case 'JOUER':
                game.campaign.playIntro();
                break;
            default:
                game.setMode('select');
                game.modes.select.show();
                game.audio.playTrack(game.data.levels[state.selected].audio);
        }
    }
}
