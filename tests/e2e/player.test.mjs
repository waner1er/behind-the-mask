import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Un joueur choisit un morceau, se laisse battre, voit le GAME OVER et continue. */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

test('MORCEAUX : choisir le 2e morceau et le lancer', async () => {
    await game.press('ArrowDown');
    await game.press('Enter');
    assert.equal((await game.state()).mode, 'select');

    await game.press('ArrowRight');
    assert.equal((await game.state()).selected, 1);

    await game.press('Enter', 60);
    const state = await game.state();
    assert.equal(state.mode, 'intro');
    assert.equal(state.level, 1);
});

test('M coupe le son', async () => {
    await game.press('KeyM');
    assert.equal(await game.page.locator('[data-hud=mute]').isHidden(), false);
});

test('sans se défendre : GAME OVER, puis START recommence le niveau', async () => {
    assert.ok(await game.until((s) => s.mode === 'gameover', 20 * 60 * 60), 'game over');
    await game.press('Enter', 120);
    const state = await game.state();
    assert.equal(state.mode, 'intro');
    assert.equal(state.lives, 3);
    assert.deepEqual(game.errors, []);
});
