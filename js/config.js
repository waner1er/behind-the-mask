/** Réglages du gameplay. Durées en images (60 par seconde), distances en pixels. */

export const FPS = 60;

export const PAPER = '#ece8dc';
export const INK = '#0c0a10';

/** Fenêtres d'une attaque : le coup touche entre hitFrom et hitTo, l'image de frappe dure jusqu'à strikeUntil. */
export const ATTACK = {
    hero: { hitFrom: 5, hitTo: 9, strikeUntil: 14, end: 20 },
    enemy: { hitFrom: 26, hitTo: 29, strikeUntil: 38, end: 46 },
    boss: { hitFrom: 18, hitTo: 22, strikeUntil: 30, end: 38 },
};

export const SKATE = { duration: 34, speed: 3.2, cooldown: 70 };
export const JUMP = { impulse: 3.4, gravity: 0.22, speed: 1.7 };
export const VINYL = { start: 5, perCrate: 5, max: 20, radius: 30, damage: 4 };

export const LIVES = { start: 3, extraEvery: 10000 };
export const HERO_HP = 100;

/** Les héros jouables, par numéro de joueur : Pete (1P) et VigiBapt (2P). */
export const HEROES = ['hero', 'bapt'];

/** Un cœur tombe tous les N ennemis mis K.O. */
export const HEART_EVERY_KILLS = 10;

/** Durée de vie d'un bonus au sol. */
export const PICKUP_LIFETIME = { life: 1800, default: 600 };

/** Mode démo : Pete est invincible et frappe plus fort, pour aller au bout du jeu. */
export const DEMO = { damage: 3 };

export const HISCORE_KEY = 'vigilante-hiscore';
