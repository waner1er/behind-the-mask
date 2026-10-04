/**
 * Le décor SVG généré par PHP. Celui du premier niveau est dans la page ; les autres
 * sont téléchargés à la demande (scene.php ou fichiers statiques) puis gardés en cache.
 *
 * Chaque plan .layer[data-factor] contient deux fois le même dessin côte à côte :
 * on le décale selon la caméra (parallaxe), modulo la largeur de l'écran.
 */
export class Backdrop {
    constructor(svg, urlPattern, width) {
        this.svg = svg;
        this.urlPattern = urlPattern;
        this.width = width;
        this.cache = new Map([[0, svg.innerHTML]]);
        this.current = 0;
        this.layers = this.#readLayers();
    }

    /** @param {number|string} index numéro du niveau (à partir de 0) ou « peace » */
    async load(index) {
        if (this.current === index) return;
        this.current = index;
        if (!this.cache.has(index)) {
            const response = await fetch(this.urlPattern.replace('%d', index));
            this.cache.set(index, await response.text());
        }
        if (this.current !== index) return; // un autre décor a été demandé entre-temps
        this.svg.innerHTML = this.cache.get(index);
        this.layers = this.#readLayers();
    }

    scroll(cam, shake) {
        for (const { el, factor } of this.layers) {
            const offset = Math.round(cam * factor) % this.width;
            el.setAttribute('transform', `translate(${-offset + shake} 0)`);
        }
    }

    #readLayers() {
        return [...this.svg.querySelectorAll('.layer')].map((el) => ({ el, factor: parseFloat(el.dataset.factor) }));
    }
}
