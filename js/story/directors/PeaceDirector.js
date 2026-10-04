import { pick, rand } from '../../util/math.js';
import { Director } from './Director.js';

/** Thème de la ville libérée (son décor SVG est scenes/level-peace). */
export const PEACE_LEVEL = { chaos: 0, accent: '#ffc62a', shouts: [], rain: false, fog: false, number: 10 };

/** Fin : la ville libérée, les arbres poussent, les gens sortent en souriant. */
export class PeaceDirector extends Director {
    static MAX_CITIZENS = 12;

    enter() {
        const { game, state } = this;
        if (state.mode === 'story' && state.level === PEACE_LEVEL) return; // déjà en place
        state.level = PEACE_LEVEL;
        state.explosions = [];
        state.particles = [];
        state.citizens = [];
        this.setStage('peace');
        game.audio.playMusic(game.data.levels[game.data.story.endingTrack - 1]?.audio);
        this.cast.add('hero', 150, 160, { scale: 1.5, dir: 1 });
    }

    update() {
        const { state } = this;
        const pete = state.cast[0];
        if (this.t % 45 === 0 && state.citizens.length < PeaceDirector.MAX_CITIZENS) this.#citizenComesOut();

        // ils s'arrêtent en ville et sautillent sur place
        for (const c of state.citizens) {
            if ((c.vx > 0 && c.x > rand(30, 300)) || (c.vx < 0 && c.x < rand(20, 290))) c.vx *= 0.97;
        }
        if (this.t % 70 === 0) state.birds.push({ x: -10, y: rand(20, 70), vx: rand(0.6, 1.2), t: 0 });
        if (this.t % 150 === 75) {
            pete.setState('jump');
            pete.vz = 3;
            pete.z = 0.1;
        }
    }

    #citizenComesOut() {
        const { width, floor } = this.game.data;
        const fromLeft = Math.random() < 0.5;
        this.state.citizens.push({
            x: fromLeft ? -10 : width + 10,
            y: rand(floor.min, floor.max),
            vx: (fromLeft ? 1 : -1) * rand(0.4, 0.8),
            t: rand(0, 30),
            image: pick(this.game.sprites.citizens),
        });
    }
}
