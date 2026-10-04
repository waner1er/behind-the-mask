import { ARROWS } from './Input.js';
import { capturePointer } from './capturePointer.js';

/**
 * Stick tactile (mobile) ou stick dessiné : on pose le doigt et on le fait glisser,
 * sa position par rapport au centre devient des flèches.
 */
export class TouchStick {
    /** Zone morte au centre, en proportion de la largeur du stick. */
    static DEAD_ZONE = 0.15;

    constructor(pad, input) {
        this.pad = pad;
        this.input = input;
        this.knob = pad.querySelector('.touch-stick__knob');
        this.finger = null;
    }

    bind() {
        this.pad.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            this.finger = e.pointerId;
            capturePointer(this.pad, e);
            this.#steer(e);
        });
        addEventListener('pointermove', (e) => {
            if (e.pointerId === this.finger) this.#steer(e);
        }, { passive: true });
        addEventListener('pointerup', (e) => this.#release(e));
        addEventListener('pointercancel', (e) => this.#release(e));
    }

    #steer(e) {
        const box = this.pad.getBoundingClientRect();
        const dx = e.clientX - (box.left + box.width / 2);
        const dy = e.clientY - (box.top + box.height / 2);
        this.#moveKnob(box, dx, dy);

        const dead = box.width * TouchStick.DEAD_ZONE;
        const wanted = new Set();
        if (dx < -dead) wanted.add('ArrowLeft');
        if (dx > dead) wanted.add('ArrowRight');
        if (dy < -dead) wanted.add('ArrowUp');
        if (dy > dead) wanted.add('ArrowDown');
        for (const arrow of ARROWS) {
            if (wanted.has(arrow)) this.input.press(arrow);
            else this.input.release(arrow);
        }
    }

    /** Le bouton du stick suit le pouce, sans sortir du socle. */
    #moveKnob(box, dx, dy) {
        if (!this.knob) return;
        const max = box.width / 2 - this.knob.offsetWidth / 2;
        const ratio = Math.min(1, max / (Math.hypot(dx, dy) || 1));
        this.pad.style.setProperty('--kx', `${dx * ratio}px`);
        this.pad.style.setProperty('--ky', `${dy * ratio}px`);
    }

    #release(e) {
        if (e.pointerId !== this.finger) return;
        this.finger = null;
        ARROWS.forEach((arrow) => this.input.release(arrow));
        this.pad.style.setProperty('--kx', '0px');
        this.pad.style.setProperty('--ky', '0px');
    }
}
