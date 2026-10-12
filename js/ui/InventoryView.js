/**
 * L'inventaire d'un joueur sous sa barre de vie, une ligne par objet : vies, vinyles, Wall of Death
 * (seulement s'il en a), arme ramassée (avec une jauge qui se vide et les secondes restantes).
 * Les icônes sont les sprites des objets au sol : on reconnaît ce qu'on vient de ramasser.
 */
export class InventoryView {
    constructor(list, game) {
        this.game = game;
        this.list = list;
        this.icons = new Map();
        this.lives = this.#row('lives', game.sprites.items.life);
        this.vinyls = this.#row('vinyls', game.sprites.items.vinyls);
        this.wods = this.#row('wod', game.sprites.items.wod);
        this.weapon = this.#row('weapon', null);
        this.weapon.gauge = document.createElement('span');
        this.weapon.gauge.className = 'hud__gauge';
        this.weapon.fill = this.weapon.gauge.appendChild(document.createElement('span'));
        this.weapon.seconds = document.createElement('span');
        this.weapon.item.append(this.weapon.gauge, this.weapon.seconds);
        this.weaponType = null;
    }

    /** p : le héros (absent s'il a quitté la partie à deux) ; lives : ses vies restantes. */
    update(p, lives) {
        const { state, data } = this.game;
        InventoryView.#set(this.lives.text, `×${Math.max(0, lives)}`);
        InventoryView.#set(this.vinyls.text, `×${p?.vinyls ?? 0}`);
        this.vinyls.item.hidden = !p;

        this.wods.item.hidden = !p?.wods;
        if (p?.wods) InventoryView.#set(this.wods.text, `×${p.wods}`);

        this.weapon.item.hidden = !p?.weapon;
        if (!p?.weapon) return;
        if (p.weapon !== this.weaponType) {
            this.weaponType = p.weapon;
            this.#setIcon(this.weapon, this.game.sprites.weaponIcons[p.weapon]);
            InventoryView.#set(this.weapon.text, data.weapons[p.weapon].name);
        }
        const left = Math.max(0, p.weaponUntil - state.tick);
        this.weapon.fill.style.width = `${(left / data.weaponDuration) * 100}%`;
        InventoryView.#set(this.weapon.seconds, `${Math.ceil(left / 60)}`);
    }

    #row(name, image) {
        const item = document.createElement('li');
        item.className = `hud__item hud__item--${name}`;
        const icon = item.appendChild(document.createElement('img'));
        icon.className = 'hud__icon';
        icon.alt = '';
        const text = item.appendChild(document.createElement('span'));
        this.list.append(item);
        const row = { item, icon, text };
        if (image) this.#setIcon(row, image);
        return row;
    }

    /** Le sprite (un canvas) devient une image ; sa taille vient du style (même case pour toutes les icônes). */
    #setIcon(row, canvas) {
        if (!this.icons.has(canvas)) this.icons.set(canvas, canvas.toDataURL());
        row.icon.src = this.icons.get(canvas);
    }

    /** Le DOM n'est touché que si le texte change (on passe ici à chaque image). */
    static #set(el, text) {
        if (el.textContent !== text) el.textContent = text;
    }
}
