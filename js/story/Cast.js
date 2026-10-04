/**
 * Les figurants des scènes animées : ils marchent vers une cible (targetX), finissent leurs attaques,
 * sautent, partent en skate. Plus les citoyens, les oiseaux et les symboles qui s'envolent.
 */
export class Cast {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /**
     * Ajoute un personnage à la scène.
     * options : scale, dir, targetX (où il marche), face (sens une fois arrivé), speed, delay...
     */
    add(type, x, y, options = {}) {
        const { data } = this.game;
        const cfg = data.enemies[type] ?? data.levels.find((l) => l.boss.sprite === type)?.boss;
        const actor = this.game.spawn(type, x, y, cfg);
        Object.assign(actor, { z: 0, vz: 0 }, options);
        actor.dir = options.dir ?? 1;
        this.state.cast.push(actor);
        return actor;
    }

    update() {
        const { state } = this;
        for (const actor of state.cast) this.#act(actor);

        for (const c of state.citizens) {
            c.t++;
            c.x += c.vx;
        }
        const W = this.game.data.width;
        for (const bird of state.birds) {
            bird.t++;
            bird.x += bird.vx;
        }
        state.birds = state.birds.filter((bird) => bird.x > -20 && bird.x < W + 20);
        for (const f of state.fx) {
            f.t++;
            f.y -= 0.4;
        }
        state.fx = state.fx.filter((f) => f.t < 90);
    }

    #act(actor) {
        actor.anim++;
        switch (actor.state) {
            case 'attack':
                actor.t++;
                if (actor.t >= 20) actor.setState('idle');
                return;
            case 'jump':
                actor.t++;
                actor.z += actor.vz;
                actor.vz -= 0.22;
                if (actor.z <= 0) {
                    Object.assign(actor, { z: 0, vz: 0 });
                    actor.setState('idle');
                }
                return;
            case 'skate':
                actor.x += actor.dir * 2.6;
                return;
        }
        if (actor.targetX !== undefined && Math.abs(actor.targetX - actor.x) > 1) {
            actor.dir = Math.sign(actor.targetX - actor.x);
            actor.x += actor.dir * (actor.speed ?? 1);
            actor.setState('walk');
        } else if (actor.state === 'walk') {
            actor.dir = actor.face ?? actor.dir;
            actor.setState('idle');
        }
    }
}
