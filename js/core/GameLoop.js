import { FPS } from '../config.js';

/**
 * Boucle à pas fixe, comme une vraie borne : la logique avance toujours de 1/60 s,
 * quelle que soit la fréquence de l'écran ; on dessine une fois par rafraîchissement.
 */
export class GameLoop {
    static STEP = 1000 / FPS;

    /** Au retour d'un onglet en arrière-plan, on ne rattrape pas plus de 100 ms. */
    static MAX_CATCH_UP = 100;

    constructor(update, draw) {
        this.update = update;
        this.draw = draw;
        this.last = performance.now();
        this.accumulator = 0;
    }

    start() {
        requestAnimationFrame((now) => this.#frame(now));
    }

    #frame(now) {
        this.accumulator += Math.min(GameLoop.MAX_CATCH_UP, now - this.last);
        this.last = now;
        while (this.accumulator >= GameLoop.STEP) {
            this.update();
            this.accumulator -= GameLoop.STEP;
        }
        this.draw();
        requestAnimationFrame((next) => this.#frame(next));
    }
}
