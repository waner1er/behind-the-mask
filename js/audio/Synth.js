/** Fréquences des formants (F1, F2) de quelques voyelles. */
const VOWELS = { a: [800, 1200], o: [500, 900], e: [550, 1800], u: [350, 700] };

/**
 * Synthétiseur « 16 bits » : aucun fichier son, tout est fabriqué avec des oscillateurs,
 * du bruit blanc et des filtres (Web Audio API).
 */
export class Synth {
    constructor(ctx, output) {
        this.ctx = ctx;
        this.output = output;
        this.noiseBuffer = Synth.#whiteNoise(ctx);
    }

    /** Note avec glissando de from à to (Hz). */
    tone({ type = 'square', from, to = from, duration = 0.1, volume = 0.2, delay = 0 }) {
        const start = this.ctx.currentTime + delay;
        const osc = this.ctx.createOscillator();
        osc.type = type;
        osc.frequency.setValueAtTime(from, start);
        osc.frequency.exponentialRampToValueAtTime(Math.max(20, to), start + duration);
        osc.connect(this.#envelope(start, duration, volume));
        osc.start(start);
        osc.stop(start + duration + 0.02);
    }

    /** Bruit blanc filtré, fréquence de coupure de from à to (Hz). */
    noise({ duration = 0.1, volume = 0.3, filter = 'lowpass', from = 2000, to = from, q = 1, delay = 0 }) {
        const start = this.ctx.currentTime + delay;
        const source = this.ctx.createBufferSource();
        source.buffer = this.noiseBuffer;
        source.loop = true;
        const biquad = this.ctx.createBiquadFilter();
        biquad.type = filter;
        biquad.Q.value = q;
        biquad.frequency.setValueAtTime(from, start);
        biquad.frequency.exponentialRampToValueAtTime(Math.max(20, to), start + duration);
        source.connect(biquad).connect(this.#envelope(start, duration, volume));
        source.start(start, Math.random() * 0.5);
        source.stop(start + duration + 0.02);
    }

    /**
     * Un cri : onde en dents de scie avec vibrato, passée dans deux filtres « formants »
     * calés sur une voyelle. contour = hauteur [début, sommet, fin] en multiples de pitch.
     */
    scream({ pitch = 200, duration = 0.6, volume = 0.35, vowel = 'a', toVowel = vowel, contour = [1, 1.3, 0.55] }) {
        const { ctx } = this;
        const start = ctx.currentTime;
        const end = start + duration;

        const voice = ctx.createOscillator();
        voice.type = 'sawtooth';
        voice.frequency.setValueAtTime(pitch * contour[0], start);
        voice.frequency.linearRampToValueAtTime(pitch * contour[1], start + duration * 0.25);
        voice.frequency.exponentialRampToValueAtTime(pitch * contour[2], end);

        const vibrato = ctx.createOscillator();
        const depth = ctx.createGain();
        vibrato.frequency.value = 7 + Math.random() * 3;
        depth.gain.value = pitch * 0.04;
        vibrato.connect(depth).connect(voice.frequency);

        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(volume, start + 0.03);
        gain.gain.setValueAtTime(volume, end - duration * 0.3);
        gain.gain.exponentialRampToValueAtTime(0.0001, end);
        gain.connect(this.output);

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

        this.noise({ duration, volume: volume * 0.15, filter: 'bandpass', from: 1500, to: 900, q: 2 }); // souffle

        voice.start(start);
        vibrato.start(start);
        voice.stop(end + 0.05);
        vibrato.stop(end + 0.05);
    }

    /** Attaque rapide puis extinction. */
    #envelope(start, duration, volume, attack = 0.005) {
        const gain = this.ctx.createGain();
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(volume, start + attack);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
        gain.connect(this.output);
        return gain;
    }

    static #whiteNoise(ctx) {
        const buffer = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
        return buffer;
    }
}
