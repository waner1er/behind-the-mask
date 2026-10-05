// Scripts des héros : Wall of Death (A + Y) et ramassage de la caisse « WOD ».
#include "data/scripts/lib.c"

// WALL OF DEATH : cinq gros durs chargent à travers l'écran depuis le dos du héros.
// Sans Wall of Death en réserve, le geste s'arrête net.
void wallOfDeath()
{
    void self = getlocalvar("self");
    int p = getentityproperty(self, "playerindex");
    int wods = getglobalvar("wods" + p);
    if (!wods) {
        setidle(self);
        return;
    }
    setglobalvar("wods" + p, 0);
    setglobalvar("wodPower", wods);
    int right = getentityproperty(self, "direction");
    float from = 410;
    if (right) {
        from = -90;
    }
    clearspawnentry();
    setspawnentry("name", "Wall");
    setspawnentry("coords", from, 160, 0);
    void wall = spawn();
    changeentityproperty(wall, "direction", right);
    message("WALL OF DEATH !!!", "", "", "", "", 2);
    sound("wod");
}

// Appelé quand le héros ramasse un objet : la caisse « WOD » met un Wall of Death en réserve.
void onGet()
{
    void self = getlocalvar("self");
    void item = getentityproperty(self, "opponent");
    if (!item) {
        return;
    }
    if (getentityproperty(item, "defaultname") == "Wod") {
        int p = getentityproperty(self, "playerindex");
        setglobalvar("wods" + p, getglobalvar("wods" + p) + 1);
        shout("WALL OF DEATH ! (A + Y)", getentityproperty(self, "x"), getentityproperty(self, "z") - 48, 1);
    }
}
