import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Le mode DÉMO joue tout seul : il doit traverser l'intro et enchaîner les niveaux sans erreur. */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

test('la démo passe l\'intro puis termine le premier niveau', async () => {
    await game.press('ArrowDown');
    await game.press('ArrowDown');
    await game.press('Enter');
    assert.equal((await game.state()).demo, true);

    assert.ok(await game.until((s) => s.mode === 'playing', 60 * 60), 'le niveau 1 démarre');
    assert.ok(await game.until((s) => s.level === 1, 3 * 60 * 60), 'le niveau 2 démarre');
    assert.deepEqual(game.errors, []);
});

test('le dernier boss vaincu, la fin et le générique ramènent à l\'accueil', async () => {
    await game.page.evaluate(() => window.game.campaign.startLevel(8));
    assert.ok(await game.until((s) => s.mode === 'story', 4 * 60 * 60), 'la scène de fin démarre');
    assert.ok(await game.until((s) => s.mode === 'title', 3 * 60 * 60), 'retour à l\'accueil après le générique');
    assert.deepEqual(game.errors, []);
});
