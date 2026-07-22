<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">

        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Адмінпанель</h1>
                <p class="mt-1 text-sm text-slate-500">Управління платформою Pro Fisher</p>
            </div>
            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Admin</span>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex gap-1 rounded-xl bg-slate-100 p-1 w-fit">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="rounded-lg px-5 py-2 text-sm font-medium transition"
                :class="activeTab === tab.key
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Dashboard -->
        <div v-if="activeTab === 'dashboard'">
            <div v-if="loadingStats" class="py-20 text-center text-slate-400">Завантаження...</div>
            <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-center"
                >
                    <p class="text-3xl font-bold" :class="card.color">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <!-- Users -->
        <div v-if="activeTab === 'users'">
            <div v-if="loadingUsers" class="py-20 text-center text-slate-400">Завантаження...</div>
            <div v-else>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Користувач</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Уловів</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дата</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Статус</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дія</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="user.avatar_url"
                                            :src="user.avatar_url"
                                            class="h-8 w-8 rounded-full object-cover"
                                        />
                                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                            {{ user.name.slice(0, 2).toUpperCase() }}
                                        </div>
                                        <span class="font-medium text-slate-900">{{ user.name }}</span>
                                        <span v-if="user.is_admin" class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700">Admin</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ user.email }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ user.catches_count }}</td>
                                <td class="px-4 py-3 text-center text-slate-400">{{ user.created_at }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="user.is_blocked
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-emerald-100 text-emerald-700'"
                                    >
                                        {{ user.is_blocked ? 'Заблоковано' : 'Активний' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        v-if="!user.is_admin"
                                        type="button"
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                        :class="user.is_blocked
                                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                            : 'bg-red-50 text-red-600 hover:bg-red-100'"
                                        :disabled="blockingId === user.id"
                                        @click="toggleBlock(user)"
                                    >
                                        {{ blockingId === user.id ? '...' : (user.is_blocked ? 'Розблокувати' : 'Заблокувати') }}
                                    </button>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :current="usersPage" :last="usersLastPage" @change="loadUsers" />
            </div>
        </div>

        <!-- Content -->
        <div v-if="activeTab === 'content'">
            <div v-if="loadingCatches" class="py-20 text-center text-slate-400">Завантаження...</div>
            <div v-else>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">
                        Всього публікацій: <span class="font-semibold text-slate-800">{{ catchesTotal }}</span>
                    </p>
                    <button
                        v-if="selectedIds.length"
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-50"
                        :disabled="bulkDeleting"
                        @click="handleBulkDelete"
                    >
                        {{ bulkDeleting ? 'Видалення...' : `Видалити вибрані (${selectedIds.length})` }}
                    </button>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50">
                            <tr>
                                <th class="w-10 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 accent-red-600"
                                        :checked="allSelected"
                                        @change="toggleSelectAll"
                                    />
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Фото</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Тип / Назва</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Автор</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Озеро</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дата</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дія</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in catches"
                                :key="item.id"
                                class="cursor-pointer select-none hover:bg-slate-50"
                                :class="selectedIds.includes(item.id) ? 'bg-red-50/50' : ''"
                                @click="toggleSelect(item.id)"
                            >
                                <td class="px-4 py-3">
                                    <!-- @click.stop: the row click already toggles; without it
                                         the event would bubble and immediately toggle back -->
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 accent-red-600"
                                        :checked="selectedIds.includes(item.id)"
                                        @click.stop
                                        @change="toggleSelect(item.id)"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <img
                                        v-if="item.photo_url"
                                        :src="item.photo_url"
                                        class="h-12 w-12 rounded-lg object-cover"
                                    />
                                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-xl">🐟</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="item.type === 'post'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-emerald-100 text-emerald-700'"
                                        >
                                            {{ item.type === 'post' ? 'Пост' : 'Улов' }}
                                        </span>
                                        <span class="font-medium text-slate-900 truncate max-w-[160px]">
                                            {{ item.fish_name || item.notes || '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ item.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ item.lake?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-slate-400">{{ item.created_at }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition"
                                        :disabled="deletingId === item.id"
                                        @click.stop="handleDelete(item)"
                                    >
                                        {{ deletingId === item.id ? '...' : 'Видалити' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :current="catchesPage" :last="catchesLastPage" @change="loadCatches" />
            </div>
        </div>

        <!-- Lakes -->
        <div v-if="activeTab === 'lakes'">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-slate-500">Всього озер: <span class="font-semibold text-slate-800">{{ lakesTotal }}</span></p>
                <button type="button"
                    class="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition"
                    @click="showAddLake = true">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Додати озеро
                </button>
            </div>
            <div v-if="loadingLakes" class="py-20 text-center text-slate-400">Завантаження...</div>
            <div v-else>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Фото</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Назва</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Регіон</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Координати</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Ціна / доба</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дата</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Дія</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="lake in lakes" :key="lake.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <img v-if="lake.photo_url" :src="lake.photo_url"
                                        class="h-12 w-16 rounded-lg object-cover" />
                                    <div v-else class="flex h-12 w-16 items-center justify-center rounded-lg bg-slate-100 text-slate-300">
                                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="m21 15-5-5L5 21"/>
                                        </svg>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ lake.name }}</div>
                                    <div class="text-xs text-slate-400">{{ lake.slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ lake.region || '—' }}</td>
                                <td class="px-4 py-3">
                                    <a
                                        :href="`https://www.openstreetmap.org/?mlat=${lake.latitude}&mlon=${lake.longitude}#map=13/${lake.latitude}/${lake.longitude}`"
                                        target="_blank"
                                        class="text-xs text-emerald-600 hover:underline"
                                    >{{ lake.latitude }}, {{ lake.longitude }}</a>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    {{ lake.price ? `${lake.price} PLN` : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-400">{{ lake.created_at }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200 transition"
                                            @click="editingLake = lake; showAddLake = true"
                                        >
                                            Редагувати
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition"
                                            :disabled="deletingLakeId === lake.id"
                                            @click="handleDeleteLake(lake)"
                                        >
                                            {{ deletingLakeId === lake.id ? '...' : 'Видалити' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :current="lakesPage" :last="lakesLastPage" @change="loadLakes" />
            </div>
        </div>

    </div>

    <!-- Add / Edit Lake Modal -->
    <AddLakeModal
        v-if="showAddLake"
        :lake="editingLake"
        @close="showAddLake = false; editingLake = null"
        @saved="onLakeSaved"
    />
</template>

<script setup>
import { computed, h, onMounted, ref, watch } from 'vue';
import {
    adminDeleteCatch,
    adminDeleteCatches,
    blockUser,
    deleteLake,
    fetchAdminCatches,
    fetchAdminLakes,
    fetchAdminStats,
    fetchAdminUsers,
    unblockUser,
} from '../../api/admin';
import AddLakeModal from './AddLakeModal.vue';

// ── Tabs ─────────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'dashboard', label: 'Дашборд' },
    { key: 'users',     label: 'Користувачі' },
    { key: 'content',   label: 'Контент' },
    { key: 'lakes',     label: 'Озера' },
];
const activeTab = ref('dashboard');

// ── Stats ─────────────────────────────────────────────────────────────────────
const stats = ref(null);
const loadingStats = ref(true);

const statCards = computed(() => {
    if (!stats.value) return [];
    return [
        { label: 'Користувачів',  value: stats.value.users_count,    color: 'text-slate-900' },
        { label: 'Заблоковано',   value: stats.value.blocked_count,   color: 'text-red-600' },
        { label: 'Уловів',        value: stats.value.catches_count,   color: 'text-emerald-600' },
        { label: 'Постів',        value: stats.value.posts_count,     color: 'text-blue-600' },
        { label: 'Лайків',        value: stats.value.likes_count,     color: 'text-pink-500' },
        { label: 'Коментарів',    value: stats.value.comments_count,  color: 'text-violet-600' },
        { label: 'Озер',          value: stats.value.lakes_count,     color: 'text-cyan-600' },
    ];
});

async function loadStats() {
    loadingStats.value = true;
    try {
        stats.value = await fetchAdminStats();
    } finally {
        loadingStats.value = false;
    }
}

// ── Users ─────────────────────────────────────────────────────────────────────
const users = ref([]);
const loadingUsers = ref(false);
const usersPage = ref(1);
const usersLastPage = ref(1);
const blockingId = ref(null);

async function loadUsers(page = 1) {
    loadingUsers.value = true;
    usersPage.value = page;
    try {
        const res = await fetchAdminUsers(page);
        users.value = res.data;
        usersLastPage.value = res.last_page;
    } finally {
        loadingUsers.value = false;
    }
}

async function toggleBlock(user) {
    blockingId.value = user.id;
    try {
        if (user.is_blocked) {
            await unblockUser(user.id);
            user.is_blocked = false;
        } else {
            await blockUser(user.id);
            user.is_blocked = true;
        }
        await loadStats();
    } finally {
        blockingId.value = null;
    }
}

// ── Content ───────────────────────────────────────────────────────────────────
const catches = ref([]);
const loadingCatches = ref(false);
const catchesPage = ref(1);
const catchesLastPage = ref(1);
const catchesTotal = ref(0);
const deletingId = ref(null);

// Selection lives within the current page: switching pages clears it, so the
// admin never deletes rows that are no longer visible.
const selectedIds = ref([]);
const bulkDeleting = ref(false);

const allSelected = computed(() =>
    catches.value.length > 0 && selectedIds.value.length === catches.value.length
);

function toggleSelect(id) {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((s) => s !== id)
        : [...selectedIds.value, id];
}

function toggleSelectAll() {
    selectedIds.value = allSelected.value ? [] : catches.value.map((c) => c.id);
}

async function loadCatches(page = 1) {
    loadingCatches.value = true;
    catchesPage.value = page;
    selectedIds.value = [];
    try {
        const res = await fetchAdminCatches(page);
        catches.value = res.data;
        catchesLastPage.value = res.last_page;
        catchesTotal.value = res.total;
    } finally {
        loadingCatches.value = false;
    }
}

async function handleDelete(item) {
    if (!confirm(`Видалити цей ${item.type === 'post' ? 'пост' : 'улов'}?`)) return;
    deletingId.value = item.id;
    try {
        await adminDeleteCatch(item.id);
        catches.value = catches.value.filter(c => c.id !== item.id);
        selectedIds.value = selectedIds.value.filter((s) => s !== item.id);
        catchesTotal.value--;
        await loadStats();
    } finally {
        deletingId.value = null;
    }
}

async function handleBulkDelete() {
    const count = selectedIds.value.length;
    if (!count || !confirm(`Видалити ${count} публікацій? Це незворотно.`)) return;

    bulkDeleting.value = true;
    try {
        await adminDeleteCatches(selectedIds.value);
        // Reload the page: deleting shifts pagination, a local filter would lie
        await Promise.all([loadCatches(catchesPage.value), loadStats()]);
    } finally {
        bulkDeleting.value = false;
    }
}

// ── Lakes ─────────────────────────────────────────────────────────────────────
const lakes = ref([]);
const loadingLakes = ref(false);
const lakesPage = ref(1);
const lakesLastPage = ref(1);
const lakesTotal = ref(0);
const showAddLake = ref(false);
const editingLake = ref(null);
const deletingLakeId = ref(null);

async function loadLakes(page = 1) {
    loadingLakes.value = true;
    lakesPage.value = page;
    try {
        const res = await fetchAdminLakes(page);
        lakes.value = res.data;
        lakesLastPage.value = res.last_page;
        lakesTotal.value = res.total;
    } finally {
        loadingLakes.value = false;
    }
}

function onLakeSaved(lake, wasEdit) {
    showAddLake.value = false;
    editingLake.value = null;
    if (wasEdit) {
        const idx = lakes.value.findIndex(l => l.id === lake.id);
        if (idx !== -1) lakes.value[idx] = lake;
    } else {
        lakes.value.unshift(lake);
        lakesTotal.value++;
    }
    loadStats();
}

async function handleDeleteLake(lake) {
    if (!confirm(`Видалити озеро «${lake.name}»? Улови користувачів збережуться, але втратять прив'язку до озера.`)) return;
    deletingLakeId.value = lake.id;
    try {
        await deleteLake(lake.id);
        lakes.value = lakes.value.filter(l => l.id !== lake.id);
        lakesTotal.value--;
        await loadStats();
    } finally {
        deletingLakeId.value = null;
    }
}

// ── Pagination ────────────────────────────────────────────────────────────────
// A render function, not a `template` string: Vite ships the runtime-only Vue
// build, which cannot compile templates at runtime.
const BTN_CLASS = 'rounded-lg border border-slate-200 px-3 py-1.5 text-sm disabled:opacity-40';

const Pagination = {
    props: { current: Number, last: Number },
    emits: ['change'],
    setup(props, { emit }) {
        return () => {
            if (props.last <= 1) return null;

            return h('div', { class: 'mt-4 flex items-center justify-center gap-2' }, [
                h('button', {
                    type: 'button',
                    class: BTN_CLASS,
                    disabled: props.current === 1,
                    onClick: () => emit('change', props.current - 1),
                }, '← Назад'),
                h('span', { class: 'text-sm text-slate-500' }, `${props.current} / ${props.last}`),
                h('button', {
                    type: 'button',
                    class: BTN_CLASS,
                    disabled: props.current === props.last,
                    onClick: () => emit('change', props.current + 1),
                }, 'Вперед →'),
            ]);
        };
    },
};

// ── Load on tab change ────────────────────────────────────────────────────────
watch(activeTab, (tab) => {
    if (tab === 'users' && !users.value.length) loadUsers();
    if (tab === 'content' && !catches.value.length) loadCatches();
    if (tab === 'lakes' && !lakes.value.length) loadLakes();
});

onMounted(loadStats);
</script>
