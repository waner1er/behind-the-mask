/**
 * Sur mobile, seuls les commandes et les liens réagissent au doigt : pas de zoom au double-tap,
 * pas de pincement, pas de défilement. Au premier appui : plein écran et paysage, si le navigateur l'accepte.
 */
export class MobileGuard {
    static CONTROLS = '[data-key], [data-touch-stick], [data-joystick], a';

    bind(root = document) {
        root.addEventListener('touchstart', (e) => {
            if (!e.target.closest('a')) e.preventDefault();
        }, { passive: false });
        root.addEventListener('touchmove', (e) => e.preventDefault(), { passive: false });
        root.addEventListener('dblclick', (e) => e.preventDefault());
        root.addEventListener('gesturestart', (e) => e.preventDefault()); // pincement sur iPhone
        root.addEventListener('contextmenu', (e) => {
            if (!e.target.closest(MobileGuard.CONTROLS)) e.preventDefault();
        });

        if (matchMedia('(pointer: coarse)').matches) {
            addEventListener('pointerdown', () => this.#goLandscape(), { once: true });
        }
    }

    #goLandscape() {
        Promise.resolve(document.documentElement.requestFullscreen?.({ navigationUI: 'hide' }))
            .then(() => screen.orientation?.lock?.('landscape'))
            .catch(() => {});
    }
}
