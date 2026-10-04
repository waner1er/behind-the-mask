/** Met en scène un plan des séquences animées : enter() au début du plan, update() à chaque image. */
export class Director {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    /** Images écoulées depuis le début du plan. */
    get t() {
        return this.game.story.t;
    }

    get cast() {
        return this.game.cast;
    }

    enter() {}

    update() {}

    /** Vide la scène et place le décor d'un niveau. */
    setStage(sceneIndex) {
        this.state.cast = [];
        this.state.cam = 0;
        this.game.backdrop.load(sceneIndex);
    }
}
