/** Meilleur score, gardé dans le navigateur (le stockage peut être indisponible : navigation privée...). */
export class HiscoreStore {
    constructor(key) {
        this.key = key;
    }

    load() {
        try {
            return parseInt(localStorage.getItem(this.key) ?? '0', 10) || 0;
        } catch {
            return 0;
        }
    }

    save(score) {
        try {
            localStorage.setItem(this.key, String(score));
        } catch {
            // stockage indisponible : tant pis
        }
    }
}
