import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { openGame } from './harness.mjs';

/** Vibrations : on espionne le pont iOS (window.webkit) et le vibreur d'une manette simulée. */
let game;
before(async () => {
    game = await openGame();
    await game.page.evaluate(() => {
        window.__phone = [];
        window.webkit = { messageHandlers: { haptics: { postMessage: (style) => window.__phone.push(style) } } };
        window.__pads = [];
        navigator.getGamepads = () => window.__pads;
        window.game.campaign.startLevel(0);
    });
    assert.ok(await game.until((s) => s.mode === 'playing', 20 * 60), 'le niveau 1 démarre');
});
after(() => game?.close());

const hurtPete = () => game.page.evaluate(() => {
    const { game: g } = window;
    const [pete] = g.state.players;
    pete.invuln = 0;
    g.combat.damagePlayer(pete, 1, pete.x + 10);
    g.state.tick++; // une vibration par image au plus
});

test('sans manette, un coup reçu fait vibrer l\'iPhone', async () => {
    await hurtPete();
    assert.deepEqual(await game.page.evaluate(() => window.__phone), ['heavy']);
});

test('avec une manette, c\'est elle qui vibre, pas le téléphone', async () => {
    await game.page.evaluate(() => {
        window.__rumbles = [];
        window.__pads = [{
            index: 0, connected: true, mapping: 'standard', axes: [0, 0, 0, 0],
            buttons: Array.from({ length: 17 }, () => ({ pressed: false })),
            vibrationActuator: { playEffect: (type, params) => { window.__rumbles.push([type, params.duration]); return Promise.resolve(); } },
        }];
        window.__phone = [];
    });
    await hurtPete();
    assert.deepEqual(await game.page.evaluate(() => window.__rumbles), [['dual-rumble', 200]]);
    assert.deepEqual(await game.page.evaluate(() => window.__phone), []);
    assert.deepEqual(game.errors, []);
});
