/** Le panneau de la borne réagit aux commandes : le joystick s'incline, les boutons s'enfoncent. */
export class ControlPanel {
    constructor(root, input) {
        this.input = input;
        this.joystick = root.querySelector('[data-joystick]');
        this.buttons = [...root.querySelectorAll('[data-key]')];
    }

    update() {
        this.joystick.style.setProperty('--tilt-x', `${this.input.axisX * 20}deg`);
        this.joystick.style.setProperty('--tilt-y', `${this.input.axisY * 14}px`);
        this.buttons.forEach((b) => b.classList.toggle('is-pressed', this.input.isHeld(b.dataset.key)));
    }
}
