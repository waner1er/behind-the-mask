import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Niveau 7 : la descente en skate vue de dos, puis le boss en vue de côté au skatepark. */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

const skater = () => game.page.evaluate(() => {
    const { state } = window.game;
    const [p] = state.players;
    return { x: p.x, z: p.z, state: p.state, position: state.race?.position ?? null };
});

test('le niveau 7 commence par la descente', async () => {
    await game.page.evaluate(() => window.game.campaign.startLevel(6));
    assert.ok(await game.until((s) => s.mode === 'race', 10 * 60), 'la course démarre après le titre');
    const start = await skater();
    await game.run(60);
    assert.ok((await skater()).position > start.position, 'la route défile');
});

test('GAUCHE pour se faufiler, B pour un ollie, ESPACE pour un coup de katana', async () => {
    const before = await skater();
    await game.hold('ArrowLeft', 20);
    assert.ok((await skater()).x < before.x - 10, 'Pete se déporte à gauche');

    await game.page.keyboard.press('KeyB');
    await game.run(8);
    assert.ok((await skater()).z > 0, 'la planche décolle');
    await game.run(60);

    await game.page.keyboard.press('Space');
    await game.run(3);
    assert.equal((await skater()).state, 'attack');
});

test('au bout de la rue, le skatepark : retour en vue de côté face au boss', async () => {
    await game.page.evaluate(() => {
        const { state } = window.game;
        state.race.position = state.race.road.finishZ - 1200;
        state.players[0].invuln = 9999;
    });
    const arena = await game.until((s) => s.mode === 'playing', 15 * 60);
    assert.ok(arena, 'le combat de boss commence');
    await game.run(120);
    const boss = await game.page.evaluate(() => ({ race: window.game.state.race, sprite: window.game.state.boss?.type }));
    assert.equal(boss.race, null);
    assert.equal(boss.sprite, 'bonebreaker');
    assert.deepEqual(game.errors, []);
});
