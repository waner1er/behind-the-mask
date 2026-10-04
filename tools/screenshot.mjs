/**
 * Capture l'écran du jeu à un moment donné, pour vérifier un décor ou un sprite.
 *
 *   node tools/screenshot.mjs [niveau 1-9|title|peace] [secondes de jeu] [fichier.png]
 *   node tools/screenshot.mjs 4 6 /tmp/niveau4.png
 */
import { openGame } from '../tests/e2e/harness.mjs';

const [target = '1', seconds = '4', file = 'screenshot.png'] = process.argv.slice(2);
const game = await openGame();
try {
    if (target === 'peace') {
        await game.page.evaluate(() => window.game.story.start(window.game.data.story.ending.slice(1), () => {}));
    } else if (target !== 'title') {
        await game.page.evaluate((n) => window.game.campaign.startLevel(n - 1), Number(target));
    }
    await game.run(Number(seconds) * 60);
    await game.page.locator('.screen').screenshot({ path: file });
    console.log(file);
    if (game.errors.length) console.error(game.errors.join('\n'));
} finally {
    await game.close();
}
