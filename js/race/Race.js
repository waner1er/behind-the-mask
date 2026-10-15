import { RACE } from '../config.js';
import { clamp, rand } from '../util/math.js';
import { Rider } from './Rider.js';
import { Road } from './Road.js';

/**
 * La descente en skate (niveaux qui ont une clé « race ») : la route défile, les héros
 * se faufilent et frappent, les ennemis débarquent aux segments prévus par le niveau.
 * Au bout, le skatepark : on repasse en vue de côté pour le boss.
 *
 * Les héros gardent x = position en travers (pixels d'écran) et y = leur ligne à l'écran ;
 * z = hauteur d'un ollie. La caméra reste dans l'axe de la route.
 */
export class Race {
    constructor(game) {
        this.game = game;
        this.state = game.state;
        const { width, height } = game.data;
        this.centerX = width / 2;
        /** Demi-largeur de la chaussée à la profondeur des héros, en pixels d'écran. */
        this.halfRoad = (RACE.cameraDepth / RACE.playerDistance) * RACE.roadWidth * (width / 2);
        /** La ligne de l'écran où roulent les héros. */
        this.playerY = Math.round(height / 2 + (RACE.cameraDepth / RACE.playerDistance) * RACE.cameraHeight * (height / 2));
    }

    start(level) {
        const { state } = this;
        state.race = {
            track: level.race,
            road: new Road(level.race),
            position: 0,
            speed: 0,
            sky: 0,
            riders: [],
            shots: [],
            pickups: level.race.pickups.map((item) => ({ ...item, taken: false })),
            trafficIndex: 0,
            finishing: 0,
        };
        this.game.backdrop.preload(state.levelIndex);
        state.players.forEach((p, i) => {
            Object.assign(p, { x: this.centerX + (state.players.length > 1 ? (i ? 30 : -30) : 0), y: this.playerY, z: 0, vz: 0, dir: 1 });
            p.setState('idle');
        });
    }

    /** Distance parcourue par les héros (devant la caméra). */
    get playerZ() {
        return this.state.race.position + RACE.playerDistance;
    }

    /** Position en travers convertie en largeurs de route (-1 et 1 = les bordures). */
    lane(x) {
        return (x - this.centerX) / this.halfRoad;
    }

    update() {
        const { game, state } = this;
        const { race } = state;
        state.players.forEach((p) => game.skater.update(p));
        this.#drive();
        this.#traffic();
        for (const r of race.riders) game.riderAI.update(r);
        const z = this.playerZ;
        race.riders = race.riders.filter((r) => (r.isDown ? r.t < 90 : true)
            && r.z > z - 14 * RACE.segment && r.z < z + 90 * RACE.segment);
        game.brawl.update();
        this.#finish();
    }

    /** Vitesse commune : HAUT pour pousser, BAS pour freiner, sinon la pente suffit. */
    #drive() {
        const { game, state } = this;
        const { race } = state;
        const riding = state.players.filter((p) => !p.isDown);
        const inputs = riding.map((p) => game.inputOf(p));

        let target = RACE.cruise;
        if (inputs.some((input) => input.axisY < 0)) target = 1;
        else if (inputs.some((input) => input.axisY > 0)) target = RACE.brake;
        if (riding.some((p) => p.state === 'skate')) target = RACE.boost;
        if (riding.some((p) => Math.abs(this.lane(p.x)) > 1.05)) target = Math.min(target, RACE.offRoad);
        if (!riding.length) target = 0;
        if (race.finishing) target = 0.3;

        const wanted = target * RACE.maxSpeed;
        race.speed += clamp(wanted - race.speed, -RACE.decel, RACE.accel);
        race.position += race.speed;

        // dans les virages, la vitesse déporte vers l'extérieur ; le ciel défile avec la route
        const seg = race.road.at(this.playerZ);
        const ratio = race.speed / RACE.maxSpeed;
        for (const p of riding) p.x -= seg.curve * ratio * ratio * RACE.centrifugal;
        race.sky += seg.curve * ratio * 0.6;
    }

    /** Les ennemis prévus par le niveau débarquent quand les héros atteignent leur segment. */
    #traffic() {
        const { game, state } = this;
        const { race } = state;
        const segment = Math.floor(this.playerZ / RACE.segment);
        for (let entry = race.track.traffic[race.trafficIndex]; entry && segment >= entry.at;
            entry = race.track.traffic[++race.trafficIndex]) {
            entry.riders.forEach((type, i) => this.#spawn(type, i));
            if (entry.riders.some((type) => game.data.riders[type].from === 'behind')) {
                game.hud.message('<span class="hud__warning">COPS ARE BEHIND!</span>', 100);
                game.sfx('siren');
            }
        }
    }

    #spawn(type, i) {
        const { game, state } = this;
        const cfg = game.data.riders[type];
        const behind = cfg.from === 'behind';
        const z = this.playerZ + (behind ? -(9 + i * 3) : 45 + i * 6) * RACE.segment;
        const x = this.centerX + rand(-0.7, 0.7) * this.halfRoad;
        state.race.riders.push(new Rider(type, cfg, x, z, cfg.speed * RACE.cruise * RACE.maxSpeed));
    }

    /** L'arche du skatepark : on freine, puis place au boss. */
    #finish() {
        const { game, state } = this;
        const { race } = state;
        if (!race.finishing && this.playerZ >= race.road.finishZ) {
            race.finishing = 1;
            game.hud.message(`SKATEPARK!<br><br><span class="hud__small">${state.level.boss.name} T'ATTEND</span>`, RACE.finishTime);
            game.sfx('clear');
        }
        if (race.finishing && ++race.finishing > RACE.finishTime) this.#enterArena();
    }

    /** Retour en vue de côté, au bout de la rue du niveau : le boss arrive (dernière vague). */
    async #enterArena() {
        const { game, state } = this;
        const { level, levelIndex } = state;
        const cam = level.length - game.data.width;
        const { floor } = game.data;
        Object.assign(state, {
            race: null, cam, locked: false, waveIndex: level.waves.length - 1,
            enemies: [], pickups: [], projectiles: [], pows: [], boxes: [], texts: [], sparks: [],
        });
        state.players.forEach((p) => {
            const lane = state.players.length > 1 ? (p.slot ? 6 : -6) : 0;
            Object.assign(p, { x: cam + 70 - p.slot * 22, y: (floor.min + floor.max) / 2 + lane, z: 0, vz: 0, vx: 0, dir: 1 });
            p.setState('idle');
        });
        game.setMode('loading');
        await game.backdrop.load(levelIndex); // déjà en cache : téléchargé pendant la descente
        game.setMode('playing');
    }
}
