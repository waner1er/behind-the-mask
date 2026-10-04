import { Director } from './Director.js';

/** Intro : Pete attend en garde ; il part en skate sur son cri de guerre (action de l'étape, voir config/story.php). */
export class GoDirector extends Director {
    enter() {
        this.setStage(0);
        this.cast.add('hero', 90, 160, { scale: 1.5, dir: 1 });
    }
}
