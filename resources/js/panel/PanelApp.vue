<script setup>
/**
 * Orchestrates the bridge, the debounced analyze cycle, and every child
 * component. Kept as the one place that knows about network requests,
 * debounce/abort/stale-response bookkeeping, and how to interpret a bridge
 * change — every child below it is a plain, mostly-presentational component
 * driven entirely by props.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { createTwillFormBridge } from '../bridge/twillFormBridge.js';
import { createApi } from '../api.js';
import { resolveColors } from './colors.js';
import ScoreChips from './ScoreChips.vue';
import SnippetPreview from './SnippetPreview.vue';
import AnalysisResults from './AnalysisResults.vue';
import InsightsCard from './InsightsCard.vue';
import SavedModeBanner from './SavedModeBanner.vue';
import StaleContentNotice from './StaleContentNotice.vue';

const props = defineProps({
    config: { type: Object, required: true },
});

const bridge = createTwillFormBridge();
const api = createApi(props.config);
const colors = resolveColors(props.config);

const debounceMs = Number(props.config.debounceMs) > 0 ? Number(props.config.debounceMs) : 500;

const mode = ref('saved');
const locale = ref(props.config.locale || 'en');
const liveFields = ref({});
const report = ref(null);
const meta = ref(null);
const loading = ref(false);
const error = ref(null);
const staleContent = ref(false);

// Plain (non-reactive) bookkeeping — none of this needs to trigger a
// re-render on its own; it only ever drives the refs above.
let mountBlocksHash = null;
let lastFieldsSignature = '';
let requestSeq = 0;
let controller = null;
let debounceTimer = null;
let pendingWhileHidden = false;

const initialForLocale = computed(() => {
    const initial = props.config.initial;
    return (initial && initial[locale.value]) || null;
});

const displaySeoScore = computed(
    () => report.value?.seo?.score ?? initialForLocale.value?.seo_score ?? null
);
const displayReadabilityScore = computed(
    () => report.value?.readability?.score ?? initialForLocale.value?.readability_score ?? null
);

function signatureOf(fields) {
    try {
        return JSON.stringify(fields || {});
    } catch {
        return '';
    }
}

/**
 * Maps the bridge's field names onto the analyze endpoint's payload keys.
 * Most are the same name; `seo_keyphrase` is NOT — the Twill form field is
 * named seo_keyphrase (see SeoFields.php), but AnalyzeRequest::rules()
 * validates it as `fields.keyphrase` (src/Http/Requests/AnalyzeRequest.php).
 * Sending seo_keyphrase here would be silently dropped by Laravel's
 * validated() (it only returns keys with a matching rule), and a live
 * keyphrase edit would never reach the engine at all.
 *
 * Only keys that were actually read are ever included — undefined means
 * "the bridge found nothing to say about this field", which must stay
 * distinct from an empty string the editor genuinely typed (an explicit ''
 * override IS sent, and is meaningful to PaperFactory's ?? fallback chain).
 */
function mapFieldsForRequest(fields) {
    const mapped = {};
    if (!fields) return mapped;

    if (fields.title !== undefined && fields.title !== null) mapped.title = fields.title;
    if (fields.seo_title !== undefined && fields.seo_title !== null) mapped.seo_title = fields.seo_title;
    if (fields.seo_description !== undefined && fields.seo_description !== null) {
        mapped.seo_description = fields.seo_description;
    }
    if (fields.seo_keyphrase !== undefined && fields.seo_keyphrase !== null) {
        mapped.keyphrase = fields.seo_keyphrase;
    }
    if (fields.slug !== undefined && fields.slug !== null) mapped.slug = fields.slug;

    return mapped;
}

function buildPayload(snapshot) {
    const base = {
        type: props.config.model && props.config.model.type,
        id: props.config.model && props.config.model.id,
        locale: locale.value,
    };

    const fields = mapFieldsForRequest(snapshot && snapshot.fields);

    // Mode-agnostic on purpose: whether we are in true "saved" mode, or a
    // degraded live-dom read that happened to find nothing, an empty
    // `fields` and no `fields` key at all mean the exact same thing to
    // AnalyzeController — omitting it is just the tidier payload.
    return Object.keys(fields).length > 0 ? { ...base, fields } : base;
}

async function runAnalyze() {
    if (document.hidden) {
        // Paused while the tab is hidden — a background tab burning through
        // the throttle limit on a debounce timer nobody can see is pure
        // waste. Re-armed by onVisibilityChange below.
        pendingWhileHidden = true;
        return;
    }

    const seq = ++requestSeq;

    if (controller) controller.abort();
    controller = new AbortController();

    loading.value = true;
    error.value = null;

    let snapshot = null;
    try {
        snapshot = bridge.read();
    } catch {
        snapshot = null;
    }

    const payload = buildPayload(snapshot);

    try {
        const data = await api.analyze(payload, controller.signal);
        if (seq !== requestSeq) return; // a newer request already superseded this one

        report.value = data.report;
        meta.value = data.meta;
    } catch (err) {
        if (seq !== requestSeq) return;
        if (err && err.name === 'AbortError') return; // superseded, not a real failure

        error.value = (err && err.message) || 'Analysis failed.';
    } finally {
        if (seq === requestSeq) loading.value = false;
    }
}

function scheduleAnalyze() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runAnalyze, debounceMs);
}

/** The one shared "Re-analyze" action — also used as the error state's retry. */
function reanalyze() {
    clearTimeout(debounceTimer);
    runAnalyze();
}

function onBridgeChange() {
    let snapshot;
    try {
        snapshot = bridge.read();
    } catch {
        return; // read() is already defensive; this is a last-resort guard
    }

    // Always kept current for the snippet preview, independent of the
    // debounced network cycle below — that preview is pure client-side
    // rendering of what was just typed and should never wait on a
    // round-trip.
    liveFields.value = snapshot.fields || {};

    const nextLocale = snapshot.locale;
    const localeChanged = !!nextLocale && nextLocale !== locale.value;
    if (localeChanged) {
        locale.value = nextLocale;
        // Drop the previous locale's report immediately instead of letting
        // it linger under the new locale's chips until the fresh response
        // lands — the cached `initial` score for the new locale (if any) is
        // a truer placeholder than another language's stale numbers.
        report.value = null;
        meta.value = null;
    }

    if (snapshot.blocksHash !== null && mountBlocksHash !== null) {
        staleContent.value = snapshot.blocksHash !== mountBlocksHash;
    }

    const fieldsSignature = signatureOf(snapshot.fields);
    const fieldsChanged = fieldsSignature !== lastFieldsSignature;
    lastFieldsSignature = fieldsSignature;

    if (localeChanged) {
        clearTimeout(debounceTimer);
        runAnalyze();
    } else if (fieldsChanged) {
        scheduleAnalyze();
    }
    // A blocks[-only change (both false) only updated staleContent above —
    // block content is never sent, and drift alone never triggers a request.
}

function onVisibilityChange() {
    if (!document.hidden && pendingWhileHidden) {
        pendingWhileHidden = false;
        runAnalyze();
    }
}

onMounted(async () => {
    document.addEventListener('visibilitychange', onVisibilityChange);

    try {
        await bridge.acquire();
    } catch {
        // acquire() is documented to never throw, but a panel mount is not
        // the place to find out the hard way.
    }

    mode.value = bridge.mode();

    let snapshot;
    try {
        snapshot = bridge.read();
    } catch {
        snapshot = { locale: null, fields: {}, blocksHash: null };
    }

    liveFields.value = snapshot.fields || {};
    if (snapshot.locale) locale.value = snapshot.locale;
    mountBlocksHash = snapshot.blocksHash ?? null;
    lastFieldsSignature = signatureOf(snapshot.fields);

    bridge.subscribe(onBridgeChange);

    // Immediate (non-debounced) run on mount, per the brief.
    runAnalyze();
});

onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    clearTimeout(debounceTimer);
    if (controller) controller.abort();
    bridge.dispose();
});
</script>

<template>
    <div class="tss-panel" :class="{ 'tss-panel--loading': loading }">
        <div class="tss-panel__header">
            <ScoreChips :seo-score="displaySeoScore" :readability-score="displayReadabilityScore" :colors="colors" />
            <button type="button" class="tss-panel__reanalyze" :disabled="loading" @click="reanalyze">
                Re-analyze
            </button>
        </div>

        <SavedModeBanner v-if="mode === 'saved'" />
        <StaleContentNotice v-if="staleContent" />

        <div v-if="error" class="tss-error">
            <span class="tss-error__text">{{ error }}</span>
            <button type="button" class="tss-error__retry" @click="reanalyze">Retry</button>
        </div>

        <SnippetPreview
            :fields="liveFields"
            :model-title="config.model && config.model.title"
            :url="config.model && config.model.url"
        />

        <template v-if="report">
            <InsightsCard :insights="report.insights" />
            <AnalysisResults title="SEO analysis" :results="(report.seo && report.seo.results) || []" :colors="colors" />
            <AnalysisResults
                title="Readability analysis"
                :results="(report.readability && report.readability.results) || []"
                :colors="colors"
            />
        </template>
        <div v-else-if="loading" class="tss-skeleton" aria-hidden="true">
            <div class="tss-skeleton__bar"></div>
            <div class="tss-skeleton__bar"></div>
            <div class="tss-skeleton__bar tss-skeleton__bar--short"></div>
        </div>

        <div v-if="loading && report" class="tss-spinner" role="status" aria-label="Updating analysis"></div>
    </div>
</template>
