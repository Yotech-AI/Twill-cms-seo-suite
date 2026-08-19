<script setup>
/**
 * A small self-contained modal over MediaSearchController (GET
 * {admin}/seo/media?q=&page=): debounced search-as-you-type, a thumbnail
 * grid, and previous/next paging. Purely presentational beyond its own
 * search state — GeneralSection owns which field ('logo' | 'default_share')
 * is being picked and what happens with the selected result.
 */
import { ref, watch } from 'vue';

const PER_PAGE = 24;

const props = defineProps({
    open: { type: Boolean, default: false },
    api: { type: Object, required: true },
});

const emit = defineEmits(['close', 'select']);

const query = ref('');
const page = ref(1);
const results = ref([]);
const loading = ref(false);
const error = ref(null);

let searchTimer = null;

async function runSearch() {
    loading.value = true;
    error.value = null;

    try {
        const data = await props.api.searchMedia(query.value, page.value);
        results.value = (data && data.data) || [];
    } catch (err) {
        error.value = (err && err.message) || 'Search failed.';
    } finally {
        loading.value = false;
    }
}

function scheduleSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 300);
}

function onQueryInput() {
    page.value = 1;
    scheduleSearch();
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            query.value = '';
            page.value = 1;
            runSearch();
        }
    }
);

function choose(item) {
    emit('select', item);
}

function close() {
    emit('close');
}

function prevPage() {
    if (page.value <= 1) return;
    page.value -= 1;
    runSearch();
}

function nextPage() {
    // No total-pages plumbing on this small a picker: fewer results than a
    // full page is the signal there is nothing more to page to.
    if (results.value.length < PER_PAGE) return;
    page.value += 1;
    runSearch();
}
</script>

<template>
    <div v-if="open" class="tss-modal-overlay" @click.self="close">
        <div class="tss-modal" role="dialog" aria-modal="true" aria-label="Choose an image">
            <div class="tss-modal__header">
                <h3 class="tss-modal__title">Choose an image</h3>
                <button type="button" class="tss-modal__close" aria-label="Close" @click="close">&times;</button>
            </div>

            <div class="tss-modal__body">
                <input
                    v-model="query"
                    type="text"
                    class="tss-input"
                    placeholder="Search media by filename…"
                    @input="onQueryInput"
                />

                <div v-if="error" class="tss-error">
                    <span class="tss-error__text">{{ error }}</span>
                    <button type="button" class="tss-error__retry" @click="runSearch">Retry</button>
                </div>
                <p v-else-if="loading" class="tss-modal__status">Loading…</p>
                <p v-else-if="results.length === 0" class="tss-modal__status">No media found.</p>
                <div v-else class="tss-modal__grid">
                    <button
                        v-for="item in results"
                        :key="item.id"
                        type="button"
                        class="tss-modal__item"
                        @click="choose(item)"
                    >
                        <img v-if="item.thumbnail" class="tss-modal__thumb" :src="item.thumbnail" :alt="item.name" />
                        <span v-else class="tss-modal__thumb tss-modal__thumb--empty" aria-hidden="true"></span>
                        <span class="tss-modal__item-name">{{ item.name }}</span>
                    </button>
                </div>
            </div>

            <div class="tss-modal__footer">
                <button type="button" class="tss-btn tss-btn--secondary" :disabled="page <= 1" @click="prevPage">
                    Previous
                </button>
                <span class="tss-modal__page">Page {{ page }}</span>
                <button type="button" class="tss-btn tss-btn--secondary" :disabled="results.length < PER_PAGE" @click="nextPage">
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
