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

export function setLocale(locale) {
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
}
