import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** L'inventaire sous la barre de vie : une ligne par objet, celles du WOD et de l'arme seulement si on les a. */
let game;
before(async () => {
    game = await openGame();
    await game.page.evaluate(() => window.game.campaign.startLevel(0));
    assert.ok(await game.until((s) => s.mode === 'playing', 20 * 60), 'le niveau 1 démarre');
});
after(() => game?.close());

const inventory = () => game.page.evaluate(() => [...document.querySelectorAll('[data-hud=inventory] .hud__item')]
    .filter((item) => !item.hidden)
    .map((item) => item.textContent));

test('au départ : vies et vinyles', async () => {
    assert.deepEqual(await inventory(), ['×3', '×5']);
});

test('un couteau ramassé : son nom, sa jauge et les secondes restantes', async () => {
    await game.page.evaluate(() => {
        const { game: g } = window;
        const [p] = g.state.players;
        g.pickups.spawn('weapon', p.x, p.y, { weapon: 'knife' });
        g.pickups.spawn('wod', p.x, p.y);
        g.pickups.collect(p);
    });
    await game.run(60);
    assert.deepEqual(await inventory(), ['×3', '×5', '×1', 'COUTEAU14']);
    const gauge = await game.page.evaluate(() => parseFloat(document.querySelector('[data-hud=inventory] .hud__gauge span').style.width));
    assert.ok(gauge > 90 && gauge < 100, `jauge entamée : ${gauge} %`);
});

test('à la fin du temps, l\'arme disparaît de la liste', async () => {
    await game.run(15 * 60);
    assert.ok(!(await inventory()).some((text) => text.startsWith('COUTEAU')));
    assert.deepEqual(game.errors, []);
});
