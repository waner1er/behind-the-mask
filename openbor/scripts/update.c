// À chaque image du jeu : messages, cris, vinyles et Wall of Death de chaque joueur, vies en plus.
#include "data/scripts/lib.c"

void main()
{
    if (!openborvariant("in_level")) {
        return;
    }
    drawMessage();
    drawShouts();
    int p;
    for (p = 0; p < 2; p++) {
        drawPlayer(p);
        extraLife(p);
    }
}

void centered(int y, int font, char text)
{
    if (text) {
        drawstring((320 - strwidth(text, font)) / 2, y, font, text);
    }
}

void drawMessage()
{
    if (now() > getglobalvar("msgUntil")) {
        return;
    }
    centered(54, 1, getglobalvar("msg0"));
    centered(66, 0, getglobalvar("msg1"));
    centered(78, 3, getglobalvar("msg2"));
    centered(92, 2, getglobalvar("msg3"));
    centered(104, 2, getglobalvar("msg4"));
}

// Les cris montent de 20 pixels et disparaissent au bout d'une seconde.
void drawShouts()
{
    int slot;
    float age;
    char text;
    for (slot = 0; slot < 6; slot++) {
        text = getglobalvar("shoutText" + slot);
        age = now() - getglobalvar("shoutAt" + slot);
        if (text && age < 200) {
            drawstring(getglobalvar("shoutX" + slot) - openborvariant("xpos") - strwidth(text, 0) / 2,
                getglobalvar("shoutY" + slot) - age / 10, getglobalvar("shoutFont" + slot), text);
        }
    }
}

// Sous la barre de vie : le stock de vinyles et les Wall of Death en réserve.
// Un héros qui entre en jeu a 5 vinyles (VINYL.start), sur 20 au plus.
void drawPlayer(int p)
{
    void hero = getplayerproperty(p, "entity");
    if (!hero) {
        return;
    }
    if (!getentityvar(hero, 3)) {
        setentityvar(hero, 3, 1);
        changeentityproperty(hero, "mp", 50);
    }
    char text = "VINYL x" + getentityproperty(hero, "mp") / 10;
    int wods = getglobalvar("wods" + p);
    if (wods) {
        text = text + "  WOD " + wods;
    }
    int x = 8;
    if (p == 1) {
        x = 168;
    }
    drawstring(x, 30, 2, text);
}

// Une vie de plus tous les 10 000 points (LIVES.extraEvery).
void extraLife(int p)
{
    void hero = getplayerproperty(p, "entity");
    int next = getglobalvar("nextLife" + p);
    if (!next) {
        next = 10000;
    }
    if (hero && getplayerproperty(p, "score") >= next) {
        changeplayerproperty(p, "lives", getplayerproperty(p, "lives") + 1);
        shout("1UP !", getentityproperty(hero, "x"), getentityproperty(hero, "z") - 60, 2);
        sound("oneup");
        next = next + 10000;
    }
    setglobalvar("nextLife" + p, next);
}
