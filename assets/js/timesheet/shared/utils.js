/**
 * Protège un texte avant de l'insérer dans du HTML (innerHTML / template string).
 * Empêche qu'un nom contenant des balises (<script>, <img onerror=…>) soit exécuté.
 */
export function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Formate un nombre d'heures à la française, avec 2 décimales : 7 → "7,00".
 * Même format que Twig : number_format(2, ',', ' ').
 */
export function formatHours(value) {
    return (Number(value) || 0).toFixed(2).replace('.', ',');
}
