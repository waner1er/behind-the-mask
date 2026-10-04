import { GAME_KEYS } from './Input.js';
import { capturePointer } from './capturePointer.js';

/** Clavier, et boutons de la borne cliquables au doigt ou à la souris (data-key="KeyB"...). */
export class KeyboardControls {
    constructor(input) {
        this.input = input;
    }

    bind(root = document) {
        addEventListener('keydown', (e) => {
            if (!GAME_KEYS.includes(e.code)) return;
            e.preventDefault();
            this.input.press(e.code);
        });
        addEventListener('keyup', (e) => this.input.release(e.code));
        addEventListener('blur', () => this.input.releaseAll());

        root.querySelectorAll('[data-key]').forEach((button) => this.#bindButton(button));
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
