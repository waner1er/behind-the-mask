import { INK, PAPER } from '../config.js';

/** Notes de musique de l'explosion des vinyles : croche et double croche. */
const NOTE_GRIDS = [
    ['....##.', '....#.#', '....#..', '....#..', '..###..', '.####..', '..##...'],
    ['.######', '.#....#', '.#....#', '.#....#', '##...##', '##..###', '.....##'],
];
const NOTE_COLORS = ['#ffd23f', '#e8203a', PAPER, '#3ef0ff', '#ff3ea5'];

/** Variantes de couleurs des otages libérés (les citoyens de la fin), sans leurs cordes. */
const CITIZEN_LOOKS = [
    { G: '#7dff5a', g: '#3aa02a', J: '#26262e', A: '#3d63d6' },
    { G: '#ff3ea5', g: '#a8136f', J: '#e8203a', A: '#2c2c38' },
    { G: '#ffd23f', g: '#c08a1a', J: '#3a6a3a', A: '#8a5a30' },
    { G: '#3ef0ff', g: '#1a8aa0', J: '#5a2a7a', A: '#26408f' },
    { G: '#f4f4f4', g: '#a8a8b8', J: '#ff8a1e', A: '#3d63d6' },
];

/**
 * Convertit les grilles de pixels envoyées par PHP (1 caractère = 1 pixel) en petits canvas prêts à dessiner.
 * Chaque image de personnage existe en version normale et en version « flash » toute blanche (coup reçu).
 */
export class SpriteBank {
    constructor(data) {
        const { anchor, weaponIcons, ...characters } = data.sprites;
        this.anchor = anchor;
        this.fighters = SpriteBank.#animations(characters);
        this.items = SpriteBank.#images(data.items.sprites, data.items.palette);
        this.weaponIcons = SpriteBank.#images(weaponIcons.sprites, weaponIcons.palette);
        this.notes = SpriteBank.#notes();
        this.citizens = SpriteBank.#citizens(data.sprites.pow);
    }

    static toCanvas(grid, palette, tint = null) {
        const canvas = document.createElement('canvas');
        canvas.width = grid[0].length;
        canvas.height = grid.length;
        const g = canvas.getContext('2d');

        grid.forEach((row, y) => {
            for (let x = 0; x < row.length; x++) {
                const color = tint ?? palette[row[x]];
                if (row[x] === '.' || !color) continue;
                g.fillStyle = color;
                g.fillRect(x, y, 1, 1);
            }
        });

        return canvas;
    }

    /** { type: { animation: [{ normal, flash }] } } */
    static #animations(characters) {
        const result = {};
        for (const [type, sheet] of Object.entries(characters)) {
            result[type] = {};
            for (const [anim, frames] of Object.entries(sheet.frames)) {
                result[type][anim] = frames.map((grid) => ({
                    normal: SpriteBank.toCanvas(grid, sheet.palette),
                    flash: SpriteBank.toCanvas(grid, sheet.palette, '#ffffff'),
                }));
            }
        }
        return result;
    }

    static #images(grids, palette) {
        return Object.fromEntries(Object.entries(grids).map(([name, grid]) => [name, SpriteBank.toCanvas(grid, palette)]));
    }

    /** Notes détourées de noir : la note en noir décalée dans les 4 directions, puis en couleur par-dessus. */
    static #notes() {
        const images = [];
        for (const color of NOTE_COLORS) {
            for (const grid of NOTE_GRIDS) {
                const image = document.createElement('canvas');
                image.width = grid[0].length + 2;
                image.height = grid.length + 2;
                const g = image.getContext('2d');
                const outline = SpriteBank.toCanvas(grid, { '#': INK });
                for (const [dx, dy] of [[0, 1], [2, 1], [1, 0], [1, 2]]) g.drawImage(outline, dx, dy);
                g.drawImage(SpriteBank.toCanvas(grid, { '#': color }), 1, 1);
                images.push(image);
            }
        }
        return images;
    }

    static #citizens(pow) {
        return CITIZEN_LOOKS.map((look) => SpriteBank.toCanvas(pow.frames.tied[1], {
            ...pow.palette, ...look, j: look.J, r: look.J, R: look.J,
        }));
    }
}
