import { BoomDirector } from './BoomDirector.js';
import { CreditsDirector } from './CreditsDirector.js';
import { GoDirector } from './GoDirector.js';
import { MaskDirector } from './MaskDirector.js';
import { PeaceDirector } from './PeaceDirector.js';
import { PeteDirector } from './PeteDirector.js';
import { VillainsDirector } from './VillainsDirector.js';

/** Un réalisateur par plan, indexé par le nom de plan utilisé dans config/story.php. */
export function createDirectors(game) {
    const peace = new PeaceDirector(game);
    return {
        pete: new PeteDirector(game),
        villains: new VillainsDirector(game),
        mask: new MaskDirector(game),
        go: new GoDirector(game),
        boom: new BoomDirector(game),
        peace,
        credits: new CreditsDirector(game, peace),
    };
}
