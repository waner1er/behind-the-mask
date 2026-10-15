import { RACE } from '../config.js';

/**
 * La route de la descente, découpée en segments (voir Vigilante\Level\RaceTrack) :
 * chaque segment a un virage (curve) et une altitude à ses deux bouts (y1, y2).
 * Les virages et les bosses s'installent et se relâchent en douceur au début et à la fin d'une section.
 */
export class Road {
    /** Segments plats ajoutés après l'arrivée : la route continue jusqu'à l'horizon. */
    static RUNOUT = RACE.drawDistance + 20;

    constructor(track) {
        this.segments = [];
        this.finish = track.finish;
        let y = 0;
        for (const [length, curve, hill] of track.sections) {
            const startY = y;
            const endY = startY + hill * RACE.segment;
            const ease = Math.max(1, Math.floor(length / 4));
            for (let i = 0; i < length; i++) {
                const intensity = Math.min(1, i / ease, (length - i) / ease);
                const y2 = startY + (endY - startY) * Road.#easeInOut((i + 1) / length);
                this.#add(curve * Road.#easeInOut(intensity), y, y2);
                y = y2;
            }
        }
        for (let i = 0; i < Road.RUNOUT; i++) this.#add(0, y, y);

        this.#place(track.roadside, 'props');
        this.#place(track.obstacles, 'obstacles');
        this.#place([{ at: track.finish, sprite: 'gate', x: 0 }], 'props');
    }

    /** Longueur jusqu'à l'arrivée, en unités du monde. */
    get finishZ() {
        return this.finish * RACE.segment;
    }

    at(z) {
        const index = Math.floor(z / RACE.segment);
        return this.segments[Math.max(0, Math.min(this.segments.length - 1, index))];
    }

    /** Altitude de la route à la profondeur z. */
    heightAt(z) {
        const seg = this.at(z);
        const percent = (z % RACE.segment) / RACE.segment;
        return seg.y1 + (seg.y2 - seg.y1) * percent;
    }

    #add(curve, y1, y2) {
        this.segments.push({ index: this.segments.length, curve, y1, y2, props: [], obstacles: [] });
    }

    #place(items, key) {
        // copies : un obstacle renversé ne doit pas le rester à la partie suivante
        for (const item of items) this.segments[item.at]?.[key].push({ ...item });
    }

    static #easeInOut(t) {
        return -Math.cos(Math.PI * t) / 2 + 0.5;
    }
}
