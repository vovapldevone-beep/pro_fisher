/**
 * Icon contours, one source of truth. Rendered by `components/shared/AppIcon.vue`.
 *
 * The contract every entry follows:
 *   · 24×24 coordinate system, drawing inscribed in the middle 20×20
 *   · outline style, stroke-width 2, round caps and joins — set by AppIcon,
 *     never here, so an icon inherits `currentColor` from whatever contains it
 *   · a string is one <path>; an array becomes one <path> per item
 *
 * Names are not free-form: the achievements API sends them as data
 * (`['id' => 'big_carp', 'icon' => 'fish']`), so these keys and the strings in
 * `CabinetController::buildAchievements()` must stay in step.
 */
export const icons = {
    // A fish seen from the side; the eye is a zero-length segment, which a round
    // linecap renders as a dot — the same trick as the comment icon in PostCard.
    fish: [
        'M6 12Q9 8.5 13.5 8.5Q17.5 8.5 20 12Q17.5 15.5 13.5 15.5Q9 15.5 6 12Z',
        'M6 12L3 9.5M6 12L3 14.5',
        'M16.5 11.2h.01',
    ],

    lake: [
        'M3 10L7 6.5L11 10L15 5.5L21 10',
        'M3 14H21',
        'M5 17.5H19',
        'M7 21H17',
    ],

    star: 'M12 3L14.8 8.7L21 9.6L16.5 14L17.6 20L12 17L6.4 20L7.5 14L3 9.6L9.2 8.7L12 3Z',

    trophy: [
        'M8 4H16V9A4 4 0 0112 13A4 4 0 018 9V4Z',
        'M8 5H5A3 3 0 008 10',
        'M16 5H19A3 3 0 0116 10',
        'M12 13V18',
        'M9 20H15',
    ],

    camera: [
        'M4 7H8L10 5H14L16 7H20V19H4V7Z',
        'M12 16A3 3 0 1012 10A3 3 0 0012 16Z',
    ],

    // Heroicons v2 outline — a hand-built crescent came out lopsided
    moon: 'M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z',

    map: [
        'M3 6L9 4L15 6L21 4V18L15 20L9 18L3 20V6Z',
        'M9 4V18',
        'M15 6V20',
    ],

    people: [
        'M9 11A3 3 0 109 5A3 3 0 009 11Z',
        'M17 12A2.5 2.5 0 1017 7A2.5 2.5 0 0017 12Z',
        'M4 20Q4 16 9 16Q14 16 14 20',
        'M14 20Q14 17 17 17Q20 17 20 20',
    ],

    // ── Statistics and navigation ─────────────────────────────────────────────

    // Shank, bend, point, barb, and a curl at the top standing in for the eye —
    // a closed eye loop turns into a blob once the stroke is applied.
    hook: [
        'M17 3.5V15',
        'M17 15A5 5 0 0 1 7 15',
        'M7 15V11',
        'M7 11L10 13.5',
        'M17 3.5A2.5 2.5 0 0 0 14 5.5',
    ],

    users: [
        'M9 11A3 3 0 109 5A3 3 0 009 11Z',
        'M17 12A2.5 2.5 0 1017 7A2.5 2.5 0 0017 12Z',
        'M4 20Q4 16 9 16Q14 16 14 20',
        'M14 20Q14 17 17 17Q20 17 20 20',
    ],

    heart: 'M12 20L10.6 18.7C6.2 14.7 3.5 12.2 3.5 8.8A4.3 4.3 0 017.8 4.5C9.6 4.5 10.8 5.4 12 6.8C13.2 5.4 14.4 4.5 16.2 4.5A4.3 4.3 0 0120.5 8.8C20.5 12.2 17.8 14.7 13.4 18.7L12 20Z',

    'map-pin': [
        'M12 21Q6 15.5 6 10A6 6 0 1118 10Q18 15.5 12 21Z',
        'M12 12A2 2 0 1012 8A2 2 0 0012 12Z',
    ],

    search: [
        'M11 18A7 7 0 1011 4A7 7 0 0011 18Z',
        'M16 16L21 21',
    ],

    shield: 'M12 3L19 6V11C19 16 16 19 12 21C8 19 5 16 5 11V6L12 3Z',

    // Heroicons v2 outline — a circle with radial spokes reads as a sun, not a cog
    gear: [
        'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.077-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
        'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ],

    doc: [
        'M6 3H13L18 8V21H6V3Z',
        'M13 3V8H18',
        'M9 12H15',
        'M9 16H15',
    ],

    grid: [
        'M4 4H10V10H4Z',
        'M14 4H20V10H14Z',
        'M4 14H10V20H4Z',
        'M14 14H20V20H14Z',
    ],

    user: [
        'M12 11A4 4 0 1012 3A4 4 0 0012 11Z',
        'M5 20Q5 15 12 15Q19 15 19 20',
    ],

    pulse: [
        'M3 12H7',
        'M7 12L9.5 7L12.5 17L15 10L16.5 12',
        'M16.5 12H21',
    ],

    // ── Interface ─────────────────────────────────────────────────────────────

    close: ['M6 6L18 18', 'M18 6L6 18'],

    plus: ['M12 5V19', 'M5 12H19'],

    minus: 'M5 12H19',

    check: 'M5 13L10 18L19 7',

    'chevron-left': 'M15 18L9 12L15 6',
    'chevron-right': 'M9 18L15 12L9 6',
    'chevron-down': 'M6 9L12 15L18 9',

    // Three quarters of a ring — pair it with Tailwind's `animate-spin`
    spinner: 'M21 12a9 9 0 1 1-6.219-8.56',

    pencil: [
        'M4 20L8 19L18.5 8.5A2 2 0 0015.5 5.5L5 16L4 20Z',
        'M14 7L17 10',
    ],

    trash: [
        'M5 7H19',
        'M9 7V5H15V7',
        'M7 7L8 20H16L17 7',
        'M10 10V17',
        'M14 10V17',
    ],

    // Circles rather than zero-length dots: at stroke-width 2 a dot is half the
    // weight of the surrounding strokes and all but vanishes at 16px.
    'dots-vertical': [
        'M12 5A1 1 0 1 0 12 7A1 1 0 1 0 12 5',
        'M12 11A1 1 0 1 0 12 13A1 1 0 1 0 12 11',
        'M12 17A1 1 0 1 0 12 19A1 1 0 1 0 12 17',
    ],

    image: [
        'M3 5H21V19H3V5Z',
        'M7 15L11 11L16 16L18 14L21 17',
        'M8 9A1.5 1.5 0 108 6A1.5 1.5 0 008 9',
    ],

    upload: [
        'M12 15V5',
        'M8 9L12 5L16 9',
        'M4 17V20H20V17',
    ],

    crosshair: [
        'M12 3V6.5',
        'M12 17.5V21',
        'M3 12H6.5',
        'M17.5 12H21',
        'M12 16.5A4.5 4.5 0 1012 7.5A4.5 4.5 0 0012 16.5Z',
    ],

    comment: [
        'M4 5H20V16H9L5 20V16H4V5Z',
        'M8 11h.01',
        'M12 11h.01',
        'M16 11h.01',
    ],

    send: 'M3 11L21 3L15 21L12 13L3 11',

    calendar: [
        'M5 5H19V20H5V5Z',
        'M5 9H19',
        'M8 3V7',
        'M16 3V7',
    ],

    // The beam spans the full width so the pans hang off its ends, not off air
    scale: [
        'M12 4V18',
        'M5 8H19',
        'M5 8L3 12H7L5 8',
        'M19 8L17 12H21L19 8',
        'M9 20H15',
    ],

    logout: [
        'M9 4H4V20H9',
        'M12 12H20',
        'M17 9L20 12L17 15',
    ],

    // Equator and two meridians only — the extra latitude curves this started
    // with turned the whole thing into a solid blob below about 20px.
    globe: [
        'M12 3A9 9 0 1012 21A9 9 0 0012 3Z',
        'M3 12H21',
        'M12 3C9 6 9 18 12 21',
        'M12 3C15 6 15 18 12 21',
    ],

    expand: [
        'M9 3H3V9',
        'M3 3L9 9',
        'M15 3H21V9',
        'M21 3L15 9',
        'M3 21H9V15',
        'M3 21L9 15',
        'M21 21H15V15',
        'M21 21L15 15',
    ],

    // Lucide, scaled 0.91 about the centre: Lucide draws to a wider field than
    // the rest of this set and would otherwise sit ~10% larger than its neighbours.
    phone: 'M 21.1 16.477 v 2.73 a 1.82 1.82 0 0 1 -1.984 1.82 a 18.009 18.009 0 0 1 -7.853 -2.794 a 17.745 17.745 0 0 1 -5.46 -5.46 a 18.009 18.009 0 0 1 -2.794 -7.89 A 1.82 1.82 0 0 1 4.82 2.9 h 2.73 a 1.82 1.82 0 0 1 1.82 1.565 a 11.684 11.684 0 0 0 0.637 2.557 a 1.82 1.82 0 0 1 -0.41 1.92 L 8.442 10.098 a 14.56 14.56 0 0 0 5.46 5.46 l 1.156 -1.156 a 1.82 1.82 0 0 1 1.92 -0.41 a 11.684 11.684 0 0 0 2.557 0.637 A 1.82 1.82 0 0 1 21.1 16.477 z',

    card: [
        'M3 6H21V18H3V6Z',
        'M3 10H21',
        'M6 15H10',
        'M14 15H18',
    ],

    bell: [
        'M12 4A4 4 0 0016 8V11C16 13 17 15 19 17H5C7 15 8 13 8 11V8A4 4 0 0012 4Z',
        'M10 19A2 2 0 0014 19',
    ],

    // One stem, not two: parallel lines 2 units apart merge into a solid block
    // once the stroke is applied.
    filter: [
        'M4 5H20',
        'M7 10H17',
        'M10 15H14',
        'M12 15V20',
    ],

    link: [
        'M 10.18 12.91 a 4.55 4.55 0 0 0 6.861 0.491 l 2.73 -2.73 a 4.55 4.55 0 0 0 -6.434 -6.434 l -1.565 1.556',
        'M 13.82 11.09 a 4.55 4.55 0 0 0 -6.861 -0.491 l -2.73 2.73 a 4.55 4.55 0 0 0 6.434 6.434 l 1.556 -1.556',
    ],
};

/** Shown when the backend sends an icon name we have no contour for. */
export const FALLBACK_ICON = 'star';
