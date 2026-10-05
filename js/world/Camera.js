import { clamp, pick, rand } from '../util/math.js';

/**
 * La caméra avance avec les héros (à deux, sans laisser le dernier hors champ), seulement vers la droite (comme dans Final Fight).
 * À chaque déclencheur, elle se bloque le temps d'une vague d'ennemis ; la dernière vague est le boss.
 */
export class Camera {
    /** Le héros reste à cette distance du bord gauche quand la caméra le suit. */
    static LEAD = 130;

    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.width = game.data.width;
    }

    update() {
        const { game, state } = this;
        const wave = state.level.waves[state.waveIndex];

        if (!state.locked && state.players.length) {
            const xs = state.players.map((p) => p.x);
            const lead = Math.min(Math.max(...xs) - Camera.LEAD, Math.min(...xs) - 10);
            const target = clamp(lead, 0, game.data.levelLength - this.width);
            state.cam = Math.max(state.cam, target);
            if (wave) state.cam = Math.min(state.cam, wave.at);
        }

        if (!state.locked && wave && state.cam >= wave.at) {
            state.locked = true;
            game.hud.showGo(false);
            this.#spawnWave(wave);
        }

        if (state.locked && state.enemies.every((e) => e.isDown)) {
            state.locked = false;
            state.waveIndex++;
            if (state.waveIndex >= state.level.waves.length) game.campaign.levelClear();
            else game.hud.showGo(true);
        }
    }

    #spawnWave(wave) {
        const { game, state } = this;
        const { floor } = game.data;
        const W = this.width;

        // une caisse de vinyles traîne parfois par terre (toujours à la 2e vague)
        if (state.waveIndex === 1 || Math.random() < 0.4) {
            game.pickups.spawn('vinyls', state.cam + rand(80, W - 60), rand(floor.min, floor.max));
        }

        wave.enemies.forEach((type, i) => {
            const fromRight = i % 2 === 0;
            const x = fromRight ? state.cam + W + 16 + i * 14 : state.cam - 16 - i * 14;
            const enemy = game.spawn(type, x, rand(floor.min, floor.max));
            enemy.dir = fromRight ? -1 : 1;
            state.enemies.push(enemy);
        });

        if (wave.boss) {
            this.#spawnBoss(wave.boss);
        } else {
            const lyric = state.level.shouts.length ? pick(state.level.shouts) : '';
            game.hud.message(`WAVE ${state.waveIndex + 1}<br><br><span class="hud__lyrics">${lyric}</span>`, 110);
        }
    }

    #spawnBoss(cfg) {
        const { game, state } = this;
        const { floor } = game.data;
        const boss = game.spawn(cfg.sprite, state.cam + this.width + 20 * cfg.scale, (floor.min + floor.max) / 2, cfg);
        boss.boss = true;
        boss.dir = -1;
        boss.special = 180;
        state.enemies.push(boss);
        state.boss = boss;
        state.lastEnemy = boss;
        game.hud.message(`<span class="hud__warning">WARNING!</span><br><br>${cfg.name}`, 160);
        game.sfx('warning');
        setTimeout(() => game.shout(cfg.line, boss.x - 40, boss.y - 50 * boss.scale, '#ffffff'), 2800);
    }
}
