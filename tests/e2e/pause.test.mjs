import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** START en pleine partie : tout se fige, START relance. */
let game;
before(async () => {
    game = await openGame();
    await game.page.evaluate(() => window.game.campaign.startLevel(0));
    assert.ok(await game.until((s) => s.mode === 'playing', 20 * 60), 'le niveau 1 démarre');
    await game.run(240); // les premiers ennemis arrivent
});
after(() => game?.close());

/** Ce qui doit rester immobile pendant la pause. */
const snapshot = () => game.page.evaluate(() => {
    const s = window.game.state;
    return { tick: s.tick, enemies: s.enemies.map((e) => [e.x, e.y, e.state, e.t]) };
});
const paused = () => game.page.evaluate(() => window.game.pause.paused);

test('START met la partie en pause : rien ne bouge', async () => {
    await game.page.evaluate(() => window.game.hud.message('WAVE 9', 60));
    await game.press('Enter', 2);
    assert.equal(await paused(), true);
    assert.equal(await game.page.locator('.hud__pause').textContent(), 'PAUSE');

    const before = await snapshot();
    await game.hold('ArrowRight', 120); // le joueur ne bouge pas non plus
    assert.deepEqual(await snapshot(), before);
});

test('START relance la partie et rend le message recouvert', async () => {
    await game.press('Enter', 2);
    assert.equal(await paused(), false);
    assert.equal(await game.page.locator('.hud__pause').count(), 0);
    assert.equal(await game.page.locator('[data-hud=message]').textContent(), 'WAVE 9');
    const { tick } = await snapshot();
    await game.run(90);
    assert.ok((await snapshot()).tick > tick, 'le temps reprend');
    assert.equal(await game.page.locator('[data-hud=message]').textContent(), '', 'le message finit de s\'afficher');
});

test('quitter l\'onglet ou l\'app met en pause', async () => {
    await game.page.evaluate(() => {
        Object.defineProperty(document, 'visibilityState', { value: 'hidden', configurable: true });
        document.dispatchEvent(new Event('visibilitychange'));
    });
    assert.equal(await paused(), true);
    await game.press('Enter', 2);
    assert.equal(await paused(), false);
    assert.deepEqual(game.errors, []);
});
