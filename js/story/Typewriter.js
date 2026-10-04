const PAUSE_TAG = /(\[PAUSE \d+(?:\.\d+)?\])/;
const PAUSE_VALUE = /^\[PAUSE (\d+(?:\.\d+)?)\]$/;

/** Texte qui s'écrit lettre par lettre, avec des silences gênants : [PAUSE 3] fige l'écriture 3 secondes. */
export class Typewriter {
    static SPEED = 0.7; // lettres par image

    constructor(sfx) {
        this.sfx = sfx;
        this.load('');
    }

    /** Retire les balises [PAUSE n] et note où l'écriture doit s'arrêter. */
    static parse(raw) {
        const pauses = {};
        let text = '';
        for (const part of raw.split(PAUSE_TAG)) {
            const pause = part.match(PAUSE_VALUE);
            if (pause) pauses[text.length] = Math.round(parseFloat(pause[1]) * 60);
            else text += part;
        }
        return { text, pauses };
    }

    load(raw) {
        Object.assign(this, Typewriter.parse(raw), { typed: 0, shown: '', pauseLeft: 0, pauseDone: {} });
    }

    get done() {
        return this.typed >= this.text.length;
    }

    get paused() {
        return this.pauseLeft > 0;
    }

    /** Affiche tout le texte d'un coup. */
    finish() {
        this.typed = this.text.length;
        this.pauseLeft = 0;
    }

    /** Avance d'une image. Renvoie le HTML à afficher s'il a changé, sinon null. */
    update() {
        const { text } = this;
        if (!text) return null;

        const before = Math.floor(this.typed);
        const pause = this.pauses[before];
        if (pause && this.pauseLeft === 0 && !this.pauseDone[before]) {
            this.pauseLeft = pause;
            this.pauseDone = { ...this.pauseDone, [before]: true };
            this.sfx('cricket');
        }
        if (this.pauseLeft > 0) {
            this.pauseLeft--;
        } else {
            this.typed = Math.min(text.length, this.typed + Typewriter.SPEED);
            if (Math.floor(this.typed) > before && before % 3 === 0 && text[before] !== ' ') this.sfx('type');
        }

        const html = text.slice(0, Math.floor(this.typed)) + (this.done ? ' <span class="blink-text">▶</span>' : '');
        if (html === this.shown) return null;
        this.shown = html;
        return html;
    }
}
