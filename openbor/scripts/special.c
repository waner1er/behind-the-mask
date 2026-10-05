// Les attaques spéciales des ennemis et des boss (js/actors/EnemyAI.js, js/actors/BossAI.js).
#include "data/scripts/lib.c"

// Une attaque spéciale toutes les « every » images au plus : sinon il reprend sa garde.
void special(int every)
{
    void self = getlocalvar("self");
    int next = getentityvar(self, 2);
    if (next && now() < next) {
        setidle(self);
        return;
    }
    setentityvar(self, 2, now() + webFrames(every));
}

// SUMMON : jusqu'à deux sbires, sans dépasser quatre à l'écran.
void summonMinions()
{
    int count = openborvariant("count_entities");
    int minions = 0;
    int i;
    void e;
    for (i = 0; i < count; i++) {
        e = getentity(i);
        if (e && getentityproperty(e, "exists") && getentityproperty(e, "type") == openborconstant("TYPE_ENEMY")
            && !getentityproperty(e, "dead") && getentityproperty(e, "maxhealth") < 300) {
            minions++;
        }
    }
    if (minions < 4) {
        summon("Skinhead", 340);
    }
    if (minions < 3) {
        summon("Masculinist", -20);
    }
}

void summon(char name, float x)
{
    clearspawnentry();
    setspawnentry("name", name);
    setspawnentry("coords", x, 146 + randomIndex(28), 0);
    spawn();
}

// TELEPORT : réapparaît dans le dos du héros le plus proche, prêt à frapper.
void teleportBehind()
{
    void self = getlocalvar("self");
    void target = findtarget(self);
    if (!target) {
        return;
    }
    int right = getentityproperty(target, "direction");
    float x = getentityproperty(target, "x") + 24;
    if (right) {
        x = x - 48;
    }
    float left = openborvariant("xpos") + 12;
    if (x < left) {
        x = left;
    }
    if (x > left + 296) {
        x = left + 296;
    }
    changeentityproperty(self, "position", x, getentityproperty(target, "z"), 0);
    changeentityproperty(self, "direction", right);
}
