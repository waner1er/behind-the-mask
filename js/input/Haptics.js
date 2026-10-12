/**
 * Vibrations : la manette si on joue avec (vibreurs « dual-rumble »), sinon le téléphone —
 * retour haptique natif dans l'app iOS (message « haptics », voir ios/Vigilante/HapticsHandler.swift),
 * navigator.vibrate ailleurs (Android). Silencieux en démo et là où rien ne sait vibrer.
 */
export class Haptics {
    /**
     * Du plus léger au plus fort : à la même image, seul le plus fort est joué.
     * phone : style UIKit (impact light/medium/heavy, ou notification success/error) ;
     * vibrate : millisecondes pour navigator.vibrate ; rumble : [durée ms, gros moteur, petit moteur].
     */
    static EFFECTS = {
        hit: { phone: 'light', vibrate: 12, rumble: [70, 0, 0.45] },
        explosion: { phone: 'medium', vibrate: 35, rumble: [250, 0.55, 0.8] },
        hurt: { phone: 'heavy', vibrate: 50, rumble: [200, 0.8, 0.5] },
        ko: { phone: 'error', vibrate: [70, 40, 140], rumble: [550, 1, 0.6] },
        wod: { phone: 'heavy', vibrate: [60, 30, 60, 30, 160], rumble: [900, 1, 0.8] },
        boss: { phone: 'success', vibrate: [90, 50, 220], rumble: [750, 1, 1] },
    };

    constructor(game) {
        this.game = game;
        this.tick = -1;
        this.strength = -1;
    }

    /** player : le héros concerné ; à deux, seule sa manette vibre. */
    pulse(name, player = null) {
        const { state, gamepads } = this.game;
        if (state.demo) return;
        const names = Object.keys(Haptics.EFFECTS);
        const strength = names.indexOf(name);
        if (strength < 0 || (state.tick === this.tick && strength <= this.strength)) return;
        this.tick = state.tick;
        this.strength = strength;

        const effect = Haptics.EFFECTS[name];
        const pads = gamepads.connected();
        if (pads.length) {
            const slot = state.duo ? player?.slot : undefined;
            pads.forEach((pad, i) => (slot === undefined || slot === i) && Haptics.#rumble(pad, effect.rumble));
        } else {
            Haptics.#phone(effect);
        }
    }

    static #rumble(pad, [duration, strongMagnitude, weakMagnitude]) {
        pad.vibrationActuator?.playEffect?.('dual-rumble', { duration, strongMagnitude, weakMagnitude })
            ?.catch(() => {}); // manette sans vibreur : tant pis
    }

    static #phone(effect) {
        const ios = window.webkit?.messageHandlers?.haptics;
        if (ios) ios.postMessage(effect.phone);
        else navigator.vibrate?.(effect.vibrate);
    }
}
