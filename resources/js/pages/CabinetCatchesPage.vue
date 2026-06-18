<template>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <router-link to="/cabinet" class="text-sm text-emerald-700 hover:underline">
                    ← Назад до кабінету
                </router-link>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Мої улови</h1>
            </div>
            <button
                type="button"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                @click="editingCatch = null; showForm = true"
            >
                + Додати улов
            </button>
        </div>

        <div class="grid gap-8 lg:grid-cols-2">
            <CatchList
                v-if="!showForm"
                :catches="catchesStore.catches"
                :loading="catchesStore.loading"
                @edit="openEdit"
                @delete="handleDelete"
            />
            <CatchForm
                v-if="showForm"
                :lakes="lakesStore.lakes"
                :editing-catch="editingCatch"
                :saving="catchesStore.saving"
                @submit="handleSubmit"
                @cancel="showForm = false; editingCatch = null"
            />
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import CatchForm from '../components/catches/CatchForm.vue';
import CatchList from '../components/catches/CatchList.vue';
import { useCatchesStore } from '../stores/catches';
import { useLakesStore } from '../stores/lakes';

const catchesStore = useCatchesStore();
const lakesStore = useLakesStore();
const editingCatch = ref(null);
const showForm = ref(false);

function openEdit(catchItem) {
    editingCatch.value = catchItem;
    showForm.value = true;
}

async function handleSubmit(formData) {
    if (editingCatch.value) {
        await catchesStore.editCatch(editingCatch.value.id, formData);
        editingCatch.value = null;
    } else {
        await catchesStore.addCatch(formData);
    }
    showForm.value = false;
}

async function handleDelete(catchItem) {
    if (confirm('Видалити цей улов?')) {
        await catchesStore.removeCatch(catchItem.id);
        if (editingCatch.value?.id === catchItem.id) {
            editingCatch.value = null;
            showForm.value = false;
        }
    }
}

onMounted(() => {
    lakesStore.loadLakes();
    catchesStore.loadCatches();
});
</script>
