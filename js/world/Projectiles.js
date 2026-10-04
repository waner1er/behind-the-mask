/** Tout ce qui vole : bouteilles, grenades, armes lancées, vinyles du héros. */
export class Projectiles {
    constructor(game) {
        this.game = game;
        this.state = game.state;
    }

    update() {
        const { game, state } = this;
        const p = state.player;
        state.projectiles = state.projectiles.filter((shot) => {
            shot.x += shot.vx;
            shot.y += shot.vy;
            shot.z += shot.vz;
            shot.vz -= shot.gravity ?? 0.08;
            shot.t = (shot.t ?? 0) + 1;

            if (shot.owner !== 'hero') {
                const hitsPlayer = Math.abs(shot.z - p.z) < 30 && Math.abs(p.x - shot.x) < 8 && Math.abs(p.y - shot.y) < 6;
                if (hitsPlayer && game.combat.damagePlayer(shot.damage, shot.x - shot.vx * 10)) return false;
            }

            if (shot.z > 0) return true;
            this.#land(shot);
            return false;
        });
    }

    #land(shot) {
        const { game } = this;
        if (shot.sprite === 'grenade' || shot.sprite === 'vinyl') {
            game.explosions.blast(shot.x, shot.y, shot.owner === 'hero');
        } else if (game.sprites.weaponIcons[shot.sprite]) {
            // une arme lancée qui rate sa cible reste par terre : à ramasser !
            game.sfx('metal');
            game.pickups.spawn('weapon', shot.x, shot.y, { weapon: shot.sprite });
        } else {
            this.state.sparks.push({ x: shot.x, y: shot.y - 4, t: 0 });
            game.sfx(shot.sprite === 'bottle' ? 'glass' : 'metal');
        }
    }
}
