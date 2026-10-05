/**
 * Enregistre les bruitages du jeu web (js/audio/sounds.js, synthétisés en direct) en fichiers WAV,
 * pour l'export OpenBOR : même synthé, même bitcrusher et même compresseur, rendus hors ligne dans Chrome.
 *
 *   node tools/record-sounds.mjs dossier/ nom[:argument] ...   → dossier/nom[-argument].wav
 *   node tools/record-sounds.mjs out/ hit slash death:skinhead bossDeath:2
 */
import { writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { openGame } from '../tests/e2e/harness.mjs';

const [directory, ...sounds] = process.argv.slice(2);
const RATE = 22050;

const game = await openGame();
try {
    for (const spec of sounds) {
        const [name, arg] = spec.split(':');
        const base64 = await game.page.evaluate(async ({ name, arg, rate }) => {
            const { SOUNDS } = await import('./js/audio/sounds.js');
            const { Synth } = await import('./js/audio/Synth.js');
            const seconds = 3;
            const ctx = new OfflineAudioContext(1, rate * seconds, rate);

            // même chaîne que AudioEngine : bitcrusher → bus des bruitages → compresseur
            const crusher = ctx.createWaveShaper();
            const curve = new Float32Array(1024);
            for (let i = 0; i < curve.length; i++) curve[i] = Math.round(((i / 1023) * 2 - 1) * 48) / 48;
            crusher.curve = curve;
            const bus = ctx.createGain();
            bus.gain.value = 1.3;
            const compressor = ctx.createDynamicsCompressor();
            compressor.threshold.value = -14;
            compressor.ratio.value = 6;
            compressor.attack.value = 0.003;
            compressor.release.value = 0.15;
            crusher.connect(bus).connect(compressor).connect(ctx.destination);

            // les sons qui crient en différé (setTimeout) : rejoués à ce moment du rendu
            const realTimeout = window.setTimeout;
            const later = [];
            window.setTimeout = (callback, delay = 0) => later.push([callback, delay / 1000]);
            const arg2 = arg === undefined ? undefined : (Number.isNaN(Number(arg)) ? arg : Number(arg));
            SOUNDS[name](new Synth(ctx, crusher), arg2);
            window.setTimeout = realTimeout;
            for (const [callback, at] of later) {
                ctx.suspend(Math.max(1 / rate * 128, at)).then(() => {
                    callback();
                    ctx.resume();
                });
            }

            const buffer = await ctx.startRendering();
            const data = buffer.getChannelData(0);
            // coupe le silence de fin
            let end = data.length;
            while (end > rate * 0.05 && Math.abs(data[end - 1]) < 0.002) end--;
            const pcm = new Int16Array(end);
            for (let i = 0; i < end; i++) pcm[i] = Math.max(-1, Math.min(1, data[i])) * 32767;

            // en-tête WAV PCM 16 bits mono
            const header = new DataView(new ArrayBuffer(44));
            const text = (offset, s) => [...s].forEach((c, i) => header.setUint8(offset + i, c.charCodeAt(0)));
            text(0, 'RIFF'); header.setUint32(4, 36 + pcm.byteLength, true); text(8, 'WAVE');
            text(12, 'fmt '); header.setUint32(16, 16, true); header.setUint16(20, 1, true); header.setUint16(22, 1, true);
            header.setUint32(24, rate, true); header.setUint32(28, rate * 2, true); header.setUint16(32, 2, true);
            header.setUint16(34, 16, true); text(36, 'data'); header.setUint32(40, pcm.byteLength, true);
            const bytes = new Uint8Array(44 + pcm.byteLength);
            bytes.set(new Uint8Array(header.buffer), 0);
            bytes.set(new Uint8Array(pcm.buffer), 44);
            let binary = '';
            for (let i = 0; i < bytes.length; i += 0x8000) binary += String.fromCharCode(...bytes.subarray(i, i + 0x8000));
            return btoa(binary);
        }, { name, arg, rate: RATE });
        await writeFile(join(directory, `${name}${arg === undefined ? '' : `-${arg}`}.wav`), Buffer.from(base64, 'base64'));
    }
    if (game.errors.length) console.error(game.errors.join('\n'));
} finally {
    await game.close();
}
