import { rand } from '../util/math.js';

/** L'ambiance qui vit toute seule : la ville part en ruine au fil de l'album, la pluie tombe, les effets s'estompent. */
export class Ambience {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.width = game.data.width;
        this.height = game.data.height;
    }

    /** Braises, cendres et explosions au loin selon le chaos du niveau, et effets de combat qui retombent. */
    updateChaos() {
        const { state, width: W, height: H } = this;
        const chaos = state.level.chaos ?? 0;
        if (chaos > 0 && Math.random() < chaos * 0.7) {
            state.particles.push({ x: rand(0, W), y: H + 2, vx: rand(-0.3, 0.3), vy: -rand(0.3, 1.1), t: 0, kind: 'ember' });
        }
        if (chaos > 0.4 && Math.random() < chaos * 0.5) {
            state.particles.push({ x: rand(0, W + 40), y: -2, vx: -rand(0.2, 0.6), vy: rand(0.2, 0.5), t: 0, kind: 'ash' });
        }
        if (chaos > 0.5 && Math.random() < chaos * 0.005) {
            state.flashes.push({ x: rand(20, W - 20), y: rand(30, 75), t: 0 });
            this.game.sfx('boom');
        }

        for (const pt of state.particles) {
            pt.t++;
            pt.x += pt.vx + Math.sin(pt.t / 12) * 0.2;
            pt.y += pt.vy;
        }
        state.particles = state.particles.filter((pt) => pt.t < 260 && pt.y > -4 && pt.y < H + 4);
        state.flashes = Ambience.#age(state.flashes, 30);

        for (const note of state.notes) {
            note.t++;
            note.x += note.vx + Math.sin(note.t / 6) * 0.4; // elles ondulent en montant
            note.y += note.vy;
            note.vy += 0.05;
        }
        state.notes = state.notes.filter((note) => note.t < 70);
        state.explosions = Ambience.#age(state.explosions, 30);
    }

    /** À chaque image, quel que soit le mode : étincelles, textes et pluie. */
    updateAlways() {
        const { state, width: W, height: H } = this;
        state.sparks = Ambience.#age(state.sparks, 10);
        state.texts = Ambience.#age(state.texts, 100);
        for (const drop of state.rain) {
            drop.y += 5;
            drop.x -= 1.5;
            if (drop.y > H) Object.assign(drop, { y: rand(-20, 0), x: rand(0, W + 60) });
        }
    }

    static #age(list, lifetime) {
        for (const item of list) item.t++;
        return list.filter((item) => item.t < lifetime);
    }
}
