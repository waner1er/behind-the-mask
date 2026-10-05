/** Un écran du jeu (accueil, choix du morceau, niveau...) : update() est appelé à chaque image. */
export class Mode {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        this.input = game.input;
    }

    update() {}

    /** Menus : le héros marche sur place pendant que la ville défile derrière lui. */
    walkInPlace() {
        const { state } = this;
        if (!state.players.length) state.players = [this.game.spawn('hero', 150, 158)];
        const [p] = state.players;
        state.cam += 0.5;
        p.x = state.cam + 150;
        p.anim++;
        p.setState('walk');
    }
}
