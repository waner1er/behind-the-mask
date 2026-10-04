'use strict';

/*
 * VIGILANTE - BEHIND THE MASK
 * Beat'em up façon borne d'arcade : un niveau par morceau de l'album,
 * le morceau en musique de fond et un boss dédié à la fin.
 *
 * PHP génère le décor (SVG, chargé niveau par niveau via scene.php) et les
 * sprites (grilles de pixels envoyées en JSON) ; ce script les anime.
 * La boucle tourne à 60 images/s fixes, comme une vraie borne.
 */
(() => {
    const data = JSON.parse(document.getElementById('game-data').textContent);
    const { width: W, height: H, floor, levelLength } = data;
    const ANCHOR = data.sprites.anchor;

    const svg = document.querySelector('.screen__scene');
    const canvas = document.querySelector('.screen__actors');
    const ctx = canvas.getContext('2d');
    const hud = Object.fromEntries([...document.querySelectorAll('[data-hud]')].map((el) => [el.dataset.hud, el]));
    const joystick = document.querySelector('[data-joystick]');
    let layers = readLayers();

    const PAPER = '#ece8dc';

    // Timings des attaques (en images)
    const HERO_ATTACK = { hitFrom: 5, hitTo: 9, strikeUntil: 14, end: 20 };
    const ENEMY_ATTACK = { hitFrom: 26, hitTo: 29, strikeUntil: 38, end: 46 };
    const BOSS_ATTACK = { hitFrom: 18, hitTo: 22, strikeUntil: 30, end: 38 };
    const SKATE = { duration: 34, speed: 3.2, cooldown: 70 };
    const JUMP = { impulse: 3.4, gravity: 0.22, speed: 1.7 };
    const BOMB = { perCrate: 5, max: 20, radius: 30, damage: 4 };

    const rand = (min, max) => min + Math.random() * (max - min);
    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
    const pick = (list) => list[Math.floor(Math.random() * list.length)];
    const pad = (n, size = 6) => String(n).padStart(size, '0');

    // -----------------------------------------------------------------------
    // Sprites : grilles de texte -> petits canvas (normal + version "flash" blanche)
    // -----------------------------------------------------------------------
    function gridToCanvas(grid, palette, tint = null) {
        const sprite = document.createElement('canvas');
        sprite.width = grid[0].length;
        sprite.height = grid.length;
        const g = sprite.getContext('2d');

        grid.forEach((row, y) => {
            for (let x = 0; x < row.length; x++) {
                const color = tint ?? palette[row[x]];
                if (row[x] === '.' || !color) continue;
                g.fillStyle = color;
                g.fillRect(x, y, 1, 1);
            }
        });

        return sprite;
    }

    const sprites = {};
    for (const [type, sprite] of Object.entries(data.sprites)) {
        if (type === 'anchor' || type === 'weaponIcons') continue;
        sprites[type] = {};
        for (const [anim, list] of Object.entries(sprite.frames)) {
            sprites[type][anim] = list.map((grid) => ({
                normal: gridToCanvas(grid, sprite.palette),
                flash: gridToCanvas(grid, sprite.palette, '#ffffff'),
            }));
        }
    }

    const items = {};
    for (const [name, grid] of Object.entries(data.items.sprites)) {
        items[name] = gridToCanvas(grid, data.items.palette);
    }

    // armes au sol
    const weaponIcons = {};
    for (const [name, grid] of Object.entries(data.sprites.weaponIcons.sprites)) {
        weaponIcons[name] = gridToCanvas(grid, data.sprites.weaponIcons.palette);
    }

    // -----------------------------------------------------------------------
    // Clavier (et boutons de la borne cliquables)
    // -----------------------------------------------------------------------
    const GAME_KEYS = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Space', 'KeyX', 'KeyB', 'KeyV', 'KeyC', 'Enter', 'KeyM'];
    const keys = new Set();    // touches maintenues
    const pressed = new Set(); // touches enfoncées depuis la dernière image

    function press(code) {
        if (!keys.has(code)) pressed.add(code);
        keys.add(code);
    }

    addEventListener('keydown', (e) => {
        if (!GAME_KEYS.includes(e.code)) return;
        e.preventDefault();
        press(e.code);
    });
    addEventListener('keyup', (e) => keys.delete(e.code));
    addEventListener('blur', () => keys.clear());

    document.querySelectorAll('[data-key]').forEach((button) => {
        button.addEventListener('pointerdown', () => press(button.dataset.key));
        button.addEventListener('pointerup', () => keys.delete(button.dataset.key));
        button.addEventListener('pointerleave', () => keys.delete(button.dataset.key));
    });

    // -----------------------------------------------------------------------
    // Son : la musique du niveau + petits bips 8-bit (Web Audio API)
    // -----------------------------------------------------------------------
    // Bruitages : voir js/sfx.js
    const sfx = (name, ...args) => window.Sfx?.play(name, ...args);
    let muted = false;

    function playTrack(level, fromStart = true) {
        if (!window.Sfx || !level.audio) return;
        if (fromStart) Sfx.stopMusic();
        Sfx.playMusic(level.audio);
    }

    function toggleMute() {
        muted = !muted;
        window.Sfx?.setMuted(muted);
        hud.mute.hidden = !muted;
    }

    // -----------------------------------------------------------------------
    // Décor : chargé à la demande pour chaque niveau
    // -----------------------------------------------------------------------
    function readLayers() {
        return [...svg.querySelectorAll('.layer')].map((el) => ({ el, factor: parseFloat(el.dataset.factor) }));
    }

    const sceneCache = new Map([[0, svg.innerHTML]]);
    let currentScene = 0;

    async function loadScene(index) {
        if (currentScene === index) return;
        currentScene = index;
        if (!sceneCache.has(index)) {
            const response = await fetch(data.sceneUrl.replace('%d', index));
            sceneCache.set(index, await response.text());
        }
        if (currentScene !== index) return; // une autre scène a été demandée entre-temps
        svg.innerHTML = sceneCache.get(index);
        layers = readLayers();
    }

    // -----------------------------------------------------------------------
    // État du jeu
    // -----------------------------------------------------------------------
    let nextId = 1;
    const state = {
        mode: 'title', // title | loading | intro | playing | clear | gameover | ending
        tick: 0,
        modeTimer: 0,
        selected: 0,
        levelIndex: 0,
        level: data.levels[0],
        cam: 0,
        locked: false,
        waveIndex: 0,
        score: 0,
        hiscore: loadHiscore(),
        lives: 3,
        player: null,
        enemies: [],
        projectiles: [],
        pickups: [],
        explosions: [],
        pows: [],
        particles: [],
        flashes: [],
        sparks: [],
        texts: [],
        rain: Array.from({ length: 90 }, () => ({ x: rand(0, W), y: rand(0, H) })),
        boss: null,
        shake: 0,
        flash: 0,
        messageUntil: 0,
        lastEnemy: null,
        lastEnemyUntil: 0,
    };

    function loadHiscore() {
        try {
            return parseInt(localStorage.getItem('vigilante-hiscore') ?? '0', 10) || 0;
        } catch {
            return 0;
        }
    }

    function saveHiscore() {
        try {
            localStorage.setItem('vigilante-hiscore', String(state.hiscore));
        } catch {
            // stockage indisponible
        }
    }

    function createFighter(type, x, y, cfg = null) {
        cfg ??= type === 'hero' ? { hp: 100 } : data.enemies[type];
        return {
            id: nextId++, type, cfg, x, y, dir: 1, state: 'idle', t: 0, anim: 0,
            hp: cfg.hp, maxHp: cfg.hp, vx: 0, invuln: 0, cooldown: rand(30, 90),
            special: cfg.every ?? 0, skateCooldown: 0, hits: new Set(), landed: false, boss: false,
            scale: cfg.scale ?? 1,
            weapon: null, weaponUntil: 0, // arme ramassée (héros)
            z: 0, vz: 0, bombs: 0,         // hauteur du saut, bombes en réserve
        };
    }

    function setState(fighter, name) {
        if (fighter.state === name) return;
        fighter.state = name;
        fighter.t = 0;
    }

    function setMode(mode) {
        state.mode = mode;
        state.modeTimer = 0;
    }

    function message(html, frames = Infinity) {
        hud.message.innerHTML = html;
        state.messageUntil = state.tick + frames;
    }

    function shout(text, x, y, color = null) {
        state.texts.push({ text, x, y, t: 0, color });
    }

    // -----------------------------------------------------------------------
    // Écran titre : choix du morceau
    // -----------------------------------------------------------------------
    function showTitle() {
        const level = data.levels[state.selected];
        message(
            `<span class="hud__small">SELECT TRACK</span><br>`
            + `<span class="hud__track">◀ ${pad(level.number, 2)} ${level.title} ▶</span>`
            + (level.feat ? `<br><span class="hud__small">FEAT. ${level.feat}</span>` : '')
            + `<br><br><span class="blink-text">INSERT COIN · PRESS ENTER</span>`
        );
        hud.level.textContent = 'HI-SCORE';
    }

    function selectTrack(delta) {
        state.selected = (state.selected + delta + data.levels.length) % data.levels.length;
        showTitle();
        loadScene(state.selected);
        playTrack(data.levels[state.selected]);
        sfx('select');
    }

    function updateTitle() {
        if (!state.player) state.player = createFighter('hero', 150, 158);
        state.cam += 0.5;
        state.player.x = state.cam + 150;
        state.player.anim++;
        setState(state.player, 'walk');

        if (state.modeTimer === 1) showTitle();
        if (pressed.has('ArrowRight')) selectTrack(1);
        if (pressed.has('ArrowLeft')) selectTrack(-1);
        if (pressed.has('Enter')) {
            state.score = 0;
            state.lives = 3;
            startLevel(state.selected);
        }
    }

    // -----------------------------------------------------------------------
    // Niveaux
    // -----------------------------------------------------------------------
    async function startLevel(index) {
        setMode('loading');
        message('LOADING...');
        await loadScene(index);

        const level = data.levels[index];
        Object.assign(state, {
            levelIndex: index, level, cam: 0, locked: false, waveIndex: 0,
            enemies: [], projectiles: [], pickups: [], explosions: [], particles: [], flashes: [],
            sparks: [], texts: [], boss: null, lastEnemy: null,
            pows: level.pows.map((x) => ({ x, y: floor.min + 1, freed: false, t: 0 })),
        });
        state.player = createFighter('hero', 70, 158);
        hud.go.hidden = true;
        hud.level.textContent = `LEVEL ${level.number}`;
        document.documentElement.style.setProperty('--accent', level.accent);

        message(
            `<span class="hud__small">MISSION ${level.number} · START!</span><br>`
            + `<span class="hud__track">${level.title}</span>`
            + (level.feat ? `<br><span class="hud__small">FEAT. ${level.feat}</span>` : '')
            + `<br><br><span class="hud__lyrics">${level.intro.join('<br>')}</span>`
        );
        playTrack(level);
        window.Sfx?.preload(data.levels[index + 1]?.audio);
        sfx('start');
        setMode('intro');
    }

    function levelClear() {
        const bonus = state.player.hp * 10 + 1000;
        state.score += bonus;
        setMode('clear');
        hud.go.hidden = true;
        message(`MISSION COMPLETE!<br><br><span class="hud__small">BONUS ${pad(bonus)}</span>`);
        sfx('clear');
    }

    function nextLevel() {
        if (state.levelIndex + 1 >= data.levels.length) {
            setMode('ending');
            return;
        }
        startLevel(state.levelIndex + 1);
    }

    function updateEnding() {
        const lineDuration = 200;
        const step = Math.floor(state.modeTimer / lineDuration);

        if (state.modeTimer % lineDuration === 1) {
            if (step < data.ending.length) {
                message(`<span class="hud__lyrics">${data.ending[step]}</span>`);
            } else if (step === data.ending.length) {
                const links = data.links.map((l) => `<a href="${l.url}" target="_blank" rel="noopener">${l.name}</a>`).join(' · ');
                message(
                    `THANKS FOR PLAYING<br><br><span class="hud__track">VIGILANTE</span><br>`
                    + `<span class="hud__small">BEHIND THE MASK</span><br><br>`
                    + `<span class="hud__links">${links}</span><br><br>`
                    + `<span class="hud__small">SCORE ${pad(state.score)}</span><br><span class="blink-text">PRESS ENTER</span>`
                );
            }
        }
        if (step >= data.ending.length && pressed.has('Enter')) {
            state.player = null;
            setMode('title');
        }
    }

    // -----------------------------------------------------------------------
    // Le héros
    // -----------------------------------------------------------------------
    function updatePlayer(p) {
        p.anim++;
        p.invuln = Math.max(0, p.invuln - 1);
        p.skateCooldown = Math.max(0, p.skateCooldown - 1);

        // l'arme ramassée ne dure qu'un temps, ensuite on reprend le jo
        if (p.weapon && state.tick >= p.weaponUntil) {
            p.weapon = null;
            shout('JO!', p.x, p.y - 48, '#ffffff');
            sfx('select');
        }

        // touché en plein saut : on retombe
        if (p.z > 0 && (p.state === 'dead' || p.state === 'hurt')) p.z = Math.max(0, p.z - 2);

        if (p.state === 'dead') {
            p.t++;
            p.x += p.vx;
            p.vx *= 0.9;
            if (p.t > 120) respawn(p);
            return;
        }

        if (p.state === 'jump') {
            // coup de pied sauté : on décolle, on avance, la jambe tendue renverse tout
            p.t++;
            p.z += p.vz;
            p.vz -= JUMP.gravity;
            const steer = (keys.has('ArrowRight') ? 1 : 0) - (keys.has('ArrowLeft') ? 1 : 0);
            p.x += p.dir * JUMP.speed + steer * 0.4;
            if (p.t > 5) heroHits(p, -4, 30, 4.5, 2);
            if (p.z <= 0 && p.t > 2) {
                p.z = 0;
                setState(p, 'idle');
                sfx('land');
            }
            keepOnScreen(p);
            return;
        }

        if (p.state === 'bomb') {
            p.t++;
            if (p.t === 6) throwBomb(p);
            if (p.t >= 16) setState(p, 'idle');
            return;
        }

        if (p.state === 'hurt') {
            p.t++;
            p.x += p.vx;
            p.vx *= 0.85;
            if (p.t > 16) setState(p, 'idle');
            keepOnScreen(p);
            return;
        }

        if (p.state === 'attack') {
            p.t++;
            if (p.t === HERO_ATTACK.hitFrom) sfx('whoosh');
            if (p.t >= HERO_ATTACK.hitFrom && p.t <= HERO_ATTACK.hitTo) {
                const weapon = data.weapons[p.weapon ?? 'staff'];
                heroHits(p, 4, weapon.reach, weapon.knockback, weapon.damage);
            }
            if (p.t >= HERO_ATTACK.end) setState(p, 'idle');
            return;
        }

        if (p.state === 'skate') {
            // attaque en glisse : on fonce, jo en avant, en renversant tout le monde
            p.t++;
            p.x += p.dir * SKATE.speed * (p.t < SKATE.duration - 8 ? 1 : 0.5);
            const dy = (keys.has('ArrowDown') ? 1 : 0) - (keys.has('ArrowUp') ? 1 : 0);
            p.y += dy * 0.6;
            heroHits(p, -6, 36, 3.6, 1);
            keepOnScreen(p);
            if (p.t >= SKATE.duration) {
                setState(p, 'idle');
                p.skateCooldown = SKATE.cooldown;
            }
            return;
        }

        if (pressed.has('Space') || pressed.has('KeyX')) {
            setState(p, 'attack');
            p.hits.clear();
            return;
        }

        if (pressed.has('KeyB')) {
            setState(p, 'jump');
            p.vz = JUMP.impulse;
            p.z = 0.1;
            p.hits.clear();
            sfx('kick');
            return;
        }

        if (pressed.has('KeyV')) {
            if (p.bombs > 0) {
                p.bombs--;
                setState(p, 'bomb');
            } else {
                shout('NO BOMB', p.x, p.y - 48, '#ffffff');
                sfx('empty');
            }
            return;
        }

        if (pressed.has('KeyC') && p.skateCooldown === 0) {
            setState(p, 'skate');
            p.hits.clear();
            p.invuln = Math.max(p.invuln, SKATE.duration);
            sfx('skate');
            return;
        }

        const dx = (keys.has('ArrowRight') ? 1 : 0) - (keys.has('ArrowLeft') ? 1 : 0);
        const dy = (keys.has('ArrowDown') ? 1 : 0) - (keys.has('ArrowUp') ? 1 : 0);

        if (dx || dy) {
            setState(p, 'walk');
            p.x += dx * 1.25;
            p.y += dy * 0.8;
            if (dx) p.dir = dx;
        } else {
            setState(p, 'idle');
        }

        keepOnScreen(p);
        collectPickups(p);
    }

    function throwBomb(p) {
        state.projectiles.push({
            sprite: 'grenade', owner: 'hero', x: p.x + p.dir * 10, y: p.y, z: 18,
            vx: p.dir * 2.8, vy: 0, vz: 1.6, gravity: 0.16, damage: BOMB.damage, spin: true, // retombe à ~80 px
        });
        sfx('throw');
    }

    function keepOnScreen(p) {
        p.x = clamp(p.x, state.cam + 10, state.cam + W - 10);
        p.y = clamp(p.y, floor.min, floor.max);
    }

    function heroHits(p, minReach, maxReach, knockback, damage) {
        for (const e of state.enemies) {
            if (e.state === 'dead' || e.state === 'vanish' || p.hits.has(e.id)) continue;
            // les gros boss sont plus larges : plus faciles à toucher
            const bulk = (e.scale - 1) * 12;
            const reach = (e.x - p.x) * p.dir;
            if (reach < minReach - bulk || reach > maxReach + bulk || Math.abs(e.y - p.y) > 7 + bulk / 4) continue;

            p.hits.add(e.id);
            e.hp -= damage;
            state.score += 10 * damage;
            state.shake = 3;
            state.lastEnemy = e;
            state.lastEnemyUntil = state.tick + 150;
            state.sparks.push({ x: e.x - p.dir * 4 * e.scale, y: e.y - 22 * e.scale, t: 0 });

            if (e.hp <= 0) {
                killEnemy(e, p.dir);
            } else if (e.boss && e.state === 'charge') {
                sfx('heavyHit'); // le boss encaisse sans s'arrêter
            } else {
                e.dir = -p.dir;
                setState(e, 'hurt');
                e.vx = p.dir * (e.boss ? knockback * 0.45 : knockback);
                sfx(e.boss ? 'heavyHit' : 'hit');
            }
        }

        // un coup sur un otage coupe ses liens
        for (const pow of state.pows) {
            if (pow.freed) continue;
            const reach = (pow.x - p.x) * p.dir;
            if (reach >= minReach && reach <= maxReach && Math.abs(pow.y - p.y) < 10) freePow(pow);
        }
    }

    /** Otage libéré : il remercie, lâche un bonus et s'enfuit. */
    function freePow(pow) {
        pow.freed = true;
        pow.t = 0;
        state.score += 1000;
        shout('THANK YOU!', pow.x, pow.y - 30, '#7dff5a');
        sfx('freed');
        const roll = Math.random();
        if (roll < 0.5) spawnPickup('bombs', pow.x, pow.y + 6, { fly: true });
        else if (roll < 0.75) spawnPickup('beer', pow.x, pow.y + 6, { fly: true });
        else spawnPickup('weapon', pow.x, pow.y + 6, { fly: true, weapon: pick(['bat', 'chain', 'knife', 'dumbbell']) });
    }

    /** Pose un bonus au sol ; avec fly, il est d'abord projeté en l'air en tournoyant. */
    function spawnPickup(kind, x, y, { fly = false, weapon = null, dir = pick([-1, 1]) } = {}) {
        state.pickups.push({
            kind, weapon, x, y: clamp(y, floor.min, floor.max), t: 0,
            z: fly ? 14 : 0, vz: fly ? 2.6 : 0, vx: fly ? dir * 1.3 : 0,
        });
    }

    function killEnemy(e, fromDir) {
        e.dir = -fromDir;
        setState(e, 'dead');
        e.vx = fromDir * 2.4;
        state.score += e.boss ? 5000 : e.cfg.score;
        sfx(e.boss ? 'heavyHit' : 'hit');
        sfx(e.boss ? 'bossDeath' : 'death', e.boss ? e.scale : e.type);

        if (e.boss) {
            state.flash = 12;
            state.shake = 8;
            shout('K.O.!', e.x, e.y - 50 * e.scale);
            for (const other of state.enemies) {
                if (other !== e && other.state !== 'dead') killEnemy(other, other.x < e.x ? -1 : 1);
            }
            state.projectiles = [];
        } else {
            if (state.level.shouts.length) shout(pick(state.level.shouts), e.x, e.y - 46);
            if (e.cfg.weapon) {
                // l'arme lui échappe et vole en tournoyant avant de retomber : à ramasser !
                spawnPickup('weapon', e.x, e.y, { fly: true, weapon: e.cfg.weapon, dir: fromDir });
            } else if (Math.random() < 0.15) {
                spawnPickup('beer', e.x, e.y, { fly: true });
            }
            if (Math.random() < 0.07) spawnPickup('bombs', e.x, e.y, { fly: true });
        }
    }

    function updatePickups() {
        state.pickups = state.pickups.filter((item) => {
            item.t++;
            if (item.z > 0 || item.vz > 0) {
                item.x += item.vx;
                item.z += item.vz;
                item.vz -= 0.18;
                if (item.z <= 0) Object.assign(item, { z: 0, vz: 0, vx: 0 });
            }
            return item.t < 600;
        });
    }

    function collectPickups(p) {
        state.pickups = state.pickups.filter((item) => {
            if (item.z === 0 && Math.abs(item.x - p.x) < 10 && Math.abs(item.y - p.y) < 6) {
                if (item.kind === 'bombs') {
                    p.bombs = Math.min(BOMB.max, p.bombs + BOMB.perCrate);
                    shout(`BOMB x${BOMB.perCrate}!`, p.x, p.y - 48, '#ffd23f');
                } else if (item.kind === 'weapon') {
                    p.weapon = item.weapon;
                    p.weaponUntil = state.tick + data.weaponDuration;
                    shout(`${data.weapons[item.weapon].name}!`, p.x, p.y - 48, '#ffd23f');
                } else {
                    p.hp = Math.min(p.maxHp, p.hp + 30);
                    shout('+30', p.x, p.y - 48, '#ffd23f');
                }
                sfx('pickup');
                return false;
            }
            return true;
        });
    }

    function damagePlayer(amount, fromX) {
        const p = state.player;
        if (p.invuln || p.state === 'dead' || p.z > 10) return false; // en l'air, on esquive

        const dir = p.x > fromX ? 1 : -1;
        p.hp -= amount;
        p.invuln = 50;
        p.dir = -dir;
        state.shake = 4;
        state.sparks.push({ x: p.x - dir * 4, y: p.y - 24, t: 0 });

        if (p.hp <= 0) {
            p.hp = 0;
            p.weapon = null;
            state.lives--;
            setState(p, 'dead');
            p.vx = dir * 2;
            sfx('heavyHit');
            sfx('heroDeath');
        } else {
            setState(p, 'hurt');
            p.vx = dir * 2.5;
            sfx('hurt');
        }
        return true;
    }

    function respawn(p) {
        if (state.lives <= 0) {
            setMode('gameover');
            // on coupe la musique du niveau et on joue le jingle d'échec
            window.Sfx?.stopMusic();
            sfx('gameOver');
            message('IF YOU KILL A MONSTER<br>YOU CAN BECOME A MONSTER<br><br><span class="blink-text">GAME OVER · ENTER TO CONTINUE</span>');
            return;
        }
        Object.assign(p, { hp: p.maxHp, invuln: 120, vx: 0 });
        setState(p, 'idle');
    }

    // -----------------------------------------------------------------------
    // Les ennemis et les boss
    // -----------------------------------------------------------------------
    function attackTiming(f) {
        if (f.type === 'hero') return HERO_ATTACK;
        return f.boss ? BOSS_ATTACK : ENEMY_ATTACK;
    }

    function updateEnemy(e, rank) {
        const p = state.player;
        const timing = attackTiming(e);
        e.anim++;

        if (e.state === 'dead') {
            e.t++;
            if (e.t < 24) {
                e.x += e.vx;
                e.vx *= 0.9;
            }
            return;
        }

        if (e.state === 'hurt') {
            e.t++;
            e.x += e.vx;
            e.vx *= 0.8;
            if (e.t > (e.boss ? 10 : 18)) {
                setState(e, 'idle');
                e.cooldown = rand(20, 50);
            }
            return;
        }

        if (e.state === 'attack') {
            e.t++;
            if (e.t >= timing.hitFrom && e.t <= timing.hitTo && !e.landed) {
                const reach = (p.x - e.x) * e.dir;
                if (reach > 2 && reach < (e.cfg.reach ?? 26) * e.scale && Math.abs(p.y - e.y) < 6 + e.scale * 2) {
                    e.landed = damagePlayer(enemyDamage(e), e.x);
                }
            }
            if (e.t >= timing.end) {
                setState(e, 'idle');
                e.cooldown = e.boss ? rand(30, 70) : rand(40, 100) * (1 - state.levelIndex * 0.04);
            }
            return;
        }

        if (e.state === 'throw') {
            updateThrow(e);
            return;
        }

        if (e.state === 'lunge-wind' || e.state === 'lunge') {
            updateLunge(e);
            return;
        }

        if (e.boss && updateBossSpecial(e)) return;

        e.cooldown--;
        if (p.state === 'dead') {
            setState(e, 'idle');
            return;
        }

        const side = e.x < p.x ? -1 : 1;
        const far = Math.abs(p.x - e.x);
        // plus on avance dans l'album, plus il y a d'ennemis qui attaquent en même temps
        const attackers = state.levelIndex >= 3 ? 3 : 2;

        // Attaques variées : ruée, lancer d'arme...
        if (!e.boss && e.cooldown <= 0 && Math.abs(p.y - e.y) < 6) {
            const move = chooseMove(e, far, rank < attackers + 1);
            if (move === 'lunge') {
                setState(e, 'lunge-wind');
                e.landed = false;
                return;
            }
            if (move === 'throw') {
                setState(e, 'throw');
                return;
            }
        }

        // les purs lanceurs (hooligans) gardent leurs distances
        if (e.cfg.projectile && !e.boss) rank = Math.max(rank, attackers) + (far < 50 ? 2 : 0);

        // Les plus proches attaquent, les autres tournent autour en attendant leur tour
        const closeRange = (e.cfg.reach ?? 26) * e.scale - 6;
        const distance = rank < attackers ? closeRange : (e.cfg.projectile ? 100 : 56);
        const targetX = p.x + side * distance;
        const targetY = p.y + (rank < attackers ? 0 : (rank % 2 ? -8 : 8));
        const dx = targetX - e.x;
        const dy = targetY - e.y;
        e.dir = p.x > e.x ? 1 : -1;

        if (rank < attackers && Math.abs(dx) < 4 && Math.abs(p.y - e.y) < 5 && e.cooldown <= 0) {
            setState(e, 'attack');
            e.landed = false;
            return;
        }

        if (Math.abs(dx) > 1.5 || Math.abs(dy) > 1) {
            setState(e, 'walk');
            e.x += Math.sign(dx) * Math.min(e.cfg.speed, Math.abs(dx));
            e.y += Math.sign(dy) * Math.min(e.cfg.speed * 0.7, Math.abs(dy));
        } else {
            setState(e, 'idle');
        }

        separate(e);
    }

    function separate(e) {
        for (const other of state.enemies) {
            if (other === e || other.state === 'dead') continue;
            if (Math.abs(other.x - e.x) < 12 && Math.abs(other.y - e.y) < 5) {
                e.y += e.y < other.y ? -0.4 : 0.4;
                e.x += e.x < other.x ? -0.3 : 0.3;
            }
        }
        e.y = clamp(e.y, floor.min, floor.max);
    }

    /** Les dégâts des ennemis augmentent au fil de l'album. */
    function enemyDamage(e) {
        return Math.round(e.cfg.damage * (1 + state.levelIndex * 0.06));
    }

    /** Choisit une attaque spéciale selon la distance et les attaques connues de l'ennemi. */
    function chooseMove(e, far, allowed) {
        const moves = e.cfg.moves ?? {};
        const options = [];
        if (allowed && moves.lunge && far > 34 && far < 80) options.push(['lunge', moves.lunge]);
        if ((moves.throw || e.cfg.projectile) && far > 50 && far < 170) options.push(['throw', moves.throw ?? 3]);
        if (!options.length) return null;

        // chaque attaque spéciale a une chance de sortir, sinon l'ennemi continue d'approcher
        const total = options.reduce((sum, [, weight]) => sum + weight, 0) + (moves.strike ?? 2);
        let roll = Math.random() * total;
        for (const [move, weight] of options) {
            roll -= weight;
            if (roll < 0) return move;
        }
        e.cooldown = 20; // pas cette fois
        return null;
    }

    /** Ruée : l'ennemi prend son élan (il clignote) puis fonce. */
    function updateLunge(e) {
        const p = state.player;
        e.t++;
        if (e.state === 'lunge-wind') {
            e.dir = p.x > e.x ? 1 : -1;
            if (e.t > 14) {
                setState(e, 'lunge');
                sfx('lunge');
            }
            return;
        }
        e.x += e.dir * 3.2;
        if (!e.landed && Math.abs(p.x - e.x) < 12 && Math.abs(p.y - e.y) < 6) {
            e.landed = damagePlayer(enemyDamage(e), e.x - e.dir * 10);
        }
        if (e.t > 18) {
            setState(e, 'idle');
            e.cooldown = rand(50, 110);
        }
        separate(e);
    }

    /** Lance un projectile vers le héros (boss, hooligans, armes lancées). */
    function updateThrow(e) {
        const p = state.player;
        e.t++;
        e.dir = p.x > e.x ? 1 : -1;
        if (e.t === 18) {
            const flight = Math.max(30, Math.abs(p.x - e.x)) / 2.4;
            const sprite = e.cfg.throws ?? e.cfg.projectile;
            state.projectiles.push({
                sprite, x: e.x + e.dir * 10 * e.scale, y: e.y, z: 26 * e.scale,
                vx: Math.sign(p.x - e.x) * 2.4, vy: (p.y - e.y) / flight, vz: 1.2, damage: enemyDamage(e),
                spin: Boolean(weaponIcons[sprite]) || sprite === 'bottle',
            });
            sfx('throw');
        }
        if (e.t > 32) {
            setState(e, 'idle');
            e.cooldown = e.cfg.every ?? 90;
        }
    }

    /** Attaques spéciales des boss. Renvoie true si le boss est occupé. */
    function updateBossSpecial(e) {
        const p = state.player;
        const cfg = e.cfg;

        switch (e.state) {
            case 'charge-wind':
                e.t++;
                e.dir = p.x > e.x ? 1 : -1;
                if (e.t > 34) {
                    setState(e, 'charge');
                    e.landed = false;
                    sfx('skate');
                }
                return true;

            case 'charge': {
                e.t++;
                e.x += e.dir * 3.4;
                if (!e.landed && Math.abs(p.x - e.x) < 14 * e.scale && Math.abs(p.y - e.y) < 6 + e.scale * 3) {
                    e.landed = damagePlayer(Math.round(cfg.damage * 1.5), e.x - e.dir * 10);
                }
                const atEdge = e.x < state.cam + 12 || e.x > state.cam + W - 12;
                if (e.t > 80 || atEdge) {
                    e.x = clamp(e.x, state.cam + 12, state.cam + W - 12);
                    setState(e, 'idle');
                }
                return true;
            }

            case 'vanish':
                e.t++;
                if (e.t === 26) {
                    e.x = clamp(p.x - p.dir * 24, state.cam + 12, state.cam + W - 12);
                    e.y = p.y;
                    e.dir = p.x > e.x ? 1 : -1;
                    setState(e, 'attack');
                    e.landed = false;
                    sfx('teleport');
                }
                return true;

            case 'summon':
                e.t++;
                if (e.t === 20) {
                    const minions = state.enemies.filter((m) => !m.boss && m.state !== 'dead').length;
                    for (let i = 0; i < Math.min(2, 4 - minions); i++) {
                        const fromRight = i === 0;
                        const minion = createFighter(pick(['skinhead', 'masculinist']), fromRight ? state.cam + W + 16 : state.cam - 16, rand(floor.min, floor.max));
                        minion.dir = fromRight ? -1 : 1;
                        state.enemies.push(minion);
                    }
                }
                if (e.t > 40) setState(e, 'idle');
                return true;
        }

        // déclenchement de l'attaque spéciale
        e.special--;
        if (e.special > 0 || p.state === 'dead') return false;
        e.special = cfg.every;

        const start = {
            charge: 'charge-wind',
            throw: 'throw',
            teleport: 'vanish',
            summon: 'summon',
        }[cfg.special];
        setState(e, start);
        if (cfg.special === 'summon' || Math.random() < 0.3) shout(cfg.line, e.x, e.y - 50 * e.scale, '#ffffff');

        return true;
    }

    function updateProjectiles() {
        const p = state.player;
        state.projectiles = state.projectiles.filter((shot) => {
            shot.x += shot.vx;
            shot.y += shot.vy;
            shot.z += shot.vz;
            shot.vz -= shot.gravity ?? 0.08;
            shot.t = (shot.t ?? 0) + 1;

            if (shot.owner !== 'hero') {
                const hitsPlayer = Math.abs(shot.z - p.z) < 30 && Math.abs(p.x - shot.x) < 8 && Math.abs(p.y - shot.y) < 6;
                if (hitsPlayer && damagePlayer(shot.damage, shot.x - shot.vx * 10)) return false;
            }

            if (shot.z > 0) return true;

            // impact au sol
            if (shot.sprite === 'grenade') {
                explode(shot.x, shot.y, shot.owner === 'hero');
            } else if (weaponIcons[shot.sprite]) {
                // une arme lancée qui rate sa cible reste par terre : à ramasser !
                sfx('metal');
                spawnPickup('weapon', shot.x, shot.y, { weapon: shot.sprite });
            } else {
                state.sparks.push({ x: shot.x, y: shot.y - 4, t: 0 });
                sfx(shot.sprite === 'bottle' ? 'glass' : 'metal');
            }
            return false;
        });
    }

    /** Explosion façon Metal Slug : boule de feu, débris, dégâts tout autour. */
    function explode(x, y, byHero) {
        state.explosions.push({ x, y, t: 0 });
        state.shake = 7;
        sfx('explosion');
        for (let i = 0; i < 6; i++) state.sparks.push({ x: x + rand(-14, 14), y: y - rand(4, 24), t: -i });

        if (byHero) {
            for (const e of state.enemies) {
                if (e.state === 'dead' || e.state === 'vanish') continue;
                if (Math.abs(e.x - x) > BOMB.radius + (e.scale - 1) * 10 || Math.abs(e.y - y) > 14) continue;
                const dir = e.x < x ? -1 : 1;
                e.hp -= BOMB.damage;
                state.score += 50;
                state.lastEnemy = e;
                state.lastEnemyUntil = state.tick + 150;
                if (e.hp <= 0) {
                    killEnemy(e, dir);
                } else if (!e.boss || e.state !== 'charge') {
                    setState(e, 'hurt');
                    e.dir = -dir;
                    e.vx = dir * (e.boss ? 1.5 : 4);
                }
            }
            for (const pow of state.pows) {
                if (!pow.freed && Math.abs(pow.x - x) < BOMB.radius && Math.abs(pow.y - y) < 14) freePow(pow);
            }
        } else {
            const p = state.player;
            if (Math.abs(p.x - x) < 20 && Math.abs(p.y - y) < 10) damagePlayer(14, x);
        }
    }

    function updatePows() {
        for (const pow of state.pows) {
            pow.t++;
            if (pow.freed) {
                pow.x -= 2.2; // il détale vers la gauche
            } else if (pow.t % 240 === 60 && pow.x > state.cam && pow.x < state.cam + W) {
                shout('HELP!', pow.x, pow.y - 26, '#ffffff');
            }
        }
        state.pows = state.pows.filter((pow) => !pow.freed || pow.t < 150);
    }

    /** Braises, cendres et explosions au loin : la ville part en ruine au fil de l'album. */
    function updateApocalypse() {
        const chaos = state.level.chaos ?? 0;
        if (chaos > 0 && Math.random() < chaos * 0.7) {
            state.particles.push({ x: rand(0, W), y: H + 2, vx: rand(-0.3, 0.3), vy: -rand(0.3, 1.1), t: 0, kind: 'ember' });
        }
        if (chaos > 0.4 && Math.random() < chaos * 0.5) {
            state.particles.push({ x: rand(0, W + 40), y: -2, vx: -rand(0.2, 0.6), vy: rand(0.2, 0.5), t: 0, kind: 'ash' });
        }
        if (chaos > 0.5 && Math.random() < chaos * 0.005) {
            state.flashes.push({ x: rand(20, W - 20), y: rand(30, 75), t: 0 });
            sfx('boom');
        }
        for (const pt of state.particles) {
            pt.t++;
            pt.x += pt.vx + Math.sin(pt.t / 12) * 0.2;
            pt.y += pt.vy;
        }
        state.particles = state.particles.filter((pt) => pt.t < 260 && pt.y > -4 && pt.y < H + 4);
        for (const f of state.flashes) f.t++;
        state.flashes = state.flashes.filter((f) => f.t < 30);
        for (const ex of state.explosions) ex.t++;
        state.explosions = state.explosions.filter((ex) => ex.t < 30);
    }

    // -----------------------------------------------------------------------
    // Caméra et vagues
    // -----------------------------------------------------------------------
    function updateCamera() {
        const p = state.player;
        const wave = state.level.waves[state.waveIndex];

        if (!state.locked) {
            // la caméra n'avance que vers la droite, comme dans Final Fight
            const target = clamp(p.x - 130, 0, levelLength - W);
            state.cam = Math.max(state.cam, target);
            if (wave) state.cam = Math.min(state.cam, wave.at);
        }

        if (!state.locked && wave && state.cam >= wave.at) {
            state.locked = true;
            hud.go.hidden = true;
            spawnWave(wave);
        }

        if (state.locked && state.enemies.every((e) => e.state === 'dead')) {
            state.locked = false;
            state.waveIndex++;
            if (state.waveIndex >= state.level.waves.length) {
                levelClear();
            } else {
                hud.go.hidden = false;
            }
        }
    }

    function spawnWave(wave) {
        // de temps en temps, une caisse de bombes traîne par terre (toujours à la 2e vague)
        if (state.waveIndex === 1 || Math.random() < 0.4) {
            spawnPickup('bombs', state.cam + rand(80, W - 60), rand(floor.min, floor.max));
        }

        wave.enemies.forEach((type, i) => {
            const fromRight = i % 2 === 0;
            const x = fromRight ? state.cam + W + 16 + i * 14 : state.cam - 16 - i * 14;
            const enemy = createFighter(type, x, rand(floor.min, floor.max));
            enemy.dir = fromRight ? -1 : 1;
            state.enemies.push(enemy);
        });

        if (wave.boss) {
            const boss = createFighter(wave.boss.sprite, state.cam + W + 20 * wave.boss.scale, (floor.min + floor.max) / 2, wave.boss);
            boss.boss = true;
            boss.dir = -1;
            boss.special = 180;
            state.enemies.push(boss);
            state.boss = boss;
            state.lastEnemy = boss;
            message(`<span class="hud__warning">WARNING!</span><br><br>${wave.boss.name}`, 160);
            sfx('warning');
            setTimeout(() => shout(wave.boss.line, boss.x - 40, boss.y - 50 * boss.scale, '#ffffff'), 2800);
        } else {
            const lyric = state.level.shouts.length ? pick(state.level.shouts) : '';
            message(`WAVE ${state.waveIndex + 1}<br><br><span class="hud__lyrics">${lyric}</span>`, 110);
        }
    }

    // -----------------------------------------------------------------------
    // Boucle principale
    // -----------------------------------------------------------------------
    function update() {
        state.tick++;
        state.modeTimer++;

        if (pressed.has('KeyM')) toggleMute();

        switch (state.mode) {
            case 'title':
                updateTitle();
                break;

            case 'intro':
                state.player.anim++;
                if (state.modeTimer > 210 || (state.modeTimer > 30 && (pressed.has('Enter') || pressed.has('Space')))) {
                    message('', 0);
                    setMode('playing');
                }
                break;

            case 'playing': {
                updatePlayer(state.player);

                const alive = state.enemies.filter((e) => e.state !== 'dead')
                    .sort((a, b) => (b.boss - a.boss) || Math.abs(a.x - state.player.x) - Math.abs(b.x - state.player.x));
                for (const e of state.enemies) updateEnemy(e, alive.indexOf(e));
                state.enemies = state.enemies.filter((e) => !(e.state === 'dead' && e.t > 100));
                updateProjectiles();
                updatePickups();
                updatePows();

                if (state.mode === 'playing') updateCamera();
                break;
            }

            case 'clear':
                updatePlayer(state.player);
                for (const e of state.enemies) e.t++;
                if (state.modeTimer > 260) nextLevel();
                break;

            case 'gameover':
                for (const e of state.enemies) if (e.state === 'dead') e.t = Math.min(e.t + 1, 60);
                if (pressed.has('Enter')) {
                    // continue : on recommence le niveau, le score repart à zéro
                    state.score = 0;
                    state.lives = 3;
                    startLevel(state.levelIndex);
                }
                break;

            case 'ending':
                state.player.anim++;
                setState(state.player, 'idle');
                updateEnding();
                break;
        }

        if (!['title', 'loading'].includes(state.mode)) updateApocalypse();
        for (const s of state.sparks) s.t++;
        state.sparks = state.sparks.filter((s) => s.t < 10);
        for (const t of state.texts) t.t++;
        state.texts = state.texts.filter((t) => t.t < 100);
        for (const drop of state.rain) {
            drop.y += 5;
            drop.x -= 1.5;
            if (drop.y > H) Object.assign(drop, { y: rand(-20, 0), x: rand(0, W + 60) });
        }

        if (state.score > state.hiscore) {
            state.hiscore = state.score;
            saveHiscore();
        }
        if (state.tick > state.messageUntil) message('');

        updateJoystick();
        pressed.clear();
    }

    function updateJoystick() {
        const dx = (keys.has('ArrowRight') ? 1 : 0) - (keys.has('ArrowLeft') ? 1 : 0);
        const dy = (keys.has('ArrowDown') ? 1 : 0) - (keys.has('ArrowUp') ? 1 : 0);
        joystick.style.setProperty('--tilt-x', `${dx * 20}deg`);
        joystick.style.setProperty('--tilt-y', `${dy * 14}px`);
        document.querySelectorAll('[data-key]').forEach((b) => b.classList.toggle('is-pressed', keys.has(b.dataset.key)));
    }

    // -----------------------------------------------------------------------
    // Rendu
    // -----------------------------------------------------------------------
    function frameOf(f) {
        // le héros avec une arme ramassée a ses propres sprites ("hero-bat"...)
        const anims = sprites[f.weapon ? `${f.type}-${f.weapon}` : f.type];
        const timing = attackTiming(f);
        const walk = (speed) => anims.walk[Math.floor(f.anim / speed) % anims.walk.length];

        switch (f.state) {
            case 'walk':
                return walk(8);
            case 'attack':
                return anims.attack[f.t >= timing.hitFrom && f.t < timing.strikeUntil ? 1 : 0];
            case 'skate':
                return anims.skate[Math.floor(f.anim / 6) % 2];
            case 'jump':
                return f.t > 5 ? anims.kick[0] : anims.jump[0];
            case 'bomb':
                return anims.attack[f.t < 6 ? 0 : 1];
            case 'lunge-wind':
                return anims.attack[0];
            case 'lunge':
                return anims.attack[1];
            case 'charge':
                return anims.skate ? anims.skate[0] : anims.attack[1];
            case 'charge-wind':
            case 'summon':
                return anims.attack[0];
            case 'throw':
                return anims.attack[f.t >= 18 ? 1 : 0];
            case 'dead':
                return (anims.dead ?? anims.idle)[0];
            case 'hurt':
                return anims.idle[1];
            default:
                return anims.idle[Math.floor(f.anim / 30) % 2];
        }
    }

    function drawFighter(f) {
        if (f.state === 'dead' && f.t > 60 && Math.floor(f.t / 4) % 2) return;
        if (f.invuln && !['hurt', 'dead', 'skate'].includes(f.state) && Math.floor(f.invuln / 3) % 2) return;
        if (f.state === 'vanish' && Math.floor(f.t / 2) % 2) return;

        const frame = frameOf(f);
        const winding = f.state === 'charge-wind' || f.state === 'lunge-wind';
        const flashing = (f.state === 'hurt' && f.t < 5) || (winding && Math.floor(f.t / 4) % 2);
        const image = flashing ? frame.flash : frame.normal;

        ctx.save();
        ctx.translate(Math.round(f.x), Math.round(f.y - (f.z ?? 0)));
        ctx.scale(f.dir < 0 ? -f.scale : f.scale, f.scale);
        if (f.state === 'dead') {
            // tombe à la renverse
            const fall = Math.min(1, f.t / 16);
            ctx.translate(0, -6 * fall);
            ctx.rotate(-fall * Math.PI / 2);
        }
        ctx.drawImage(image, -ANCHOR.x, -ANCHOR.y);
        ctx.restore();

        // traînée du coup de jo (et de la glisse en skate)
        const swinging = f.type === 'hero' && f.state === 'attack' && f.t >= HERO_ATTACK.hitFrom && f.t <= HERO_ATTACK.hitTo + 2;
        const skating = f.state === 'skate' || f.state === 'charge' || f.state === 'lunge';
        if (swinging || skating) {
            ctx.fillStyle = 'rgba(255,255,255,0.8)';
            const x = Math.round(f.x);
            const y = Math.round(f.y);
            for (const [offset, length] of [[-24, 14], [-20, 20], [-16, 10]]) {
                const start = swinging
                    ? (f.dir > 0 ? x + 36 : x - 36 - length)
                    : (f.dir > 0 ? x - 18 - length : x + 18);
                ctx.fillRect(start, y + offset + (skating ? 14 : 0), length, 1);
            }
        }
    }

    /** Dessine une image centrée, éventuellement en rotation (armes qui volent). */
    function drawSpinning(image, x, y, angle) {
        ctx.save();
        ctx.translate(Math.round(x), Math.round(y));
        if (angle) ctx.rotate(Math.round(angle / (Math.PI / 4)) * (Math.PI / 4)); // rotation par huitièmes, plus "pixel"
        ctx.drawImage(image, -Math.round(image.width / 2), -Math.round(image.height / 2));
        ctx.restore();
    }

    /** Disque de pixels (pour les explosions). */
    function pixelDisc(cx, cy, radius, color) {
        ctx.fillStyle = color;
        for (let dy = -radius; dy <= radius; dy++) {
            const half = Math.floor(Math.sqrt(radius * radius - dy * dy));
            ctx.fillRect(Math.round(cx - half), Math.round(cy + dy), half * 2 + 1, 1);
        }
    }

    function drawExplosion(ex) {
        const t = ex.t;
        const radius = t < 8 ? 4 + t * 2 : Math.max(2, 20 - (t - 8) * 0.8);
        const colors = ['#ffffff', '#fff6b0', '#ffd23f', '#ff8a1e', '#e8203a', '#5a2a1e', '#2e2b28'];
        const color = colors[Math.min(colors.length - 1, Math.floor(t / 4))];
        const lift = t * 0.6; // la boule de feu monte en fumant
        pixelDisc(ex.x, ex.y - radius - lift, Math.round(radius), color);
        if (t < 16) pixelDisc(ex.x, ex.y - radius - lift, Math.round(radius * 0.5), colors[Math.max(0, Math.floor(t / 4) - 2)]);
    }

    function drawShadow(x, y, size = 10) {
        ctx.fillStyle = 'rgba(0,0,0,0.4)';
        ctx.fillRect(Math.round(x) - size, Math.round(y) - 1, size * 2, 2);
        ctx.fillRect(Math.round(x) - size + 3, Math.round(y) + 1, size * 2 - 6, 1);
    }

    function drawSpark(s) {
        if (s.t < 0) return;
        const r = 2 + s.t;
        ctx.fillStyle = s.t % 2 ? '#ffffff' : state.level.accent;
        const x = Math.round(s.x);
        const y = Math.round(s.y);
        ctx.fillRect(x - r, y, r * 2 + 1, 1);
        ctx.fillRect(x, y - r, 1, r * 2 + 1);
        const d = Math.round(r * 0.6);
        for (const [sx, sy] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
            ctx.fillRect(x + sx * d, y + sy * d, 1, 1);
        }
    }

    function drawText(t) {
        ctx.font = '8px "Press Start 2P"';
        ctx.textAlign = 'center';
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#000';
        const width = ctx.measureText(t.text).width;
        const x = clamp(t.x, state.cam + width / 2 + 4, state.cam + W - width / 2 - 4);
        const y = Math.max(12, Math.round(t.y - t.t * 0.3));
        if (t.t > 70 && t.t % 4 < 2) return;
        ctx.strokeText(t.text, x, y);
        ctx.fillStyle = t.color ?? (t.t % 8 < 4 ? state.level.accent : PAPER);
        ctx.fillText(t.text, x, y);
    }

    function drawWeather() {
        for (const f of state.flashes) {
            const r = Math.round(2 + f.t * 0.6);
            ctx.globalAlpha = Math.max(0, 1 - f.t / 30) * 0.7;
            pixelDisc(f.x, f.y, r, f.t < 6 ? '#fff6b0' : '#ff8a1e');
            ctx.globalAlpha = 1;
        }
        for (const pt of state.particles) {
            ctx.fillStyle = pt.kind === 'ember' ? (pt.t % 20 < 10 ? '#ffd23f' : '#ff5a1e') : 'rgba(60,56,52,0.7)';
            ctx.fillRect(Math.round(pt.x), Math.round(pt.y), 1, 1);
        }
        if (state.level.rain) {
            ctx.fillStyle = 'rgba(210,220,235,0.45)';
            for (const drop of state.rain) ctx.fillRect(Math.round(drop.x), Math.round(drop.y), 1, 4);
        }
        if (state.level.fog) {
            for (let i = 0; i < 3; i++) {
                const offset = (state.tick * (0.2 + i * 0.15) + i * 90) % (W + 120) - 120;
                ctx.fillStyle = 'rgba(155,77,255,0.08)';
                ctx.fillRect(Math.round(offset), 118 + i * 14, 160, 30);
            }
        }
    }

    function draw() {
        const shake = state.shake > 0 ? Math.round(rand(-state.shake, state.shake)) : 0;
        state.shake = Math.max(0, state.shake - 0.4);

        for (const { el, factor } of layers) {
            const offset = Math.round(state.cam * factor) % W;
            el.setAttribute('transform', `translate(${-offset + shake} 0)`);
        }

        // le canvas est en résolution x2 : on dessine en coordonnées 320x180
        ctx.setTransform(2, 0, 0, 2, 0, 0);
        ctx.imageSmoothingEnabled = false;
        ctx.clearRect(0, 0, W, H);
        ctx.save();
        ctx.translate(-Math.round(state.cam) + shake, 0);

        for (const pow of state.pows) {
            const frame = sprites.pow.tied[Math.floor(pow.t / 20) % 2].normal;
            const hop = pow.freed ? Math.abs(Math.sin(pow.t / 5)) * 6 : 0;
            drawShadow(pow.x, pow.y, 7);
            ctx.save();
            ctx.translate(Math.round(pow.x), Math.round(pow.y - hop));
            if (pow.freed) ctx.scale(-1, 1);
            ctx.drawImage(frame, -Math.round(frame.width / 2), -frame.height + 1);
            ctx.restore();
        }

        for (const item of state.pickups) {
            if (item.t > 480 && item.t % 6 < 3) continue;
            const image = item.kind === 'weapon' ? weaponIcons[item.weapon] : items[item.kind];
            const bob = item.z > 0 ? 0 : Math.floor(item.t / 20) % 2;
            drawShadow(item.x, item.y, Math.max(4, image.width / 2));
            drawSpinning(image, item.x, item.y - item.z - image.height / 2 - 1 - bob, item.z > 0 ? item.t * 0.35 : 0);
        }

        const actors = [...state.enemies, state.player].filter(Boolean).sort((a, b) => a.y - b.y);
        actors.forEach((a) => drawShadow(a.x, a.y, 10 * a.scale));
        actors.forEach(drawFighter);

        for (const shot of state.projectiles) {
            const image = weaponIcons[shot.sprite] ?? items[shot.sprite];
            drawShadow(shot.x, shot.y, 3);
            drawSpinning(image, shot.x, shot.y - shot.z - image.height / 2, shot.spin ? shot.t * 0.4 : 0);
        }

        state.explosions.forEach(drawExplosion);

        state.sparks.forEach(drawSpark);
        state.texts.forEach(drawText);
        ctx.restore();

        drawWeather();

        if (state.flash > 0) {
            ctx.fillStyle = `rgba(255,255,255,${state.flash / 16})`;
            ctx.fillRect(0, 0, W, H);
            state.flash--;
        }

        drawHud();
    }

    function drawHud() {
        const p = state.player;
        const inGame = !['title', 'loading'].includes(state.mode);
        hud.score.textContent = pad(state.score);
        hud.hiscore.textContent = pad(state.hiscore);
        hud.life.style.width = `${inGame && p ? (p.hp / p.maxHp) * 100 : 100}%`;
        const seconds = p?.weapon ? Math.ceil((p.weaponUntil - state.tick) / 60) : 0;
        hud.lives.textContent = `♥ x${Math.max(0, state.lives)} · BOMB ${p?.bombs ?? 0}` + (p?.weapon ? ` · ${data.weapons[p.weapon].name} ${seconds}` : '');

        const boss = state.boss && state.boss.state !== 'dead' ? state.boss : null;
        const enemy = boss ?? state.lastEnemy;
        const showEnemy = inGame && enemy && (boss || state.tick < state.lastEnemyUntil);
        hud.enemy.hidden = !showEnemy;
        if (showEnemy) {
            hud['enemy-name'].textContent = enemy.cfg.name;
            hud['enemy-life'].style.width = `${(Math.max(0, enemy.hp) / enemy.maxHp) * 100}%`;
        }
    }

    let last = performance.now();
    let accumulator = 0;
    const STEP = 1000 / 60;

    function loop(now) {
        accumulator += Math.min(100, now - last);
        last = now;
        while (accumulator >= STEP) {
            update();
            accumulator -= STEP;
        }
        draw();
        requestAnimationFrame(loop);
    }

    // index.php?debug : l'état du jeu est accessible dans la console (window.game)
    if (new URLSearchParams(location.search).has('debug')) window.game = { state, startLevel, killEnemy, spawnPickup };

    ctx.imageSmoothingEnabled = false;
    document.fonts.load('8px "Press Start 2P"').finally(() => requestAnimationFrame(loop));
})();
