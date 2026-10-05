// Un ennemi tombe : il crie une phrase des paroles du morceau, et un cœur tombe tous les 10 K.O.
#include "data/scripts/lib.c"

void main()
{
    void self = getlocalvar("self");
    float x = getentityproperty(self, "x");
    float z = getentityproperty(self, "z");
    int count = getglobalvar("shoutCount");
    if (count) {
        shout(getglobalvar("shoutLine" + randomIndex(count)), x, z - 46, 0);
    }
    int kills = getglobalvar("kills") + 1;
    setglobalvar("kills", kills);
    if (kills % 10 == 0) {
        clearspawnentry();
        setspawnentry("name", "Life");
        setspawnentry("coords", x - openborvariant("xpos"), z, 14);
        spawn();
    }
}
