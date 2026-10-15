import { ATTACK, RACE } from '../config.js';

/** Couleurs de la descente : bitume, bordures rouge et blanc de L.A., trottoir, pelouses ; une bande sur deux plus sombre. */
const COLORS = {
    light: { grass: '#4f8a3a', walk: '#c8b4a0', curb: '#e8203a', road: '#4a4452', line: '#ffd23f' },
    dark: { grass: '#457e33', walk: '#b8a492', curb: '#f4f0e6', road: '#433d4b', line: null },
};
const FOG = '255,138,110';

/** Agrandissement des décors par rapport aux personnages (un palmier dépasse largement Pete). */
const SIZE = { palm: 2.4, lamp: 1.5, donut: 1.6, gate: 1, cone: 1.4, barrier: 1.4, trash: 1.3, pickup: 1.4 };

/**
 * Dessine la descente en perspective façon Out Run : la route segment par segment, ligne d'écran
 * par ligne d'écran (des pixels nets), puis tout ce qui est dessus, du plus loin au plus près.
 * Ne décide de rien : lit state.race.
 */
export class RoadPainter {
    constructor(ctx, sprites, game) {
        this.ctx = ctx;
        this.sprites = sprites;
        this.game = game;
        this.state = game.state;
        this.width = game.data.width;
        this.height = game.data.height;
        /** Échelle de projection à la profondeur des héros : les sprites y sont à leur taille. */
        this.playerScale = RACE.cameraDepth / RACE.playerDistance;
    }

    draw() {
        const view = this.#road();
        this.#sprites(view);
        this.#progress();
    }

    /** Projette les segments visibles et peint la chaussée ; renvoie les segments projetés (pour les sprites). */
    #road() {
        const { race } = this.state;
        const { road } = race;
        const base = road.at(race.position);
        const camY = road.heightAt(this.game.race.playerZ) + RACE.cameraHeight;
        const basePercent = (race.position % RACE.segment) / RACE.segment;
        const view = [];
        let maxY = this.height;
        let x = 0;
        let dx = -base.curve * basePercent;

        for (let n = 0; n < RACE.drawDistance; n++) {
            const seg = road.segments[base.index + n];
            if (!seg) break;
            const z1 = seg.index * RACE.segment - race.position;
            const p1 = this.#project(x, seg.y1 - camY, z1);
            const p2 = this.#project(x + dx, seg.y2 - camY, z1 + RACE.segment);
            x += dx;
            dx += seg.curve;
            const entry = { seg, p1, p2, clip: maxY, fog: (n / RACE.drawDistance) ** 2 * 0.85 };
            view.push(entry);
            if (z1 <= 1 || p2.y >= p1.y || p2.y >= maxY) continue;
            this.#segment(entry, maxY);
            maxY = p2.y;
        }
        return view;
    }

    #project(x, y, z) {
        const scale = RACE.cameraDepth / Math.max(z, 1);
        return {
            x: this.width / 2 + scale * x * this.width / 2,
            y: this.height / 2 - scale * y * this.height / 2,
            w: scale * RACE.roadWidth * this.width / 2,
            scale,
        };
    }

    /** Une tranche de route, ligne d'écran par ligne d'écran entre ses deux bords. */
    #segment({ seg, p1, p2, fog }, maxY) {
        const { ctx } = this;
        const colors = Math.floor(seg.index / 3) % 2 ? COLORS.dark : COLORS.light;
        const top = Math.max(0, Math.ceil(p2.y));
        const bottom = Math.min(maxY, Math.ceil(p1.y));
        const row = (from, to, y, color) => {
            ctx.fillStyle = color;
            ctx.fillRect(Math.round(from), y, Math.round(to) - Math.round(from), 1);
        };

        for (let y = top; y < bottom; y++) {
            const t = (y - p2.y) / (p1.y - p2.y);
            const cx = p2.x + (p1.x - p2.x) * t;
            const w = p2.w + (p1.w - p2.w) * t;
            row(0, this.width, y, colors.grass);
            row(cx - w * 1.45, cx + w * 1.45, y, colors.walk);
            row(cx - w * 1.08, cx + w * 1.08, y, colors.curb);
            row(cx - w, cx + w, y, colors.road);
            if (colors.line) {
                const lineW = Math.max(1, w * 0.025);
                for (const lane of [-1 / 3, 1 / 3]) row(cx + lane * w - lineW / 2, cx + lane * w + lineW / 2, y, colors.line);
            }
            if (fog > 0.02) row(0, this.width, y, `rgba(${FOG},${fog.toFixed(2)})`);
        }
    }

    /** Décors, obstacles, bonus, ennemis, vinyles et héros, triés du plus loin au plus près. */
    #sprites(view) {
        const { state, game } = this;
        const { race } = state;
        const { roadside, riders } = this.sprites.race;
        const toWorld = (x) => ((x - game.race.centerX) / game.race.halfRoad) * RACE.roadWidth;
        const items = [];
        const add = (z, worldX, draw) => items.push({ z, worldX, draw });

        for (const { seg } of view) {
            const z = seg.index * RACE.segment;
            for (const prop of seg.props) {
                const image = roadside[prop.sprite];
                add(z, prop.x * RACE.roadWidth, (at) => this.#image(image, at, SIZE[prop.sprite.startsWith('sign') ? 'lamp' : prop.sprite] ?? 1, prop.x < 0 && prop.sprite === 'lamp'));
            }
            for (const o of seg.obstacles) {
                if (o.hit) continue;
                const image = roadside[o.sprite] ?? this.sprites.items[o.sprite];
                add(z, o.x * RACE.roadWidth, (at) => this.#image(image, at, SIZE[o.sprite]));
            }
        }
        for (const item of race.pickups) {
            if (item.taken) continue;
            const image = this.sprites.items[item.kind];
            add(item.at * RACE.segment, item.x * RACE.roadWidth, (at) => this.#image(image, at, SIZE.pickup, false, Math.floor(state.tick / 15) % 2));
        }
        for (const r of race.riders) add(r.z, toWorld(r.x), (at) => this.#rider(riders[r.type], r, at));
        for (const shot of race.shots) {
            add(shot.z, toWorld(shot.x), (at) => this.#image(this.sprites.items.vinyl, at, 1, false, shot.y, shot.t * 0.4));
        }
        for (const p of state.players) add(game.race.playerZ - 1, toWorld(p.x), (at) => this.#skater(riders[p.type], p, at));

        items.sort((a, b) => b.z - a.z);
        for (const item of items) {
            const at = this.#locate(view, item.z, item.worldX);
            if (at) item.draw(at);
        }
    }

    /** Position à l'écran et échelle d'un objet du monde, et la ligne sous laquelle une bosse le cache. */
    #locate(view, z, worldX) {
        const { race } = this.state;
        const n = Math.floor(z / RACE.segment) - view[0]?.seg.index;
        const entry = view[n];
        if (!entry || z - race.position < 300) return null;
        const t = (z % RACE.segment) / RACE.segment;
        const scale = entry.p1.scale + (entry.p2.scale - entry.p1.scale) * t;
        return {
            x: entry.p1.x + (entry.p2.x - entry.p1.x) * t + scale * worldX * this.width / 2,
            y: entry.p1.y + (entry.p2.y - entry.p1.y) * t,
            size: scale / this.playerScale,
            clip: entry.clip,
            fog: entry.fog,
        };
    }

    /** Une image posée au sol, agrandie selon la distance, coupée par la bosse qui la cache. */
    #image(image, at, size = 1, flip = false, lift = 0, angle = 0) {
        if (!image) return;
        const { ctx } = this;
        const scale = at.size * size;
        const w = image.width * scale;
        const h = image.height * scale;
        const left = at.x - w / 2;
        const top = at.y - h - lift * at.size;
        const visible = Math.min(h, at.clip - top);
        if (visible <= 0 || w < 1 || left > this.width || left + w < 0) return;

        ctx.save();
        ctx.translate(Math.round(left + w / 2), Math.round(top));
        if (flip) ctx.scale(-1, 1);
        if (angle) {
            ctx.translate(0, h / 2);
            ctx.rotate(Math.round(angle / (Math.PI / 4)) * (Math.PI / 4));
            ctx.translate(0, -h / 2);
        }
        ctx.drawImage(image, 0, 0, image.width, image.height * (visible / h), -Math.round(w / 2), 0, Math.round(w), Math.round(visible));
        ctx.restore();
    }

    #rider(anims, r, at) {
        const frame = this.#riderFrame(anims, r);
        const flashing = r.state === 'wind' && Math.floor(r.t / 4) % 2;
        const image = flashing || (r.state === 'hurt' && r.t < 5) ? frame.flash : frame.normal;
        if (r.isDown) {
            this.#image(image, at, 1, r.vx < 0, 0, r.spin);
            return;
        }
        this.#shadow(at);
        this.#image(image, at, 1, r.dir < 0 && ['wind', 'strike', 'ram'].includes(r.state));
    }

    #riderFrame(anims, r) {
        switch (r.state) {
            case 'wind': return anims.attack[0];
            case 'strike':
            case 'ram': return anims.attack[1];
            case 'hurt':
            case 'crash': return anims.hurt[0];
            default: return anims.ride[Math.floor(r.anim / 8) % 2];
        }
    }

    #skater(anims, p, at) {
        if (p.invuln && !['hurt', 'dead', 'skate'].includes(p.state) && Math.floor(p.invuln / 3) % 2) return;
        if (p.state === 'dead' && p.t > 60 && Math.floor(p.t / 4) % 2) return;
        const timing = ATTACK.hero;
        let frame = anims.ride[Math.floor(p.anim / (p.state === 'skate' ? 4 : 10)) % 2];
        if (p.state === 'attack') frame = anims.attack[p.t >= timing.hitFrom && p.t < timing.strikeUntil ? 1 : 0];
        if (p.state === 'hurt' || p.isDown) frame = anims.hurt[0];
        const image = p.state === 'hurt' && p.t < 5 ? frame.flash : frame.normal;

        this.#shadow(at);
        const angle = p.isDown ? Math.min(1, p.t / 16) * (p.vx < 0 ? -1.6 : 1.6) : 0;
        this.#image(image, at, 1, p.state === 'attack' && p.dir < 0, p.z, angle);
        if (p.state === 'skate') this.#speedLines(at);
    }

    #shadow(at) {
        const w = Math.round(12 * at.size);
        this.ctx.fillStyle = 'rgba(0,0,0,0.35)';
        this.ctx.fillRect(Math.round(at.x - w), Math.round(at.y - 1), w * 2, Math.max(1, Math.round(2 * at.size)));
    }

    #speedLines(at) {
        this.ctx.fillStyle = 'rgba(255,255,255,0.8)';
        for (const [dx, dy, length] of [[-22, -30, 10], [20, -24, 14], [-18, -14, 12], [24, -8, 8]]) {
            this.ctx.fillRect(Math.round(at.x + dx), Math.round(at.y + dy + (this.state.tick % 3)), 1, length);
        }
    }

    /** En bas de l'écran : la distance parcourue jusqu'au skatepark. */
    #progress() {
        const { ctx, game } = this;
        const ratio = Math.min(1, game.race.playerZ / this.state.race.road.finishZ);
        const [left, right, y] = [70, 250, 174];
        ctx.fillStyle = 'rgba(12,10,16,0.7)';
        ctx.fillRect(left - 1, y - 1, right - left + 2, 4);
        ctx.fillStyle = '#ffd23f';
        ctx.fillRect(left, y, Math.round((right - left) * ratio), 2);
        ctx.fillStyle = '#e8203a';
        ctx.fillRect(right - 2, y - 4, 3, 8);
    }
}
