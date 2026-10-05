// La charge du Wall of Death : chaque ennemi n'est percuté qu'une fois ;
// les petits tombent K.O., un boss perd la moitié de sa vie par Wall of Death en réserve.
#include "data/scripts/lib.c"

void wallSweep()
{
    void self = getlocalvar("self");
    float x = getentityproperty(self, "x");
    int count = openborvariant("count_entities");
    int i;
    void e;
    float dx;
    int maxhealth;
    for (i = 0; i < count; i++) {
        e = getentity(i);
        if (e && getentityproperty(e, "exists") && getentityproperty(e, "type") == openborconstant("TYPE_ENEMY")
            && !getentityproperty(e, "dead") && getentityvar(e, 1) != self) {
            dx = getentityproperty(e, "x") - x;
            if (dx > -40 && dx < 40) {
                setentityvar(e, 1, self);
                maxhealth = getentityproperty(e, "maxhealth");
                // les boss ont au moins 300 points de vie, les autres au plus 90
                if (maxhealth >= 300) {
                    damageentity(e, self, maxhealth / 2 * getglobalvar("wodPower"), 1, openborconstant("ATK_NORMAL"));
                } else {
                    damageentity(e, self, 999, 1, openborconstant("ATK_NORMAL"));
                }
            }
        }
    }
}
