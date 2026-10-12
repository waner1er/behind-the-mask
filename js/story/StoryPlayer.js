import { Typewriter } from './Typewriter.js';

/**
 * Déroule une séquence animée (config/story.php) : à chaque étape, un « réalisateur » met le plan
 * en scène et le texte s'écrit dans la boîte de dialogue. ESPACE ou B pour passer au plan suivant,
 * START pour passer toute la séquence (on connaît déjà l'histoire).
 */
export class StoryPlayer {
    /** En démo, un texte fini reste affiché 1,5 s avant de passer tout seul. */
    static DEMO_READING_TIME = 90;

    constructor(game, directors) {
        this.game = game;
        this.directors = directors;
        this.typewriter = new Typewriter((name) => game.sfx(name));
        this.steps = [];
        this.index = 0;
        this.t = 0;
        this.waited = 0;
        this.onEnd = null;
    }

    get step() {
        return this.steps[this.index];
    }

    /** Le plan en cours (pete, villains, mask...). */
    get scene() {
        return this.step?.scene;
    }

    start(steps, onEnd) {
        const { game } = this;
        Object.assign(this, { steps, index: 0, onEnd });
        game.state.clearScene();
        game.hud.setLayout('story', true);
        game.hud.message(game.state.demo ? '' : '<span class="hud__skip">START : PASSER</span>');
        game.setMode('story');
        this.#enter();
    }

    update() {
        const { game } = this;
        const { step, typewriter } = this;
        this.t++;
        this.directors[step.scene]?.update(step);
        game.cast.update();

        const html = typewriter.update();
        if (html !== null) game.hud.setDialog(html);

        // le générique a sa propre sortie (CreditsDirector) ; ailleurs START passe toute la séquence
        if (step.scene !== 'credits' && !game.state.demo && game.input.wasPressed('Enter')) {
            this.#end();
            return;
        }
        if (step.duration) {
            if (this.t >= step.duration) this.#next();
            return;
        }
        if (step.scene === 'credits' || !this.#skipRequested()) return;
        if (typewriter.done) this.#next();
        else typewriter.finish();
    }

    #skipRequested() {
        const { game, typewriter } = this;
        const demoNext = game.state.demo && typewriter.done && !typewriter.paused
            && ++this.waited > StoryPlayer.DEMO_READING_TIME;
        return demoNext || game.input.wasPressed('Space', 'KeyB');
    }

    #enter() {
        const { game, step } = this;
        const samePlan = this.steps[this.index - 1]?.scene === step.scene;
        this.typewriter.load(step.text ?? '');
        this.waited = 0;
        game.hud.showDialog(Boolean(step.text));

        // même plan que l'étape précédente : l'animation continue, on ne la rejoue pas
        if (!samePlan) {
            this.t = 0;
            this.directors[step.scene]?.enter(step);
        }
        this.#peteReacts(step);
    }

    /** Réplique en bulle et action de Pete au moment précis de l'étape. */
    #peteReacts(step) {
        const { game } = this;
        const pete = game.state.cast.find((a) => a.type === 'hero');
        if (!pete) return;
        if (step.shout) {
            game.shout(step.shout, pete.x + 40, pete.y - 60 * pete.scale, '#ffd23f');
            game.sfx('kick');
        }
        if (step.action === 'skate') {
            pete.setState('skate');
            game.sfx('skate');
        }
    }

    #next() {
        this.index++;
        if (this.index < this.steps.length) this.#enter();
        else this.#end();
    }

    #end() {
        this.game.hud.message('');
        this.game.hud.showDialog(false);
        this.game.hud.setLayout('story', false);
        this.onEnd?.();
    }
}
