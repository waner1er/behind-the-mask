/**
 * La musique des niveaux. Chaque morceau est téléchargé en entier puis décodé en mémoire :
 * il boucle sans coupure et ne dépend pas du streaming (que « php -S » gère mal).
 */
export class MusicPlayer {
    constructor(ctx, bus) {
        this.ctx = ctx;
        this.bus = bus;
        this.tracks = new Map(); // url -> Promise<AudioBuffer>
        this.current = null;     // { url, source, gain } ; source null pendant le chargement
    }

    async play(url) {
        if (this.current?.url === url) return;
        this.stop();
        this.current = { url, source: null, gain: null };

        let buffer;
        try {
            buffer = await this.#load(url);
        } catch {
            return;
        }
        if (this.current?.url !== url) return; // un autre morceau a été demandé entre-temps

        const { ctx } = this;
        const source = ctx.createBufferSource();
        source.buffer = buffer;
        source.loop = true;
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(1, ctx.currentTime + 0.3);
        source.connect(gain).connect(this.bus);
        source.start();
        this.current = { url, source, gain };
    }

    stop(fade = 0.4) {
        if (!this.current) return;
        const { source, gain } = this.current;
        this.current = null; // un morceau encore en chargement ne sera pas lancé
        if (!source) return;
        const now = this.ctx.currentTime;
        gain.gain.setValueAtTime(gain.gain.value, now);
        gain.gain.linearRampToValueAtTime(0, now + fade);
        source.stop(now + fade + 0.05);
    }

    preload(url) {
        this.#load(url).catch(() => {});
    }

    #load(url) {
        if (!this.tracks.has(url)) {
            this.tracks.set(url, fetch(url)
                .then((response) => response.arrayBuffer())
                .then((bytes) => this.ctx.decodeAudioData(bytes))
                .catch((error) => {
                    this.tracks.delete(url);
                    throw error;
                }));
        }
        return this.tracks.get(url);
    }
}
