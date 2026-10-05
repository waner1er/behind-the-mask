/*
 * VIGILANTE - BEHIND THE MASK
 * Beat'em up façon borne d'arcade : un niveau par morceau de l'album, un boss au bout de chaque rue.
 * PHP génère le décor et les sprites (voir src/) ; ce module les anime. Architecture : docs/javascript.md.
 */
import { GameLoop } from './core/GameLoop.js';
import { Game } from './Game.js';
import { KeyboardControls } from './input/KeyboardControls.js';
import { MobileGuard } from './input/MobileGuard.js';
import { TouchStick } from './input/TouchStick.js';

const data = JSON.parse(document.getElementById('game-data').textContent);
const game = new Game(data);

new KeyboardControls(game.input, game.pads, () => game.state.duo).bind();
document.querySelectorAll('[data-joystick], [data-touch-stick]').forEach((pad) => new TouchStick(pad, game.input).bind());
new MobileGuard().bind();
game.audio.unlockOnGesture();

// index.php?debug : le jeu est accessible dans la console (window.game)
if (new URLSearchParams(location.search).has('debug')) window.game = game;

const loop = new GameLoop(() => game.update(), () => game.draw());
document.fonts.load('8px "Press Start 2P"').finally(() => loop.start());
