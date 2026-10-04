import { pad } from '../../util/math.js';
import { Director } from './Director.js';

/** Fin : le générique débile qui défile sur la ville libérée. */
export class CreditsDirector extends Director {
    /** En démo, retour à l'accueil à la fin du générique. */
    static DEMO_DURATION = 3300;

    constructor(game, peace) {
        super(game);
        this.peace = peace;
    }

    enter() {
        const { data } = this.game;
        const lines = data.story.credits.map(([name, job]) => (
            `<div class="hud__credit"><span class="hud__credit-name">${name}</span><span class="hud__credit-job">${job}</span></div>`
        )).join('');
        const links = data.links.map((l) => `<a href="${l.url}" target="_blank" rel="noopener">${l.name}</a>`).join(' · ');
        this.game.hud.showCredits(`<div class="hud__credits-roll">${lines}`
            + '<div class="hud__credit hud__credit--end"><span class="hud__track">THANKS FOR PLAYING</span>'
            + `<span class="hud__links">${links}</span><span class="hud__small">SCORE ${pad(this.state.score)}</span>`
            + '<span class="blink-text">PRESS START</span></div></div>');
    }

    update() {
        const { game } = this;
        this.peace.update();
        if (this.state.demo && this.t > CreditsDirector.DEMO_DURATION) game.demo.exit();
        if (this.t > 120 && game.input.wasPressed('Enter', 'Space')) game.goHome();
    }
}
