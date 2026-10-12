import { ControlsHelp } from '../ui/ControlsHelp.js';

/**
 * Pause pendant une partie : START fige tout (jeu, musique, bruitages) et ouvre un petit menu —
 * reprendre (choix par défaut : START, START relance), recommencer le niveau, ou revenir à l'accueil.
 * L'écran de pause rappelle aussi les commandes (ControlsHelp).
 * La partie se met en pause toute seule quand on quitte l'onglet ou l'app (appel, notification…).
 */
export class Pause {
    static MENU = ['REPRENDRE', 'RECOMMENCER LE NIVEAU', "RETOUR A L'ACCUEIL"];

    constructor(game) {
        this.game = game;
        this.paused = false;
        this.choice = 0;
        /** Le message du HUD recouvert par la pause et sa fin, rendus à la reprise (vague, boss…). */
        this.hidden = { html: '', until: 0 };
        this.help = new ControlsHelp(game);
    }

    bind(root = document) {
        root.addEventListener('visibilitychange', () => {
            if (root.visibilityState === 'hidden' && this.#canPause()) this.set(true);
        });
    }

    /** À appeler en début d'image ; renvoie true tant que la partie est figée (et l'image où elle repart). */
    update() {
        if (!this.paused) {
            if (this.game.input.wasPressed('Enter') && this.#canPause()) this.set(true);
            return this.paused;
        }
        // à deux, les flèches et les boutons de chaque joueur vont à son propre pavé : on écoute tout le monde
        const pressed = (...codes) => [this.game.input, ...this.game.pads].some((input) => input.wasPressed(...codes));
        if (pressed('ArrowUp', 'ArrowDown')) {
            const size = Pause.MENU.length;
            this.choice = (this.choice + (pressed('ArrowUp') ? size - 1 : 1)) % size;
            this.game.sfx('select');
            this.#show();
        }
        if (pressed('Enter', 'Space')) this.#choose();
        return true; // l'appui qui ferme le menu ne doit pas aussi frapper
    }

    set(paused) {
        const { game } = this;
        const { hud, audio, state } = game;
        if (paused === this.paused) return;
        this.paused = paused;
        audio.setPaused(paused);
        hud.setLayout('paused', paused);
        if (paused) {
            this.choice = 0;
            this.hidden = { html: hud.el.message.innerHTML, until: state.messageUntil };
            this.#show();
        } else {
            // le temps du jeu (state.tick) n'a pas avancé : le message reprend là où il en était
            hud.el.message.innerHTML = this.hidden.html;
            state.messageUntil = this.hidden.until;
        }
    }

    #show() {
        const items = Pause.MENU.map((label, i) => (i === this.choice
            ? `<span class="hud__menu-item is-active">▶ ${label}</span>`
            : `<span class="hud__menu-item">${label}</span>`)).join('');
        this.game.hud.message(`<span class="hud__pause">PAUSE</span><span class="hud__menu">${items}</span>${this.help.html()}`);
    }

    #choose() {
        const { game } = this;
        const { state } = game;
        this.set(false);
        switch (Pause.MENU[this.choice]) {
            case 'RECOMMENCER LE NIVEAU':
                state.resetScore(); // comme après un game over : score et vies repartent de zéro
                game.campaign.startLevel(state.levelIndex);
                break;
            case "RETOUR A L'ACCUEIL":
                game.goHome();
                break;
            default:
                break;
        }
    }

    #canPause() {
        const { state } = this.game;
        return state.mode === 'playing' && !state.demo;
    }
}
