import { ControlsHelp } from '../ui/ControlsHelp.js';

/**
 * Pause pendant une partie : START fige tout (jeu, musique, bruitages), START relance.
 * L'écran de pause rappelle les commandes (ControlsHelp).
 * La partie se met aussi en pause toute seule quand on quitte l'onglet ou l'app (appel, notification…).
 */
export class Pause {
    constructor(game) {
        this.game = game;
        this.paused = false;
        /** Le message du HUD recouvert par « PAUSE » et sa fin, rendus à la reprise (vague, boss…). */
        this.hidden = { html: '', until: 0 };
        this.help = new ControlsHelp(game);
    }

    bind(root = document) {
        root.addEventListener('visibilitychange', () => {
            if (root.visibilityState === 'hidden' && this.#canPause()) this.set(true);
        });
    }

    /** À appeler en début d'image ; renvoie true tant que la partie est figée. */
    update() {
        if (this.game.input.wasPressed('Enter') && (this.paused || this.#canPause())) this.set(!this.paused);
        return this.paused;
    }

    set(paused) {
        const { hud, audio } = this.game;
        if (paused === this.paused) return;
        this.paused = paused;
        audio.setPaused(paused);
        hud.setLayout('paused', paused);
        if (paused) {
            this.hidden = { html: hud.el.message.innerHTML, until: this.game.state.messageUntil };
            hud.message(`<span class="hud__pause">PAUSE</span>${this.help.html()}`);
        } else {
            // le temps du jeu (state.tick) n'a pas avancé : le message reprend là où il en était
            hud.el.message.innerHTML = this.hidden.html;
            this.game.state.messageUntil = this.hidden.until;
        }
    }

    #canPause() {
        const { state } = this.game;
        return state.mode === 'playing' && !state.demo;
    }
}
