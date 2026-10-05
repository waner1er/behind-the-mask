import { PICKUP_LIFETIME } from '../config.js';
import { rand } from '../util/math.js';
import { EffectPainter } from './EffectPainter.js';
import { FighterPainter } from './FighterPainter.js';
import { PixelBrush } from './PixelBrush.js';
import { StoryPainter } from './StoryPainter.js';
import { WeatherPainter } from './WeatherPainter.js';

/**
 * Dessine une image du jeu : fait défiler le décor SVG, puis peint les acteurs sur le canvas,
 * triés par profondeur (y), et les effets par-dessus.
 */
export class Renderer {
    /** Le canvas est en résolution x2 : on dessine en coordonnées d'écran (320 x 180). */
    static SCALE = 2;

    constructor(game, canvas) {
        const { state, data, sprites } = game;
        this.game = game;
        this.state = state;
        this.sprites = sprites;
        this.width = data.width;
        this.height = data.height;
        this.ctx = canvas.getContext('2d');
        this.ctx.imageSmoothingEnabled = false;
        this.brush = new PixelBrush(this.ctx);
        this.fighters = new FighterPainter(this.ctx, sprites);
        this.effects = new EffectPainter(this.ctx, this.brush, state, this.width);
        this.weather = new WeatherPainter(this.ctx, this.brush, state, this.width);
        this.story = new StoryPainter(this.ctx, this.brush, state);
    }

    draw() {
        const { ctx, state } = this;
        const shake = state.shake > 0 ? Math.round(rand(-state.shake, state.shake)) : 0;
        state.shake = Math.max(0, state.shake - 0.4);
        this.game.backdrop.scroll(state.cam, shake);

        ctx.setTransform(Renderer.SCALE, 0, 0, Renderer.SCALE, 0, 0);
        ctx.imageSmoothingEnabled = false;
        ctx.clearRect(0, 0, this.width, this.height);
        ctx.save();
        ctx.translate(-Math.round(state.cam) + shake, 0);

        const inStory = state.mode === 'story';
        this.#hostages();
        this.#pickups();
        if (inStory && this.game.story.scene === 'mask') this.story.dataCenter();
        this.#mysteryBoxes();
        this.#actors();
        this.#projectiles();
        state.explosions.forEach((ex) => this.effects.explosion(ex));
        if (inStory) this.story.extras();
        state.notes.forEach((note) => this.effects.note(note));
        state.sparks.forEach((s) => this.effects.spark(s));
        state.texts.forEach((t) => this.effects.text(t));
        ctx.restore();

        this.weather.draw();
        this.#whiteFlash();
        this.game.hud.update();
    }

    #hostages() {
        for (const pow of this.state.pows) {
            const frame = this.sprites.fighters.pow.tied[Math.floor(pow.t / 20) % 2].normal;
            const hop = pow.freed ? Math.abs(Math.sin(pow.t / 5)) * 6 : 0;
            this.brush.shadow(pow.x, pow.y, 7);
            this.brush.standing(frame, pow.x, pow.y, { flip: pow.freed, hop });
        }
    }

    #pickups() {
        for (const item of this.state.pickups) {
            const lifetime = PICKUP_LIFETIME[item.kind] ?? PICKUP_LIFETIME.default;
            if (item.t > lifetime - 120 && item.t % 6 < 3) continue; // clignote avant de disparaître
            const image = item.kind === 'weapon' ? this.sprites.weaponIcons[item.weapon] : this.sprites.items[item.kind];
            const bob = item.z > 0 ? 0 : Math.floor(item.t / 20) % 2;
            this.brush.shadow(item.x, item.y, Math.max(4, image.width / 2));
            this.brush.spinning(image, item.x, item.y - item.z - image.height / 2 - 1 - bob, item.z > 0 ? item.t * 0.35 : 0);
        }
    }

    #mysteryBoxes() {
        const image = this.sprites.items.wodbox;
        for (const box of this.state.boxes) {
            if (box.hp <= 0) continue;
            const wobble = box.shake > 0 ? Math.round(rand(-1, 1)) : 0;
            box.shake = Math.max(0, box.shake - 1);
            this.brush.shadow(box.x, box.y, 7);
            this.ctx.drawImage(image, Math.round(box.x - image.width / 2) + wobble, Math.round(box.y - image.height + 1));
        }
    }

    #actors() {
        const { state } = this;
        const actors = [...state.enemies, ...state.cast, ...state.toughGuys, ...state.players]
            .filter(Boolean)
            .sort((a, b) => a.y - b.y);
        actors.forEach((a) => this.brush.shadow(a.x, a.y, 10 * a.scale));
        actors.forEach((a) => this.fighters.draw(a));
    }

    #projectiles() {
        for (const shot of this.state.projectiles) {
            const image = this.sprites.weaponIcons[shot.sprite] ?? this.sprites.items[shot.sprite];
            this.brush.shadow(shot.x, shot.y, 3);
            this.brush.spinning(image, shot.x, shot.y - shot.z - image.height / 2, shot.spin ? shot.t * 0.4 : 0);
        }
    }

    #whiteFlash() {
        const { state } = this;
        if (state.flash <= 0) return;
        this.ctx.fillStyle = `rgba(255,255,255,${state.flash / 16})`;
        this.ctx.fillRect(0, 0, this.width, this.height);
        state.flash--;
    }
}
