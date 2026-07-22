<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-4 font-bold text-slate-900">{{ t('cabinet.activity') }}</h3>

        <div class="space-y-4">
            <div
                v-for="(item, index) in activity"
                :key="index"
                class="flex items-start gap-3"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm"
                    :class="avatarClass(item.type)"
                >
                    <span v-if="item.author_name">{{ item.author_name.slice(0, 2).toUpperCase() }}</span>
                    <span v-else>{{ typeEmoji(item.type) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-slate-700">
                        <template v-for="(part, i) in getMessageParts(item)" :key="i">
                            <router-link
                                v-if="part.to"
                                :to="part.to"
                                class="font-semibold text-emerald-600 hover:underline"
                            >{{ part.text }}</router-link>
                            <span v-else>{{ part.text }}</span>
                        </template>
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ timeAgo(item.created_at) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    activity: {
        type: Array,
        default: () => [],
    },
});

function getMessageParts(item) {
    const { type, data = {} } = item;
    const name = data.name || '';
    const userId = data.id;

    if ((type === 'following' || type === 'follower') && name && userId) {
        const fullMsg = t(`activity.${type}`, data);
        const idx = fullMsg.indexOf(name);
        if (idx === -1) return [{ text: fullMsg }];
        return [
            idx > 0 ? { text: fullMsg.slice(0, idx) } : null,
            { text: name, to: `/fishers/${userId}` },
            idx + name.length < fullMsg.length ? { text: fullMsg.slice(idx + name.length) } : null,
        ].filter(Boolean);
    }

    return [{ text: t(`activity.${type}`, data) }];
}

function typeEmoji(type) {
    const map = { catch: '🎣', comment: '💬', achievement: '⭐', follower: '👤', following: '➕' };
    return map[type] || '•';
}

function avatarClass(type) {
    const map = {
        catch: 'bg-emerald-100 text-emerald-700',
        comment: 'bg-blue-100 text-blue-700',
        achievement: 'bg-amber-100 text-amber-700',
        follower: 'bg-slate-100 text-slate-600',
        following: 'bg-violet-100 text-violet-700',
    };
    return map[type] || 'bg-slate-100';
}

function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const diff = Date.now() - date.getTime();
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (hours < 1) return t('time.justNow');
    if (hours < 24) return t('time.hoursAgo', { hours });
    return t('time.daysAgo', { days });
}
</script>
