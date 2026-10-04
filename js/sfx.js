'use strict';

/*
 * Tout le son du jeu : la musique des niveaux et les bruitages.
 *
 * La musique est téléchargée en entier puis décodée en mémoire (AudioBuffer) :
 * elle boucle sans coupure et ne dépend pas du serveur (le serveur intégré
 * "php -S" ne gère pas le streaming audio, ce qui faisait sauter la musique).
 *
 * Mixage : musique (plus basse) + bruitages -> compresseur -> volume général.
 *
 * Bruitages "16 bits" synthétisés en direct avec la Web Audio API :
 * aucun fichier son, tout est fabriqué avec des oscillateurs, du bruit blanc et des filtres.
 * Un "bitcrusher" (WaveShaper en escalier) donne le grain des consoles de l'époque.
 *
 * Les cris utilisent des filtres "formants" : deux filtres passe-bande calés sur les
 * fréquences d'une voyelle (A, O...) transforment une onde en dents de scie en voix.
 */
window.Sfx = (() => {
    let ctx = null;
    let output = null;     // entrée des bruitages (passe par le bitcrusher)
    let musicBus = null;   // entrée de la musique
    let master = null;
    let noiseBuffer = null;
    let muted = false;

    const MUSIC_VOLUME = 0.45;
    const SFX_VOLUME = 1.3;

    const tracks = new Map(); // url -> Promise<AudioBuffer>
    let music = null;         // { source, gain, url }

    // Fréquences des formants (F1, F2) de quelques voyelles
    const VOWELS = { a: [800, 1200], o: [500, 900], e: [550, 1800], u: [350, 700] };

    function init() {
        if (ctx) return ctx;
        try {
            ctx = new AudioContext();
        } catch {
            return null;
        }

        // bitcrusher : la courbe en escalier réduit la résolution du signal
        const crusher = ctx.createWaveShaper();
        const steps = 48;
        const curve = new Float32Array(1024);
        for (let i = 0; i < curve.length; i++) {
            const x = (i / (curve.length - 1)) * 2 - 1;
            curve[i] = Math.round(x * steps) / steps;
        }
        crusher.curve = curve;

        // le compresseur évite que ça sature quand tout tape en même temps
        const compressor = ctx.createDynamicsCompressor();
        compressor.threshold.value = -14;
        compressor.ratio.value = 6;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.15;

        master = ctx.createGain();
        master.gain.value = muted ? 0 : 1;
        compressor.connect(master).connect(ctx.destination);

        const sfxBus = ctx.createGain();
        sfxBus.gain.value = SFX_VOLUME;
        crusher.connect(sfxBus).connect(compressor);
        output = crusher;

        musicBus = ctx.createGain();
        musicBus.gain.value = MUSIC_VOLUME;
        musicBus.connect(compressor);

        noiseBuffer = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
        const data = noiseBuffer.getChannelData(0);
        for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;

        return ctx;
    }

    /** Enveloppe de volume : attaque rapide puis extinction. */
    function envelope(start, duration, volume, attack = 0.005) {
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(volume, start + attack);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
        gain.connect(output);
        return gain;
    }

    function tone({ type = 'square', from, to = from, duration = 0.1, volume = 0.2, delay = 0 }) {
        const start = ctx.currentTime + delay;
        const osc = ctx.createOscillator();
        osc.type = type;
        osc.frequency.setValueAtTime(from, start);
        osc.frequency.exponentialRampToValueAtTime(Math.max(20, to), start + duration);
        osc.connect(envelope(start, duration, volume));
        osc.start(start);
        osc.stop(start + duration + 0.02);
    }

    function noise({ duration = 0.1, volume = 0.3, filter = 'lowpass', from = 2000, to = from, q = 1, delay = 0 }) {
        const start = ctx.currentTime + delay;
        const source = ctx.createBufferSource();
        source.buffer = noiseBuffer;
        source.loop = true;
        const biquad = ctx.createBiquadFilter();
        biquad.type = filter;
        biquad.Q.value = q;
        biquad.frequency.setValueAtTime(from, start);
        biquad.frequency.exponentialRampToValueAtTime(Math.max(20, to), start + duration);
        source.connect(biquad).connect(envelope(start, duration, volume));
        source.start(start, Math.random() * 0.5);
        source.stop(start + duration + 0.02);
    }

    /**
     * Un cri : onde en dents de scie + vibrato, passée dans les formants d'une voyelle.
     * pitch = hauteur de la voix, contour = [début, sommet, fin] (multiplicateurs)
     */
    function scream({ pitch = 200, duration = 0.6, volume = 0.35, vowel = 'a', toVowel = vowel, contour = [1, 1.3, 0.55] }) {
        const start = ctx.currentTime;
        const end = start + duration;

        const voice = ctx.createOscillator();
        voice.type = 'sawtooth';
        voice.frequency.setValueAtTime(pitch * contour[0], start);
        voice.frequency.linearRampToValueAtTime(pitch * contour[1], start + duration * 0.25);
        voice.frequency.exponentialRampToValueAtTime(pitch * contour[2], end);

        // vibrato
        const lfo = ctx.createOscillator();
        const depth = ctx.createGain();
        lfo.frequency.value = 7 + Math.random() * 3;
        depth.gain.value = pitch * 0.04;
        lfo.connect(depth).connect(voice.frequency);

        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(volume, start + 0.03);
        gain.gain.setValueAtTime(volume, end - duration * 0.3);
        gain.gain.exponentialRampToValueAtTime(0.0001, end);
        gain.connect(output);

        [0, 1].forEach((i) => {
            const formant = ctx.createBiquadFilter();
            formant.type = 'bandpass';
            formant.Q.value = 7 + i * 3;
            formant.frequency.setValueAtTime(VOWELS[vowel][i], start);
            formant.frequency.linearRampToValueAtTime(VOWELS[toVowel][i], end);
            const level = ctx.createGain();
            level.gain.value = i === 0 ? 1.4 : 0.9;
            voice.connect(formant).connect(level).connect(gain);
        });

        // souffle
        noise({ duration, volume: volume * 0.15, filter: 'bandpass', from: 1500, to: 900, q: 2 });

        voice.start(start);
        lfo.start(start);
        voice.stop(end + 0.05);
        lfo.stop(end + 0.05);
    }

    // Hauteur et voyelle du cri de chaque ennemi
    const VOICES = {
        skinhead: { pitch: 210, vowel: 'a' },
        batter: { pitch: 180, vowel: 'a' },
        chainer: { pitch: 240, vowel: 'e' },
        hooligan: { pitch: 260, vowel: 'o' },
        masculinist: { pitch: 150, vowel: 'o' },
        knifer: { pitch: 280, vowel: 'e' },
        gymbro: { pitch: 110, vowel: 'u' },
    };

    const sounds = {
        // coup de jo dans le vide
        whoosh: () => noise({ duration: 0.13, volume: 0.35, filter: 'bandpass', from: 500, to: 2600, q: 1.5 }),
        // coup qui touche
        hit: () => {
            noise({ duration: 0.11, volume: 0.95, filter: 'lowpass', from: 3200, to: 350 });
            tone({ type: 'sine', from: 140, to: 45, duration: 0.16, volume: 0.9 });       // impact sourd
            tone({ type: 'square', from: 190, to: 60, duration: 0.12, volume: 0.45 });
            tone({ type: 'triangle', from: 1400, to: 300, duration: 0.035, volume: 0.4 }); // claquement
        },
        heavyHit: () => {
            noise({ duration: 0.26, volume: 1, filter: 'lowpass', from: 1800, to: 100 });
            tone({ type: 'sine', from: 110, to: 30, duration: 0.32, volume: 1 });
            tone({ type: 'square', from: 230, to: 55, duration: 0.15, volume: 0.45 });
            tone({ type: 'triangle', from: 1000, to: 200, duration: 0.04, volume: 0.4 });
        },
        // le héros encaisse
        hurt: () => {
            noise({ duration: 0.14, volume: 0.9, filter: 'lowpass', from: 1800, to: 200 });
            tone({ type: 'sine', from: 120, to: 40, duration: 0.18, volume: 0.8 });
            tone({ type: 'square', from: 130, to: 45, duration: 0.16, volume: 0.4 });
            scream({ pitch: 230, duration: 0.18, volume: 0.22, vowel: 'u', contour: [1, 1.1, 0.8] });
        },
        metal: () => {
            tone({ type: 'square', from: 1500, to: 1300, duration: 0.18, volume: 0.12 });
            tone({ type: 'square', from: 2230, to: 2000, duration: 0.14, volume: 0.08 });
            noise({ duration: 0.05, volume: 0.3, filter: 'highpass', from: 3000 });
        },
        glass: () => {
            noise({ duration: 0.18, volume: 0.35, filter: 'highpass', from: 2500, to: 5000 });
            [2600, 3300, 2900].forEach((f, i) => tone({ type: 'square', from: f, duration: 0.05, volume: 0.06, delay: i * 0.03 }));
        },
        explosion: () => {
            noise({ duration: 0.7, volume: 0.8, filter: 'lowpass', from: 900, to: 60 });
            tone({ type: 'sine', from: 80, to: 25, duration: 0.6, volume: 0.6 });
        },
        throw: () => tone({ type: 'triangle', from: 300, to: 700, duration: 0.12, volume: 0.15 }),
        skate: () => {
            noise({ duration: 0.55, volume: 0.25, filter: 'lowpass', from: 500, to: 250 });
            tone({ type: 'sawtooth', from: 90, to: 70, duration: 0.5, volume: 0.06 });
        },
        pickup: () => [660, 880, 1320].forEach((f, i) => tone({ type: 'square', from: f, duration: 0.07, volume: 0.12, delay: i * 0.06 })),
        select: () => tone({ type: 'square', from: 520, to: 780, duration: 0.06, volume: 0.1 }),
        start: () => [440, 660, 880].forEach((f, i) => tone({ type: 'square', from: f, duration: 0.12, volume: 0.12, delay: i * 0.1 })),
        warning: () => [0, 0.35, 0.7].forEach((delay) => {
            tone({ type: 'square', from: 440, to: 330, duration: 0.3, volume: 0.15, delay });
        }),
        teleport: () => tone({ type: 'sine', from: 300, to: 1800, duration: 0.25, volume: 0.15 }),
        clear: () => [523, 659, 784, 1047].forEach((f, i) => tone({ type: 'square', from: f, duration: 0.18, volume: 0.12, delay: i * 0.12 })),

        // cri de mort d'un ennemi (chacun sa voix, un peu aléatoire)
        death: (type) => {
            const voice = VOICES[type] ?? { pitch: 200, vowel: 'a' };
            scream({ pitch: voice.pitch * (0.9 + Math.random() * 0.2), duration: 0.55, vowel: voice.vowel, toVowel: 'o' });
        },
        // cri d'un boss : grave et long
        bossDeath: (scale = 2) => {
            scream({ pitch: 130 / scale, duration: 1.4, volume: 0.45, vowel: 'a', toVowel: 'o', contour: [1, 1.5, 0.4] });
            noise({ duration: 1.2, volume: 0.4, filter: 'lowpass', from: 700, to: 80 });
        },
        // le vinyle explose : scratch de DJ puis petit accord joué en arpège
        scratch: () => {
            noise({ duration: 0.12, volume: 0.5, filter: 'bandpass', from: 700, to: 2600, q: 3 });
            noise({ duration: 0.14, volume: 0.45, filter: 'bandpass', from: 2400, to: 500, q: 3, delay: 0.12 });
            const chord = [523, 659, 784, 1047].sort(() => Math.random() - 0.5);
            chord.forEach((f, i) => tone({ type: 'square', from: f, duration: 0.14, volume: 0.12, delay: 0.25 + i * 0.07 }));
        },
        // coup de katana : "shing" métallique
        slash: () => {
            noise({ duration: 0.12, volume: 0.35, filter: 'highpass', from: 2500, to: 6000 });
            tone({ type: 'triangle', from: 2400, to: 1800, duration: 0.18, volume: 0.1 });
            tone({ type: 'sine', from: 3600, to: 3200, duration: 0.14, volume: 0.06 });
        },
        // grillons pendant un silence gênant : "cri-cri... cri-cri..."
        cricket: () => {
            [0, 0.12, 0.9, 1.02, 1.8, 1.92].forEach((delay) => tone({ type: 'square', from: 4200, to: 4000, duration: 0.05, volume: 0.04, delay }));
        },
        // petit bip de la machine à écrire (boîte de dialogue)
        type: () => tone({ type: 'square', from: 880, to: 860, duration: 0.025, volume: 0.05 }),
        // coup de pied sauté : souffle + petit cri "HYA!"
        kick: () => {
            noise({ duration: 0.18, volume: 0.35, filter: 'bandpass', from: 400, to: 2200, q: 1.2 });
            scream({ pitch: 300, duration: 0.16, volume: 0.25, vowel: 'a', contour: [1, 1.2, 0.9] });
        },
        land: () => noise({ duration: 0.06, volume: 0.3, filter: 'lowpass', from: 600, to: 200 }),
        lunge: () => noise({ duration: 0.25, volume: 0.4, filter: 'bandpass', from: 300, to: 1500, q: 1 }),
        empty: () => tone({ type: 'square', from: 180, to: 160, duration: 0.05, volume: 0.1 }),
        // explosion lointaine, étouffée
        boom: () => {
            noise({ duration: 0.9, volume: 0.22, filter: 'lowpass', from: 300, to: 50 });
            tone({ type: 'sine', from: 60, to: 30, duration: 0.8, volume: 0.25 });
        },
        // otage libéré : petite fanfare joyeuse
        freed: () => [523, 659, 784, 1047, 784, 1047].forEach((f, i) => tone({ type: 'square', from: f, duration: 0.08, volume: 0.12, delay: i * 0.07 })),
        // jingle d'échec : descente chromatique puis note grave qui s'éteint
        gameOver: () => {
            [392, 370, 349, 330].forEach((f, i) => {
                tone({ type: 'square', from: f, duration: 0.3, volume: 0.2, delay: 0.4 + i * 0.34 });
                tone({ type: 'triangle', from: f / 2, duration: 0.3, volume: 0.3, delay: 0.4 + i * 0.34 });
            });
            tone({ type: 'square', from: 262, to: 247, duration: 1.6, volume: 0.2, delay: 1.8 });
            tone({ type: 'triangle', from: 131, to: 123, duration: 1.8, volume: 0.35, delay: 1.8 });
            tone({ type: 'sawtooth', from: 65, to: 55, duration: 1.8, volume: 0.12, delay: 1.8 });
        },
        // "AAAH... OOOH" : la mort du héros
        heroDeath: () => {
            scream({ pitch: 240, duration: 1.3, volume: 0.45, vowel: 'a', toVowel: 'u', contour: [1, 1.35, 0.35] });
            tone({ type: 'square', from: 400, to: 60, duration: 1.2, volume: 0.08, delay: 0.1 });
        },
    };

    /** Télécharge et décode un morceau (une seule fois par morceau). */
    function loadTrack(url) {
        if (!tracks.has(url)) {
            tracks.set(url, fetch(url)
                .then((response) => response.arrayBuffer())
                .then((bytes) => ctx.decodeAudioData(bytes))
                .catch((error) => {
                    tracks.delete(url);
                    throw error;
                }));
        }
        return tracks.get(url);
    }

    function stopMusic(fade = 0.4) {
        if (!music) return;
        const { source, gain } = music;
        music = null; // si le morceau était encore en chargement, il ne sera pas lancé
        if (!source) return;
        const now = ctx.currentTime;
        gain.gain.setValueAtTime(gain.gain.value, now);
        gain.gain.linearRampToValueAtTime(0, now + fade);
        source.stop(now + fade + 0.05);
    }

    return {
        /** Lance la musique d'un niveau, en boucle sans coupure. */
        async playMusic(url) {
            if (!url || !init()) return;
            if (ctx.state === 'suspended') ctx.resume();
            if (music?.url === url) return;
            stopMusic();
            const wanted = url;
            music = { url, source: null, gain: null };

            let buffer;
            try {
                buffer = await loadTrack(url);
            } catch {
                return;
            }
            if (music?.url !== wanted) return; // un autre morceau a été demandé entre-temps

            const source = ctx.createBufferSource();
            source.buffer = buffer;
            source.loop = true;
            const gain = ctx.createGain();
            gain.gain.setValueAtTime(0, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(1, ctx.currentTime + 0.3);
            source.connect(gain).connect(musicBus);
            source.start();
            music = { url, source, gain };
        },
        /** Recommence le morceau en cours depuis le début. */
        restartMusic() {
            const url = music?.url;
            if (!url) return;
            stopMusic(0.1);
            this.playMusic(url);
        },
        /** Précharge un morceau en arrière-plan (le niveau suivant). */
        preload(url) {
            if (url && init()) loadTrack(url).catch(() => {});
        },
        stopMusic: () => ctx && stopMusic(),
        /** À appeler pendant un geste de l'utilisateur (iOS/Android n'autorisent le son qu'à ce moment-là). */
        unlock() {
            if (init() && ctx.state === 'suspended') ctx.resume();
        },
        /** Morceau en cours de lecture (null si rien ou en chargement). */
        get playing() {
            return music?.source ? music.url : null;
        },
        play(name, ...args) {
            if (muted || !init()) return;
            if (ctx.state === 'suspended') ctx.resume();
            try {
                sounds[name]?.(...args);
            } catch {
                // un son raté ne doit jamais casser le jeu
            }
        },
        setMuted(value) {
            muted = value;
            if (master) master.gain.setTargetAtTime(muted ? 0 : 1, ctx.currentTime, 0.05);
        },
    };
})();
