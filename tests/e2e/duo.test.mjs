import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** À deux sur un clavier : Pete joue avec ZQSD + V C X W, VigiBapt avec OKLM + , ; : ! */
let game;
before(async () => {
    game = await openGame();
});
after(() => game?.close());

test('2 JOUEURS : Pete et VigiBapt entrent dans le niveau 1', async () => {
    await game.press('ArrowDown');
    await game.press('Enter');
    assert.equal((await game.page.evaluate(() => window.game.state.duo)), true);

    assert.ok(await game.until((s) => s.mode === 'story', 10 * 60), 'l\'intro démarre');
    // START fait avancer l'intro plan par plan
    for (let i = 0; i < 60 && (await game.state()).mode !== 'playing'; i++) await game.press('Enter', 120);
    assert.equal((await game.state()).mode, 'playing', 'le niveau 1 démarre');

    const players = await game.players();
    assert.deepEqual(players.map((p) => p.type), ['hero', 'bapt']);
    assert.deepEqual((await game.state()).lives, [3, 3]);
    assert.equal(await game.page.locator('[data-hud=p2]').isHidden(), false);
});

test('chaque joueur a son pavé', async () => {
    const held = () => game.page.evaluate(() => window.game.pads.map((pad) => [...pad.held]));
    await game.run(60); // START passe l'intro d'un coup : les deux héros finissent de se placer

    // les héros ne prennent pas de coups pendant ce test : on ne teste que les commandes
    // (les ennemis restent, le test suivant en a besoin pour le game over)
    await game.page.evaluate(() => window.game.state.players.forEach((p) => { p.invuln = 99999; }));
    const [, bapt] = await game.players();
    await game.page.keyboard.down('KeyL'); // « L » : 2P descend
    await game.run(30);
    assert.deepEqual(await held(), [[], ['ArrowDown']]);
    assert.ok((await game.players())[1].y > bapt.y, 'VigiBapt descend');
    await game.page.keyboard.up('KeyL');

    const [pete] = await game.players();
    await game.page.keyboard.down('KeyA'); // « Q » : 1P recule
    await game.run(30);
    assert.deepEqual(await held(), [['ArrowLeft'], []]);
    assert.ok((await game.players())[0].x < pete.x, 'Pete recule');
    await game.page.keyboard.up('KeyA');

    await game.page.keyboard.press('KeyM'); // « , » : 2P frappe (et ne coupe pas le son)
    await game.run(2);
    assert.equal((await game.players())[1].state, 'attack');
    assert.equal(await game.page.locator('[data-hud=mute]').isHidden(), true);

    await game.page.keyboard.press('KeyV'); // « V » : 1P frappe
    await game.run(2);
    assert.equal((await game.players())[0].state, 'attack');
    await game.page.evaluate(() => window.game.state.players.forEach((p) => { p.invuln = 0; }));
    assert.deepEqual(game.errors, []);
});

test('sans se défendre : chacun perd ses vies, puis GAME OVER', async () => {
    assert.ok(await game.until((s) => s.mode === 'gameover', 30 * 60 * 60), 'game over');
    assert.deepEqual((await game.state()).lives, [0, 0]);
    assert.deepEqual(game.errors, []);
});
