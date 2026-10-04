import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Le mode DÉMO joue tout seul : il doit aller au bout du jeu, sans erreur ni blocage. */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

test('la démo enchaîne l\'intro, les 9 niveaux, la fin et le générique, puis revient à l\'accueil', async () => {
    await game.press('ArrowDown');
    await game.press('ArrowDown');
    await game.press('Enter');
    assert.equal((await game.state()).demo, true);

    assert.ok(await game.until((s) => s.mode === 'playing', 60 * 60), 'le niveau 1 démarre');
    assert.ok(await game.until((s) => s.level === 8 && s.mode === 'playing', 20 * 60 * 60), 'le dernier niveau démarre');
    assert.ok(await game.until((s) => s.mode === 'story', 5 * 60 * 60), 'la scène de fin démarre');
    const end = await game.until((s) => s.mode === 'title', 3 * 60 * 60);
    assert.ok(end, 'retour à l\'accueil après le générique');
    assert.equal(end.demo, false);
    assert.deepEqual(game.errors, []);
});
