/**
 * WALL OF DEATH : des gros durs hardcore chargent à travers l'écran avec Pete.
 * Tous les petits ennemis tombent, un boss perd la moitié de sa vie par Wall of Death
 * (avec deux en réserve, on les lance ensemble : le boss y passe).
 */
export class WallOfDeath {
    static GUYS_PER_WALL = 5;
    static TYPES = ['tough1', 'tough2', 'tough3'];

    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    launch(p) {
        const { game, state } = this;
        const { width, floor } = game.data;
        const power = p.wods;
        p.wods = 0;
        p.invuln = Math.max(p.invuln, 150);
        const fromLeft = p.dir > 0;

        for (let i = 0; i < WallOfDeath.GUYS_PER_WALL * power; i++) {
            const x = fromLeft ? state.cam - 20 - i * 14 : state.cam + width + 20 + i * 14;
            const y = floor.min + ((i * 7) % (floor.max - floor.min));
            const guy = game.spawn(WallOfDeath.TYPES[i % WallOfDeath.TYPES.length], x, y, { hp: 1 });
            guy.dir = fromLeft ? 1 : -1;
            guy.scale = 1.5;
            guy.setState('walk');
            state.toughGuys.push(guy);
        }
        state.wodPower = power;
        state.shake = 10;
        state.flash = 6;
        game.hud.message('<span class="hud__warning">WALL OF DEATH !!!</span>', 120);
        game.sfx('wod');
    }

    update() {
        const { game, state } = this;
        for (const guy of state.toughGuys) {
            guy.anim += 3; // ils courent à fond
            guy.x += guy.dir * 4.2;
            if (state.tick % 6 === 0) state.shake = 4;
            for (const e of state.enemies) {
                if (e.isDown || Math.abs(e.x - guy.x) > 14) continue;
                if (state.toughGuys.some((g) => g.hits.has(e.id))) continue; // percuté une seule fois par charge
                guy.hits.add(e.id);
                state.sparks.push({ x: e.x, y: e.y - 20 * e.scale, t: 0 });
                state.target(e);
                if (!e.boss) {
                    game.combat.killEnemy(e, guy.dir);
                    continue;
                }
                e.hp -= Math.ceil(e.maxHp / 2) * state.wodPower;
                if (e.hp <= 0) game.combat.killEnemy(e, guy.dir);
                else game.sfx('heavyHit');
            }
        }
        const { width } = game.data;
        state.toughGuys = state.toughGuys.filter((g) => g.x > state.cam - 120 && g.x < state.cam + width + 120);
    }
}
