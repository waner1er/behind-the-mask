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
    // les commandes du clavier, WALL OF DEATH en évidence
    assert.equal(await game.page.locator('.hud__control--wod').first().textContent(), 'ESPACE + V');

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

test('menu de pause : RECOMMENCER LE NIVEAU repart de zéro', async () => {
    await game.page.evaluate(() => { window.game.state.score = 1234; });
    await game.press('Enter', 2);
    await game.press('ArrowDown', 2);
    await game.press('Enter', 2);
    assert.equal(await paused(), false);
    assert.ok(await game.until((s) => s.mode === 'playing', 20 * 60), 'le niveau redémarre');
    const state = await game.state();
    assert.equal(state.level, 0);
    assert.equal(state.score, 0);
});

test('menu de pause : RETOUR A L\'ACCUEIL', async () => {
    await game.press('Enter', 2);
    await game.press('ArrowUp', 2); // le menu boucle : de REPRENDRE, haut va au dernier choix
    await game.press('Enter', 10);
    assert.equal((await game.state()).mode, 'title');
    assert.equal(await paused(), false);
});

test('scénario : START le passe d\'un coup, ESPACE avance plan par plan', async () => {
    await game.press('Enter'); // JOUER
    assert.ok(await game.until((s) => s.mode === 'story', 10 * 60), 'l\'intro démarre');
    assert.equal(await game.page.locator('.hud__skip').textContent(), 'START : PASSER');

    await game.run(60);
    await game.press('Space', 10);
    assert.equal((await game.state()).mode, 'story', 'ESPACE ne fait qu\'avancer');

    await game.press('Enter', 2);
    assert.notEqual((await game.state()).mode, 'story', 'START passe toute l\'intro');
    assert.ok(await game.until((s) => s.mode === 'playing', 20 * 60), 'le niveau 1 démarre');
    assert.deepEqual(game.errors, []);
});
