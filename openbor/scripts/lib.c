// Fonctions partagées par les scripts de Vigilante : temps, messages, cris, sons.
// Le temps du moteur compte 200 unités par seconde ; le jeu web compte 60 images par seconde.

int now()
{
    return openborvariant("elapsed_time");
}

// Durée en images du jeu web (60 par seconde) convertie en temps du moteur.
int webFrames(int frames)
{
    return frames * 200 / 60;
}

// Message au centre de l'écran (jusqu'à 5 lignes, "" = ligne vide), pendant « seconds » secondes.
void message(char l0, char l1, char l2, char l3, char l4, float seconds)
{
    setglobalvar("msg0", l0);
    setglobalvar("msg1", l1);
    setglobalvar("msg2", l2);
    setglobalvar("msg3", l3);
    setglobalvar("msg4", l4);
    setglobalvar("msgUntil", now() + seconds * 200);
}

// Texte qui s'envole au-dessus d'un point du niveau (cri, bonus...), comme game.shout() côté web.
void shout(char text, float x, float y, int font)
{
    int slot = getglobalvar("shoutNext");
    if (!slot) {
        slot = 0;
    }
    setglobalvar("shoutText" + slot, text);
    setglobalvar("shoutX" + slot, x);
    setglobalvar("shoutY" + slot, y);
    setglobalvar("shoutFont" + slot, font);
    setglobalvar("shoutAt" + slot, now());
    setglobalvar("shoutNext", (slot + 1) % 6);
}

void sound(char name)
{
    int id = getglobalvar("sample_" + name);
    if (!id) {
        id = loadsample("data/sounds/" + name + ".wav");
        setglobalvar("sample_" + name, id);
    }
    playsample(id, 0, 120, 120, 100, 0);
}

int randomIndex(int count)
{
    int r = rand() % count;
    if (r < 0) {
        r = r + count;
    }
    return r;
}
