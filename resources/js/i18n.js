import { createI18n } from 'vue-i18n';
import uk from './locales/uk.json';
import pl from './locales/pl.json';

const savedLocale = localStorage.getItem('locale') || 'uk';

export const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'uk',
    messages: { uk, pl },
});

// The blade shell ships lang="uk"; a returning PL visitor kept it until they
// touched the switcher, so screen readers and Chrome's translate prompt saw
// Ukrainian on a Polish page.
document.documentElement.lang = savedLocale;

export function setLocale(locale) {
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
}
