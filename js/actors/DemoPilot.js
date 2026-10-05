import { clamp } from '../util/math.js';

/**
 * Le « cerveau » de Pete en mode démo : à chaque image, il simule les touches qu'un joueur appuierait.
 * Il vise un ennemi, sinon la caisse « ? », sinon un bonus, sinon il avance.
 */
export class DemoPilot {
    /** Si plus rien ne bouge pendant 20 s dans une vague, on débloque la situation. */
    static IDLE_LIMIT = 1200;

    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.input = game.input;
        this.targetId = null;
        this.lastScore = null;
        this.idle = 0;
    }

    start() {
        this.state.demo = true;
        this.state.duo = false;
        this.game.campaign.playIntro();
    }

    /** N'importe quelle touche ou bouton ramène à l'accueil. */
    exit() {
        this.state.demo = false;
        this.input.clear();
        this.game.goHome();
    }

    play() {
        const { state, input } = this;
        input.releaseAll();
        const [p] = state.players;
        if (state.mode !== 'playing' || !p || ['dead', 'hurt'].includes(p.state)) return;

        // les ennemis juste hors champ comptent aussi : Pete se place au bord et frappe
        const W = this.game.data.width;
        const onScreen = (x) => x > state.cam - 60 && x < state.cam + W + 60;
        const alive = state.enemies.filter((e) => !e.isUntouchable && onScreen(e.x));

        this.#unstick();
        const boss = alive.find((e) => e.boss);

        if (p.wods > 0 && (boss || alive.length >= 4)) {
            input.held.add('Space');
            input.held.add('KeyV');
            input.pressed.add('Space');
            return;
        }

        const target = this.#chooseTarget(p, alive);
        if (!target) {
            input.held.add('ArrowRight'); // rien à l'écran : on avance
            return;
        }
        this.#steer(p, target, alive.length);
    }

    #unstick() {
        const { state } = this;
        if (state.score !== this.lastScore) {
            this.lastScore = state.score;
            this.idle = 0;
        } else if (++this.idle > DemoPilot.IDLE_LIMIT && state.locked) {
            this.idle = 0;
            for (const e of state.enemies) {
                if (!e.isDown) this.game.combat.killEnemy(e, 1);
            }
        }
    }

    /**
     * Garde la même cible tant qu'elle est debout (sinon Pete zappe d'un ennemi à l'autre),
     * sinon l'ennemi le plus proche, la caisse « ? » ou un bonus utile.
     */
    #chooseTarget(p, alive) {
        const { state } = this;
        const W = this.game.data.width;
        const nearest = alive.find((e) => e.id === this.targetId)
            ?? alive.sort((a, b) => Math.abs(a.x - p.x) - Math.abs(b.x - p.x))[0];
        this.targetId = nearest?.id;

        if (nearest) {
            // à portée de katana, même face aux très gros boss ; si la place est hors de l'écran, on passe de l'autre côté
            const spacing = 20 + (nearest.scale - 1) * 10;
            const fits = (x) => x >= state.cam + 10 && x <= state.cam + W - 10;
            let side = Math.sign(p.x - nearest.x) || -1;
            if (!fits(nearest.x + side * spacing)) side = -side;
            return { x: nearest.x + side * spacing, y: nearest.y, face: nearest.x, scale: nearest.scale, strike: true };
        }

        // la caméra ne recule jamais : ce qui est resté derrière le bord gauche est perdu
        const reachable = (x) => x > state.cam && x < state.cam + W;
        const box = state.boxes.find((b) => b.hp > 0 && reachable(b.x));
        if (box) return { x: box.x - 22, y: box.y, face: box.x, scale: 1, strike: true };

        const pickup = state.pickups.find((it) => it.z === 0 && reachable(it.x) && ['wod', 'life', 'vinyls'].includes(it.kind));
        return pickup ? { x: pickup.x, y: pickup.y, strike: false } : null;
    }

    #steer(p, target, crowd) {
        const { state, input } = this;
        const W = this.game.data.width;
        target.x = clamp(target.x, state.cam + 10, state.cam + W - 10); // impossible de sortir de l'écran
        const dx = target.x - p.x;
        const dy = target.y - p.y;

        // à portée : on se tourne vers la cible et on frappe sans se replacer
        // (zone large : se retourner fait bouger Pete d'un pas, il ne doit pas en sortir)
        const gap = Math.abs(target.face - p.x);
        const inReach = gap >= 6 && gap <= 34 + ((target.scale ?? 1) - 1) * 12;
        if (target.strike && inReach && Math.abs(dy) <= 4) {
            const facing = Math.sign(target.face - p.x) || 1;
            if (p.dir !== facing) input.held.add(facing > 0 ? 'ArrowRight' : 'ArrowLeft');
            else if (state.tick % 200 === 0) input.pressed.add('KeyB');
            else if (crowd >= 3 && p.vinyls > 0 && state.tick % 150 === 0) input.pressed.add('KeyV');
            else if (state.tick % 12 === 0) input.pressed.add('Space');
            return;
        }

        if (Math.abs(dx) > 3) input.held.add(dx > 0 ? 'ArrowRight' : 'ArrowLeft');
        if (Math.abs(dy) > 2) input.held.add(dy > 0 ? 'ArrowDown' : 'ArrowUp');
    }
}
