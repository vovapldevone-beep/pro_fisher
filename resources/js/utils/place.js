/**
 * Shortens a place name for display in a badge.
 *
 * Nominatim (used by LakeSelect and the GPS option) returns the whole
 * administrative chain — "озеро Кірпічка, Війтівська Гора, Дрогобич,
 * Дрогобицька міська громада, Дрогобицький район, Львівська область,
 * Україна" is a real record, 117 characters, of which only the head names
 * the spot. CSS truncation alone would cut that at "озеро Кірпічк…" on a
 * narrow card, so the string is trimmed by meaning first and by width after.
 */
export function shortPlace(value, maxParts = 2, maxChars = 42) {
    const text = (value ?? '').trim();
    if (text.length <= maxChars) return text;

    const short = text
        .split(',')
        .slice(0, maxParts)
        .map(part => part.trim())
        .filter(Boolean)
        .join(', ');

    // A single overlong part (or one word) still has to be cut by width
    return short.length <= maxChars ? short : `${short.slice(0, maxChars - 1).trimEnd()}…`;
}
