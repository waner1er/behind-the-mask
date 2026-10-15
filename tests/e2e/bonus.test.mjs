import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Après le boss de Beer Church : le niveau bonus, Pete ivre contre le conteneur à verre. */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

const bonus = () => game.page.evaluate(() => {
    const { state } = window.game;
    return { mode: state.mode, level: state.levelIndex, hp: state.bonus?.hp, drunk: state.players[0]?.drunk };
});

test('MISSION COMPLETE à Beer Church, puis le niveau bonus', async () => {
    await game.page.evaluate(() => window.game.campaign.startLevel(3));
    await game.run(260);
    await game.page.evaluate(() => window.game.campaign.levelClear());
    assert.ok(await game.until((s) => s.mode === 'bonus', 10 * 60), 'le niveau bonus démarre');
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

test('TIME OVER : on passe quand même au niveau suivant, dessoûlé', async () => {
    await game.page.evaluate(() => { window.game.state.bonus.time = 1; });
    const next = await game.until((s) => s.mode === 'intro' && s.level === 4, 10 * 60);
    assert.ok(next, 'le niveau 5 démarre');
    assert.equal((await bonus()).drunk, false);
    assert.deepEqual(game.errors, []);
});
