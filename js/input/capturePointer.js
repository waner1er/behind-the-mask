/** Garde le doigt sur l'élément même s'il en sort. Certains navigateurs mobiles refusent : on suit alors le doigt par son identifiant. */
export function capturePointer(element, event) {
    try {
        element.setPointerCapture(event.pointerId);
    } catch {
        // pas grave
    }
}
