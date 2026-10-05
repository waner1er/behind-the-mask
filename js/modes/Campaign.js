import { LIVES } from '../config.js';
import { pad } from '../util/math.js';

/** L'enchaînement de la partie : intro, niveaux, vies, game over, fin. */
export class Campaign {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /** JOUER : l'intro, puis le niveau 1. */
    playIntro() {
        const { game } = this;
        game.story.start(game.data.story.intro, () => this.startLevel(0));
        game.audio.playMusic(game.data.levels[game.data.story.introTrack - 1]?.audio);
    }

    async startLevel(index) {
        const { game, state } = this;
        const { data } = game;
        game.setMode('loading');
        game.hud.message('LOADING...');
        await game.backdrop.load(index);

        const level = data.levels[index];
        Object.assign(state, {
            levelIndex: index, level, cam: 0, locked: false, waveIndex: 0,
            enemies: [], projectiles: [], pickups: [], explosions: [], notes: [], particles: [], flashes: [],
            sparks: [], texts: [], boss: null, lastEnemy: null, toughGuys: [], cast: [],
        });
        game.hostages.place(level, data.floor);
        game.boxes.place(level, data.floor);
        // un joueur à court de vies revient au niveau suivant
        state.lives = state.lives.map((lives) => (lives > 0 ? lives : LIVES.start));
        state.players = game.spawnPlayers(70, 158);

        game.hud.showGo(false);
        game.hud.setLevelLabel(state.demo ? `DÉMO · LEVEL ${level.number}` : `LEVEL ${level.number}`);
        document.documentElement.style.setProperty('--accent', level.accent);
        game.hud.message(
            `<span class="hud__small">MISSION ${level.number} · START!</span><br>`
            + `<span class="hud__track">${level.title}</span>`
            + (level.feat ? `<br><span class="hud__small">FEAT. ${level.feat}</span>` : '')
            + `<br><br><span class="hud__lyrics">${level.intro.join('<br>')}</span>`,
        );
        game.audio.playTrack(level.audio);
        game.audio.preload(data.levels[index + 1]?.audio);
        game.sfx('start');
        game.setMode('intro');
    }

    nextLevel() {
        this.startLevel(this.state.levelIndex + 1);
    }

    /** Bonus de fin de niveau ; après le dernier boss (le Docteur Mask), place à la fin. */
    levelClear() {
        const { game, state } = this;
        const bonus = state.players.reduce((sum, p) => sum + p.hp * 10, 1000);
        state.score += bonus;
        if (state.levelIndex === game.data.levels.length - 1) {
            game.story.start(game.data.story.ending, () => game.goHome());
            return;
        }
        game.setMode('clear');
        game.hud.showGo(false);
        game.hud.message(`MISSION COMPLETE!<br><br><span class="hud__small">BONUS ${pad(bonus)}</span>`);
        game.sfx('clear');
    }

    /** Après un K.O. d'un héros : il se relève, ou il quitte la partie ; plus personne, c'est le game over. */
    respawn(p) {
        const { game, state } = this;
        if (state.lives[p.slot] > 0) {
            Object.assign(p, { hp: p.maxHp, invuln: 120, vx: 0 });
            p.setState('idle');
            return;
        }
        if (state.players.length > 1) {
            state.players = state.players.filter((other) => other !== p);
            game.shout(`${p.slot + 1}P GAME OVER`, p.x, p.y - 40, '#ffffff');
            return;
        }
        game.setMode('gameover');
        game.audio.stopMusic();
        game.sfx('gameOver');
        game.hud.message('IF YOU KILL A MONSTER<br>YOU CAN BECOME A MONSTER<br><br>'
            + '<span class="blink-text">GAME OVER · START TO CONTINUE</span>');
    }

    /** Une vie de plus pour chaque joueur tous les 10 000 points (score commun), et le meilleur score. */
    updateScore() {
        const { game, state } = this;
        const playing = state.players.length && !['title', 'select', 'story'].includes(state.mode);
        if (playing && state.score >= state.nextLife) {
            state.nextLife += LIVES.extraEvery;
            state.lives = state.lives.map((lives) => lives + 1);
            for (const p of state.players) game.shout('1UP !', p.x, p.y - 60, '#7dff5a');
            game.sfx('oneup');
        }
        if (state.score > state.hiscore) {
            state.hiscore = state.score;
            game.hiscores.save(state.hiscore);
        }
    }
}
