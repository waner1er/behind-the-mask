// Scripts des héros : Wall of Death (A + Y) ; le ramassage est dans pickup.c.
#include "data/scripts/lib.c"

// WALL OF DEATH : cinq gros durs chargent à travers l'écran depuis le dos du héros.
// Sans Wall of Death en réserve, le geste s'arrête net.
void wallOfDeath()
{
    void self = getlocalvar("self");
    int p = getentityproperty(self, "playerindex");
    int wods = counter("wods" + p);
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
