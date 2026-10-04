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
        state.player ??= this.game.spawn('hero', 150, 158);
        state.cam += 0.5;
        state.player.x = state.cam + 150;
        state.player.anim++;
        state.player.setState('walk');
    }
}
