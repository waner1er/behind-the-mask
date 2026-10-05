/**
 * Filme une séquence animée du jeu web (intro ou fin) pour l'export OpenBOR : une image PNG
 * de l'écran toutes les 6 images de jeu (10 par seconde), sans la boîte de dialogue — le texte
 * est réécrit en pixel art par PHP, plus lisible en 320×180 — et un manifest.json qui note,
 * pour chaque image, combien de lettres la machine à écrire a déjà tapées. Le générique (plan
 * « credits ») est filmé sans son texte, une image toutes les 10 : PHP le fait défiler par-dessus.
 *
 *   node tools/capture-story.mjs dossier/ intro
 */
import { mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { openGame } from '../tests/e2e/harness.mjs';

const [directory, sequence = 'intro'] = process.argv.slice(2);
const EVERY = 6;
/** Le générique défile pendant 55 s (style.scss, credits-roll). */
const CREDITS = { every: 10, frames: 55 * 60 };
/** Une fois le texte écrit, il reste lisible 2,5 s avant le plan suivant. */
const READING = 150;

await mkdir(directory, { recursive: true });
const game = await openGame();
try {
    // écran en 640×360 exactement (2 × 320×180), sans effets de tube cathodique
    await game.page.addStyleTag({
        content: '.screen{width:640px!important;height:360px!important;border-radius:0!important;box-shadow:none!important}'
            + '.screen::before,.screen::after{display:none!important}.hud__dialog,.hud__credits{visibility:hidden!important}',
    });
    await game.page.evaluate((name) => window.game.story.start(window.game.data.story[name], () => {}), sequence);
    const steps = await game.page.evaluate((name) => window.game.data.story[name].length, sequence);
    const screen = game.page.locator('.screen');
    const manifest = [];

    for (let step = 0; step < steps; step++) {
        const info = await game.page.evaluate(() => {
            const { story } = window.game;
            return { text: story.typewriter.text, duration: story.step.duration ?? null, scene: story.step.scene };
        });
        const credits = info.scene === 'credits';
        const every = credits ? CREDITS.every : EVERY;
        const frames = [];
        let after = 0;
        for (let n = 0; ; n++) {
            const typed = await game.page.evaluate(() => Math.floor(window.game.story.typewriter.typed));
            const done = typed >= info.text.length;
            if (credits ? n * every >= CREDITS.frames : (info.duration ? n * every >= info.duration : done && after >= READING)) break;
            const file = `${String(step).padStart(2, '0')}-${String(n).padStart(3, '0')}.png`;
            await screen.screenshot({ path: join(directory, file) });
            frames.push({ file, typed });
            if (done) after += every;
            await game.run(every);
        }
        manifest.push({ ...info, every, frames });
        if (!info.duration) await game.page.keyboard.press('Enter'); // plan suivant
        // attend que l'histoire soit vraiment passée au plan suivant (ou terminée)
        for (let wait = 0; wait < 600; wait++) {
            await game.run(1);
            if (await game.page.evaluate((index) => window.game.story.index !== index, step)) break;
        }
    }
    await writeFile(join(directory, 'manifest.json'), JSON.stringify(manifest, null, 1));
    if (game.errors.length) console.error(game.errors.join('\n'));
} finally {
    await game.close();
}
