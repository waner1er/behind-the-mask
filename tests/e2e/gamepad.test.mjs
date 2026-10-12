import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Deux manettes simulées (disposition standard) : on remplace navigator.getGamepads. */
let game;
before(async () => {
    game = await openGame();
    await game.page.evaluate(() => {
        const pad = (index) => ({
            index,
            connected: true,
            mapping: 'standard',
            buttons: Array.from({ length: 17 }, () => ({ pressed: false })),
            axes: [0, 0, 0, 0],
        });
        window.__pads = [pad(0), pad(1)];
        navigator.getGamepads = () => window.__pads;
    });
});
after(() => game?.close());

/** Appuie sur un bouton de la manette n, le temps de quelques images. */
async function button(pad, i, frames = 20) {
    await game.page.evaluate(([p, b]) => { window.__pads[p].buttons[b].pressed = true; }, [pad, i]);
    await game.run(2);
    await game.page.evaluate(([p, b]) => { window.__pads[p].buttons[b].pressed = false; }, [pad, i]);
    await game.run(frames);
}

/** Pousse le stick gauche de la manette n pendant quelques images. */
async function stick(pad, x, y, frames) {
    await game.page.evaluate(([p, ax, ay]) => { window.__pads[p].axes = [ax, ay, 0, 0]; }, [pad, x, y]);
    await game.run(frames);
    await game.page.evaluate((p) => { window.__pads[p].axes = [0, 0, 0, 0]; }, pad);
    await game.run(2);
}

test('à la manette : croix pour le menu, START pour 2 JOUEURS', async () => {
    await button(0, 13); // croix bas
    await button(0, 9); // START
    assert.equal(await game.page.evaluate(() => window.game.state.duo), true);
    assert.equal(await game.page.evaluate(() => document.documentElement.classList.contains('has-gamepad')), true);

    assert.ok(await game.until((s) => s.mode === 'story', 10 * 60), 'l\'intro démarre');
    for (let i = 0; i < 60 && (await game.state()).mode !== 'playing'; i++) await button(1, 9, 120);
    assert.equal((await game.state()).mode, 'playing', 'START de la 2e manette fait aussi avancer');
});

test('chaque manette commande son héros', async () => {
    const held = () => game.page.evaluate(() => window.game.pads.map((pad) => [...pad.held]));

    // personne pour bousculer ou frapper les héros : on ne teste que les commandes
    await game.page.evaluate(() => {
        const { state } = window.game;
        state.enemies = [];
        state.players.forEach((p) => { p.invuln = 99999; });
    });
    const [, bapt] = await game.players();
    await game.page.evaluate(() => { window.__pads[1].axes = [0, 1, 0, 0]; }); // manette 2 : stick bas
    await game.run(30);
    assert.deepEqual(await held(), [[], ['ArrowDown']]);
    assert.ok((await game.players())[1].y > bapt.y, 'VigiBapt descend');
    await stick(1, 0, 0, 0);

    const [pete] = await game.players();
    await game.page.evaluate(() => { window.__pads[0].axes = [-1, 0, 0, 0]; }); // manette 1 : stick gauche
    await game.run(30);
    assert.deepEqual(await held(), [['ArrowLeft'], []]);
    assert.ok((await game.players())[0].x < pete.x, 'Pete recule');
    await stick(0, 0, 0, 0);

    await button(1, 0, 0); // A de la manette 2 : katana
    assert.equal((await game.players())[1].state, 'attack');
    await button(0, 0, 0); // A de la manette 1
    assert.equal((await game.players())[0].state, 'attack');
});

test('une manette débranchée lâche ses touches', async () => {
    await game.page.evaluate(() => { window.__pads[0].axes = [1, 0, 0, 0]; });
    await game.run(10);
    assert.equal(await game.page.evaluate(() => window.game.pads[0].isHeld('ArrowRight')), true);

    await game.page.evaluate(() => { window.__pads = [null, window.__pads[1]]; });
    await game.run(2);
    assert.equal(await game.page.evaluate(() => window.game.pads[0].isHeld('ArrowRight')), false);
    assert.deepEqual(game.errors, []);
});
