/**
 * Manettes (Xbox, PlayStation, MFi, Backbone…) via l'API Gamepad, en disposition « standard ».
 * Chaque manette est traduite en touches du mode solo, comme le clavier : en solo, toutes
 * pilotent Pete ; à deux, la 1re manette branchée est le joueur 1, la 2e le joueur 2.
 * L'API n'émet pas d'événement par bouton : on relit les manettes à chaque image.
 */
export class GamepadControls {
    /** Bouton (numéro de la disposition standard) → touches. Les noms sont ceux de la manette Xbox. */
    static BUTTONS = {
        0: ['Space'], // A : katana
        1: ['KeyB'], // B : coup de pied sauté
        2: ['KeyV'], // X : vinyle (A + X : wall of death, comme ESPACE + V)
        3: ['KeyC'], // Y : skate
        5: ['Space', 'KeyV'], // RB : wall of death d'un seul doigt
        8: ['KeyM'], // Select / Options : musique
        9: ['Enter'], // Start / Menu
        12: ['ArrowUp'],
        13: ['ArrowDown'],
        14: ['ArrowLeft'],
        15: ['ArrowRight'],
    };

    /** Au-delà, le stick gauche compte comme une flèche (en dessous, il flotte au repos). */
    static DEAD_ZONE = 0.5;

    /** Touches communes à la partie (pas à un joueur), comme au clavier. */
    static SHARED = ['Enter', 'KeyM'];

    constructor(game, root = document.documentElement) {
        this.game = game;
        this.root = root;
        /** Par manette : touche maintenue → commande qui l'a reçue (pour la relâcher au même endroit). */
        this.held = new Map();
        this.count = 0;
    }

    poll() {
        const pads = navigator.getGamepads?.() ?? [];
        const connected = [...pads].filter((pad) => pad?.connected && pad.mapping === 'standard');
        const seen = new Set();

        connected.forEach((pad, slot) => {
            seen.add(pad.index);
            this.#apply(pad.index, this.#codes(pad), slot);
        });
        for (const index of this.held.keys()) {
            if (!seen.has(index)) this.#apply(index, new Set(), 0); // débranchée : on lâche tout
        }
        if (connected.length !== this.count) {
            this.count = connected.length;
            // manette en main : le stick et les boutons tactiles s'effacent (style.scss)
            this.root.classList.toggle('has-gamepad', this.count > 0);
        }
    }

    /** Les touches que la manette tient enfoncées en ce moment. */
    #codes(pad) {
        const codes = new Set();
        pad.buttons.forEach((button, i) => {
            if (button.pressed) GamepadControls.BUTTONS[i]?.forEach((code) => codes.add(code));
        });
        const [x = 0, y = 0] = pad.axes;
        const dead = GamepadControls.DEAD_ZONE;
        if (x < -dead) codes.add('ArrowLeft');
        if (x > dead) codes.add('ArrowRight');
        if (y < -dead) codes.add('ArrowUp');
        if (y > dead) codes.add('ArrowDown');
        return codes;
    }

    #apply(index, codes, slot) {
        const held = this.held.get(index) ?? new Map();
        for (const [code, input] of held) {
            const target = this.#target(code, slot);
            if (!codes.has(code) || target !== input) {
                input.release(code);
                held.delete(code);
            }
        }
        for (const code of codes) {
            if (held.has(code)) continue;
            const target = this.#target(code, slot);
            this.game.audio.unlock();
            target.press(code);
            held.set(code, target);
        }
        if (held.size) this.held.set(index, held);
        else this.held.delete(index);
    }

    /** À deux, chaque manette commande son héros ; START et la musique restent communs. */
    #target(code, slot) {
        const { game } = this;
        if (!game.state.duo || GamepadControls.SHARED.includes(code)) return game.input;
        return game.pads[slot] ?? game.input;
    }
}
