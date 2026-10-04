export const rand = (min, max) => min + Math.random() * (max - min);
export const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
export const pick = (list) => list[Math.floor(Math.random() * list.length)];

/** Score sur 6 chiffres : 000150. */
export const pad = (n, size = 6) => String(n).padStart(size, '0');
