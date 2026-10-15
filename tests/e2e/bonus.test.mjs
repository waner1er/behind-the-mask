import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Beer Church : tout le niveau, c'est Pete ivre contre le conteneur à verre (façon Street Fighter II). */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

const bonus = () => game.page.evaluate(() => {
    const { state } = window.game;
    return { mode: state.mode, level: state.levelIndex, hp: state.bonus?.hp, drunk: state.players[0]?.drunk };
});

test('Beer Church commence directement par la casse du conteneur', async () => {
    await game.page.evaluate(() => window.game.campaign.startLevel(3));
    assert.ok(await game.until((s) => s.mode === 'bonus', 10 * 60), 'la casse démarre après le titre');
    assert.equal(await game.page.evaluate(() => window.game.state.enemies.length), 0, 'pas de vagues');
    const start = await bonus();
    assert.equal(start.drunk, true, 'Pete est ivre');
    assert.ok(start.hp > 0);
});

test('un coup de katana entame le conteneur', async () => {
    const before = await bonus();
    await game.page.evaluate(() => {
        const { state } = window.game;
        const p = state.players[0];
        Object.assign(p, { x: state.bonus.x - 34, y: state.bonus.y, dir: 1, hiccup: 999 });
    });
    await game.press('Space', 24);
    assert.ok((await bonus()).hp < before.hp);
});

test('TIME OVER : MISSION COMPLETE quand même, puis le niveau 5, dessoûlé', async () => {
    await game.page.evaluate(() => { window.game.state.bonus.time = 1; });
    assert.ok(await game.until((s) => s.mode === 'loading', 15 * 60), 'MISSION COMPLETE, puis chargement du niveau 5');
    // le décor du niveau 5 se télécharge en temps réel (plus lent sur le serveur d'intégration)
    await game.page.waitForFunction(() => window.game.state.mode !== 'loading', null, { timeout: 60000, polling: 100 });
    const next = await game.until((s) => s.mode === 'intro' && s.level === 4, 2 * 60);
    assert.ok(next, 'le niveau 5 démarre');
    assert.equal((await bonus()).drunk, false);
    assert.deepEqual(game.errors, []);
});
