import { DUO_KEYS, GAME_KEYS } from './Input.js';
import { capturePointer } from './capturePointer.js';

/**
 * Clavier, et boutons de la borne cliquables au doigt ou à la souris (data-key="KeyB"...).
 * En partie à deux, les pavés des joueurs vont à leur propre commande (pads) ; le reste (START...) au jeu.
 */
export class KeyboardControls {
    constructor(input, pads = [], isDuo = () => false) {
        this.input = input;
        this.pads = pads;
        this.isDuo = isDuo;
    }

    bind(root = document) {
        addEventListener('keydown', (e) => {
            const pad = this.#padOf(e.code);
            if (pad) {
                e.preventDefault();
                pad.input.press(pad.code);
                return;
            }
            if (!GAME_KEYS.includes(e.code)) return;
            e.preventDefault();
            this.input.press(e.code);
        });
        addEventListener('keyup', (e) => {
            this.input.release(e.code);
            DUO_KEYS.forEach((keys, i) => keys[e.code] && this.pads[i]?.release(keys[e.code]));
        });
        addEventListener('blur', () => [this.input, ...this.pads].forEach((input) => input.releaseAll()));

        root.querySelectorAll('[data-key]').forEach((button) => this.#bindButton(button));
    }

    /** @returns {{input: Input, code: string}|null} la commande du joueur à qui appartient cette touche */
    #padOf(code) {
        if (!this.isDuo()) return null;
        const i = DUO_KEYS.findIndex((keys) => keys[code]);
        return i >= 0 && this.pads[i] ? { input: this.pads[i], code: DUO_KEYS[i][code] } : null;
    }

    #bindButton(button) {
        const code = button.dataset.key;
        const release = () => this.input.release(code);
        button.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            capturePointer(button, e);
            this.input.press(code);
        });
        button.addEventListener('pointerup', release);
        button.addEventListener('pointercancel', release);
        button.addEventListener('contextmenu', (e) => e.preventDefault()); // appui long sur mobile
    }
}
