/**
 * Les commandes affichées sur l'écran de pause, selon ce que le joueur a en main :
 * manette (noms Xbox, ou PlayStation si c'en est une), écran tactile, ou clavier (à deux : un pavé par joueur).
 * Le WALL OF DEATH est en rouge : c'est le coup qu'on ne devine pas tout seul.
 * Pas de majuscules accentuées : la police pixel n'en a pas.
 */
export class ControlsHelp {
    static WOD = 'WALL OF DEATH';

    static XBOX = [
        ['STICK / CROIX', 'MARCHER'],
        ['A', 'COUP DE KATANA'],
        ['B', 'COUP DE PIED SAUTE'],
        ['X', 'VINYLE'],
        ['Y', 'SKATE'],
        ['A + X OU RB', ControlsHelp.WOD],
        ['START', 'PAUSE / VALIDER'],
    ];

    static PLAYSTATION = [
        ['STICK / FLECHES', 'MARCHER'], // « croix » désigne déjà le bouton ✕
        ['CROIX', 'COUP DE KATANA'],
        ['ROND', 'COUP DE PIED SAUTE'],
        ['CARRE', 'VINYLE'],
        ['TRIANGLE', 'SKATE'],
        ['CROIX + CARRE OU R1', ControlsHelp.WOD],
        ['OPTIONS', 'PAUSE / VALIDER'],
    ];

    static TOUCH = [
        ['STICK', 'MARCHER'],
        ['KATANA', 'COUP DE KATANA'],
        ['B', 'COUP DE PIED SAUTE'],
        ['V', 'VINYLE'],
        ['C', 'SKATE'],
        ['KATANA + V', ControlsHelp.WOD],
        ['START', 'PAUSE / VALIDER'],
    ];

    static KEYBOARD = [
        ['FLECHES', 'MARCHER'],
        ['ESPACE', 'COUP DE KATANA'],
        ['B', 'COUP DE PIED SAUTE'],
        ['V', 'VINYLE'],
        ['C', 'SKATE'],
        ['ESPACE + V', ControlsHelp.WOD],
        ['ENTREE', 'PAUSE / VALIDER'],
    ];

    /** À deux au clavier AZERTY : action, 1P, 2P (voir DUO_KEYS dans Input.js). */
    static DUO_KEYBOARD = [
        ['', 'PETE', 'BAPT'],
        ['MARCHER', 'Z Q S D', 'O K L M'],
        ['COUP DE KATANA', 'V', ','],
        ['COUP DE PIED SAUTE', 'W', '!'],
        ['VINYLE', 'X', ':'],
        ['SKATE', 'C', ';'],
        [ControlsHelp.WOD, 'V + X', ', + :'],
        ['PAUSE / VALIDER', 'ENTREE', 'ENTREE'],
    ];

    constructor(game) {
        this.game = game;
    }

    html() {
        const rows = this.#rows();
        const cells = rows.flatMap((row) => row.map((text, i) => {
            const classes = ['hud__control'];
            if (i === 0 && text) classes.push('hud__control--key');
            if (row.includes(ControlsHelp.WOD)) classes.push('hud__control--wod');
            return `<span class="${classes.join(' ')}">${text}</span>`;
        }));
        return `<span class="hud__controls" style="--columns: ${rows[0].length}">${cells.join('')}</span>`
            + '<span class="hud__small">WALL OF DEATH : UN PAR NIVEAU, DANS LA CAISSE « ? »</span>';
    }

    #rows() {
        const [pad] = this.game.gamepads.connected();
        if (pad) return /dualsense|dualshock|playstation/i.test(pad.id) ? ControlsHelp.PLAYSTATION : ControlsHelp.XBOX;
        if (matchMedia('(pointer: coarse)').matches) return ControlsHelp.TOUCH;
        return this.game.state.duo ? ControlsHelp.DUO_KEYBOARD : ControlsHelp.KEYBOARD;
    }
}
