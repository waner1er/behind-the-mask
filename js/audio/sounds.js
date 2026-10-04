/** Voix (hauteur et voyelle) du cri de chaque ennemi. */
const VOICES = {
    skinhead: { pitch: 210, vowel: 'a' },
    batter: { pitch: 180, vowel: 'a' },
    chainer: { pitch: 240, vowel: 'e' },
    hooligan: { pitch: 260, vowel: 'o' },
    masculinist: { pitch: 150, vowel: 'o' },
    knifer: { pitch: 280, vowel: 'e' },
    gymbro: { pitch: 110, vowel: 'u' },
};

const arpeggio = (s, notes, { duration, step, volume = 0.12, offset = 0 }) => notes.forEach(
    (f, i) => s.tone({ type: 'square', from: f, duration, volume, delay: offset + i * step }),
);

/** Recettes des bruitages : (synth, ...arguments) => void. */
export const SOUNDS = {
    whoosh: (s) => s.noise({ duration: 0.13, volume: 0.35, filter: 'bandpass', from: 500, to: 2600, q: 1.5 }),
    hit: (s) => {
        s.noise({ duration: 0.11, volume: 0.95, filter: 'lowpass', from: 3200, to: 350 });
        s.tone({ type: 'sine', from: 140, to: 45, duration: 0.16, volume: 0.9 });
        s.tone({ type: 'square', from: 190, to: 60, duration: 0.12, volume: 0.45 });
        s.tone({ type: 'triangle', from: 1400, to: 300, duration: 0.035, volume: 0.4 });
    },
    heavyHit: (s) => {
        s.noise({ duration: 0.26, volume: 1, filter: 'lowpass', from: 1800, to: 100 });
        s.tone({ type: 'sine', from: 110, to: 30, duration: 0.32, volume: 1 });
        s.tone({ type: 'square', from: 230, to: 55, duration: 0.15, volume: 0.45 });
        s.tone({ type: 'triangle', from: 1000, to: 200, duration: 0.04, volume: 0.4 });
    },
    hurt: (s) => {
        s.noise({ duration: 0.14, volume: 0.9, filter: 'lowpass', from: 1800, to: 200 });
        s.tone({ type: 'sine', from: 120, to: 40, duration: 0.18, volume: 0.8 });
        s.tone({ type: 'square', from: 130, to: 45, duration: 0.16, volume: 0.4 });
        s.scream({ pitch: 230, duration: 0.18, volume: 0.22, vowel: 'u', contour: [1, 1.1, 0.8] });
    },
    metal: (s) => {
        s.tone({ type: 'square', from: 1500, to: 1300, duration: 0.18, volume: 0.12 });
        s.tone({ type: 'square', from: 2230, to: 2000, duration: 0.14, volume: 0.08 });
        s.noise({ duration: 0.05, volume: 0.3, filter: 'highpass', from: 3000 });
    },
    glass: (s) => {
        s.noise({ duration: 0.18, volume: 0.35, filter: 'highpass', from: 2500, to: 5000 });
        arpeggio(s, [2600, 3300, 2900], { duration: 0.05, step: 0.03, volume: 0.06 });
    },
    explosion: (s) => {
        s.noise({ duration: 0.7, volume: 0.8, filter: 'lowpass', from: 900, to: 60 });
        s.tone({ type: 'sine', from: 80, to: 25, duration: 0.6, volume: 0.6 });
    },
    throw: (s) => s.tone({ type: 'triangle', from: 300, to: 700, duration: 0.12, volume: 0.15 }),
    skate: (s) => {
        s.noise({ duration: 0.55, volume: 0.25, filter: 'lowpass', from: 500, to: 250 });
        s.tone({ type: 'sawtooth', from: 90, to: 70, duration: 0.5, volume: 0.06 });
    },
    pickup: (s) => arpeggio(s, [660, 880, 1320], { duration: 0.07, step: 0.06 }),
    select: (s) => s.tone({ type: 'square', from: 520, to: 780, duration: 0.06, volume: 0.1 }),
    start: (s) => arpeggio(s, [440, 660, 880], { duration: 0.12, step: 0.1 }),
    warning: (s) => [0, 0.35, 0.7].forEach((delay) => {
        s.tone({ type: 'square', from: 440, to: 330, duration: 0.3, volume: 0.15, delay });
    }),
    teleport: (s) => s.tone({ type: 'sine', from: 300, to: 1800, duration: 0.25, volume: 0.15 }),
    clear: (s) => arpeggio(s, [523, 659, 784, 1047], { duration: 0.18, step: 0.12 }),

    /** Cri de mort d'un ennemi : chacun sa voix, un peu aléatoire. */
    death: (s, type) => {
        const voice = VOICES[type] ?? { pitch: 200, vowel: 'a' };
        s.scream({ pitch: voice.pitch * (0.9 + Math.random() * 0.2), duration: 0.55, vowel: voice.vowel, toVowel: 'o' });
    },
    /** Cri d'un boss : d'autant plus grave qu'il est gros. */
    bossDeath: (s, scale = 2) => {
        s.scream({ pitch: 130 / scale, duration: 1.4, volume: 0.45, vowel: 'a', toVowel: 'o', contour: [1, 1.5, 0.4] });
        s.noise({ duration: 1.2, volume: 0.4, filter: 'lowpass', from: 700, to: 80 });
    },
    /** Le vinyle explose : scratch de DJ puis accord en arpège. */
    scratch: (s) => {
        s.noise({ duration: 0.12, volume: 0.5, filter: 'bandpass', from: 700, to: 2600, q: 3 });
        s.noise({ duration: 0.14, volume: 0.45, filter: 'bandpass', from: 2400, to: 500, q: 3, delay: 0.12 });
        const chord = [523, 659, 784, 1047].sort(() => Math.random() - 0.5);
        arpeggio(s, chord, { duration: 0.14, step: 0.07, offset: 0.25 });
    },
    slash: (s) => {
        s.noise({ duration: 0.12, volume: 0.35, filter: 'highpass', from: 2500, to: 6000 });
        s.tone({ type: 'triangle', from: 2400, to: 1800, duration: 0.18, volume: 0.1 });
        s.tone({ type: 'sine', from: 3600, to: 3200, duration: 0.14, volume: 0.06 });
    },
    oneup: (s) => arpeggio(s, [659, 784, 1319, 1047, 1175, 1568], { duration: 0.08, step: 0.08 }),
    /** WALL OF DEATH : la foule hurle, la batterie blast, les gars crient. */
    wod: (s) => {
        s.noise({ duration: 1.8, volume: 0.45, filter: 'bandpass', from: 400, to: 1400, q: 0.8 });
        for (let i = 0; i < 10; i++) {
            s.tone({ type: 'sine', from: 120, to: 40, duration: 0.12, volume: 0.6, delay: i * 0.14 });
            s.noise({ duration: 0.08, volume: 0.35, filter: 'highpass', from: 2000, delay: i * 0.14 + 0.07 });
        }
        [190, 240, 160].forEach((pitch, i) => setTimeout(
            () => s.scream({ pitch, duration: 0.9, volume: 0.3, vowel: 'a', toVowel: 'o', contour: [1, 1.2, 0.8] }),
            i * 120,
        ));
    },
    /** Grillons pendant un silence gênant. */
    cricket: (s) => [0, 0.12, 0.9, 1.02, 1.8, 1.92].forEach(
        (delay) => s.tone({ type: 'square', from: 4200, to: 4000, duration: 0.05, volume: 0.04, delay }),
    ),
    /** Machine à écrire de la boîte de dialogue. */
    type: (s) => s.tone({ type: 'square', from: 880, to: 860, duration: 0.025, volume: 0.05 }),
    /** Coup de pied sauté : souffle et « HYA ! ». */
    kick: (s) => {
        s.noise({ duration: 0.18, volume: 0.35, filter: 'bandpass', from: 400, to: 2200, q: 1.2 });
        s.scream({ pitch: 300, duration: 0.16, volume: 0.25, vowel: 'a', contour: [1, 1.2, 0.9] });
    },
    land: (s) => s.noise({ duration: 0.06, volume: 0.3, filter: 'lowpass', from: 600, to: 200 }),
    lunge: (s) => s.noise({ duration: 0.25, volume: 0.4, filter: 'bandpass', from: 300, to: 1500, q: 1 }),
    empty: (s) => s.tone({ type: 'square', from: 180, to: 160, duration: 0.05, volume: 0.1 }),
    /** Explosion lointaine, étouffée. */
    boom: (s) => {
        s.noise({ duration: 0.9, volume: 0.22, filter: 'lowpass', from: 300, to: 50 });
        s.tone({ type: 'sine', from: 60, to: 30, duration: 0.8, volume: 0.25 });
    },
    freed: (s) => arpeggio(s, [523, 659, 784, 1047, 784, 1047], { duration: 0.08, step: 0.07 }),
    /** Descente chromatique puis note grave qui s'éteint. */
    gameOver: (s) => {
        [392, 370, 349, 330].forEach((f, i) => {
            s.tone({ type: 'square', from: f, duration: 0.3, volume: 0.2, delay: 0.4 + i * 0.34 });
            s.tone({ type: 'triangle', from: f / 2, duration: 0.3, volume: 0.3, delay: 0.4 + i * 0.34 });
        });
        s.tone({ type: 'square', from: 262, to: 247, duration: 1.6, volume: 0.2, delay: 1.8 });
        s.tone({ type: 'triangle', from: 131, to: 123, duration: 1.8, volume: 0.35, delay: 1.8 });
        s.tone({ type: 'sawtooth', from: 65, to: 55, duration: 1.8, volume: 0.12, delay: 1.8 });
    },
    /** « AAAH... OOOH » : la mort du héros. */
    heroDeath: (s) => {
        s.scream({ pitch: 240, duration: 1.3, volume: 0.45, vowel: 'a', toVowel: 'u', contour: [1, 1.35, 0.35] });
        s.tone({ type: 'square', from: 400, to: 60, duration: 1.2, volume: 0.08, delay: 0.1 });
    },
};
