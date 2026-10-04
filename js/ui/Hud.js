import { pad } from '../util/math.js';

/** L'affichage par-dessus l'écran (DOM) : score, vies, barres de vie, messages, dialogues, générique. */
export class Hud {
    constructor(root, game) {
        this.game = game;
        this.state = game.state;
        this.el = Object.fromEntries([...root.querySelectorAll('[data-hud]')].map((el) => [el.dataset.hud, el]));
        this.el.root = root.querySelector('.hud');
    }

    /** Message au centre de l'écran, pendant N images (par défaut jusqu'au suivant). */
    message(html, frames = Infinity) {
        this.el.message.innerHTML = html;
        this.state.messageUntil = this.state.tick + frames;
    }

    /** Efface le message quand son temps est écoulé. */
    expireMessage() {
        if (this.state.tick > this.state.messageUntil) this.message('');
    }

    setLevelLabel(text) {
        this.el.level.textContent = text;
    }

    setLayout(name, enabled) {
        this.el.root.classList.toggle(`hud--${name}`, enabled);
    }

    setMuted(muted) {
        this.el.mute.hidden = !muted;
    }

    showGo(visible) {
        this.el.go.hidden = !visible;
    }

    showDialog(visible) {
        this.el.dialog.hidden = !visible;
    }

    setDialog(html) {
        this.el.dialog.innerHTML = html;
    }

    /** Générique de fin ; null pour le cacher. */
    showCredits(html) {
        this.el.credits.hidden = html === null;
        if (html !== null) this.el.credits.innerHTML = html;
    }

    /** Mise à jour à chaque image : score, vie, inventaire, ennemi visé. */
    update() {
        const { state, el } = this;
        const { weapons } = this.game.data;
        const p = state.player;
        const inGame = !['title', 'loading'].includes(state.mode);

        el.score.textContent = pad(state.score);
        el.hiscore.textContent = pad(state.hiscore);
        el.life.style.width = `${inGame && p ? (p.hp / p.maxHp) * 100 : 100}%`;

        const seconds = p?.weapon ? Math.ceil((p.weaponUntil - state.tick) / 60) : 0;
        el.lives.textContent = `♥ x${Math.max(0, state.lives)} · VINYL ${p?.vinyls ?? 0}`
            + (p?.wods ? ` · WOD ${p.wods}` : '')
            + (p?.weapon ? ` · ${weapons[p.weapon].name} ${seconds}` : '');

        const boss = state.boss && state.boss.state !== 'dead' ? state.boss : null;
        const enemy = boss ?? state.lastEnemy;
        const showEnemy = inGame && enemy && (boss || state.tick < state.lastEnemyUntil);
        el.enemy.hidden = !showEnemy;
        if (showEnemy) {
            el['enemy-name'].textContent = enemy.cfg.name;
            el['enemy-life'].style.width = `${(Math.max(0, enemy.hp) / enemy.maxHp) * 100}%`;
        }
    }
}
