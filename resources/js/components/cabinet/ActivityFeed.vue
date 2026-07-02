<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">{{ t('cabinet.activity') }}</h3>
            <button type="button" class="text-sm text-blue-600 hover:underline">{{ t('common.viewAll') }}</button>
        </div>

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
                    <p class="text-sm text-slate-700">{{ getMessage(item) }}</p>
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

function getMessage(item) {
    const key = `activity.${item.type}`;
    return t(key, item.data ?? {});
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
