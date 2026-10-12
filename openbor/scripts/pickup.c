// Ramassage en passant dessus (objets « touch », comme Pickups.collect du jeu web) :
// la caisse WOD met un Wall of Death en réserve, une arme s'équipe pour 15 s.
// Bière, cœur et vinyles n'ont pas besoin de script : le moteur ajoute leur vie ou leurs vinyles.
#include "data/scripts/lib.c"
#include "data/scripts/weapons.c"

void main()
{
    void item = getlocalvar("self");
    void hero = getlocalvar("damagetaker");
    if (!hero || getlocalvar("attacktype") != openborconstant("ATK_ITEM")) {
        return;
    }
    char name = getentityproperty(item, "defaultname");
    int p = getentityproperty(hero, "playerindex");
    float x = getentityproperty(hero, "x");
    float z = getentityproperty(hero, "z");
    if (name == "Wod") {
        setglobalvar("wods" + p, counter("wods" + p) + 1);
        shout("WALL OF DEATH ! (A + Y)", x, z - 48, 1);
        return;
    }
    int weapon = weaponNumber(name);
    if (weapon) {
        changeentityproperty(hero, "weapon", weapon, 0);
        setentityvar(hero, 4, now());
        setentityvar(hero, 5, weaponLabel(name));
        shout(weaponLabel(name) + " !", x, z - 48, 2);
    }
}
