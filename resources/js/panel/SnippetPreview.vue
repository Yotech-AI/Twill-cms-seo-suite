<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    // Current bridge field snapshot ({title, seo_title, seo_description, ...}) —
    // updated live on every bridge change, independent of the debounced
    // server round-trip, so this preview tracks keystrokes even while an
    // analyze request is still in flight.
    fields: { type: Object, default: () => ({}) },
    // Fallback title from config.model.title (the host model's own title at
    // the current locale) — used only when no seo_title has been typed yet.
    modelTitle: { type: String, default: null },
    // The model's live permalink from config.model.url, rendered breadcrumb-style.
    url: { type: String, default: null },
});

const TITLE_FONT = '18px arial';
const TITLE_MAX_WIDTH_PX = 600;
const DESCRIPTION_MAX_CHARS = { desktop: 156, mobile: 120 };
const PLACEHOLDER = 'Preview appears as you type…';

const device = ref('desktop');

function setDevice(next) {
    device.value = next;
}

// One canvas, reused for every measurement rather than created per-call.
let measureCanvasContext = null;

function measureTextWidth(text, font) {
    try {
        if (!measureCanvasContext) {
            measureCanvasContext = document.createElement('canvas').getContext('2d');
        }
        if (!measureCanvasContext) return text.length * 9;

        measureCanvasContext.font = font;
        return measureCanvasContext.measureText(text).width;
    } catch {
        // No canvas support (or an unusual embedding environment) — an
        // approximate width still lets truncation degrade sanely instead of
        // losing the whole preview to a thrown error.
        return text.length * 9;
    }
}

/** Binary search for the longest prefix (plus an ellipsis) that still fits maxWidth. */
function truncateToPixelWidth(text, maxWidth, font) {
    if (!text) return '';
    if (measureTextWidth(text, font) <= maxWidth) return text;

    let low = 0;
    let high = text.length;

    while (low < high) {
        const mid = Math.ceil((low + high) / 2);
        if (measureTextWidth(`${text.slice(0, mid)}…`, font) <= maxWidth) {
            low = mid;
        } else {
            high = mid - 1;
        }
    }

    return `${text.slice(0, low)}…`;
}

/** Clamps to maxChars without cutting a word in half, then appends an ellipsis. */
function clampToWordBoundary(text, maxChars) {
    if (!text) return '';
    if (text.length <= maxChars) return text;

    const slice = text.slice(0, maxChars);
    const lastSpace = slice.lastIndexOf(' ');
    const trimmed = (lastSpace > 0 ? slice.slice(0, lastSpace) : slice).trimEnd();

    return `${trimmed}…`;
}

function breadcrumbFromUrl(rawUrl) {
    if (!rawUrl) return null;

    try {
        const base = typeof window !== 'undefined' ? window.location.origin : undefined;
        const parsed = new URL(rawUrl, base);
        return { host: parsed.host, segments: parsed.pathname.split('/').filter(Boolean) };
    } catch {
        // A relative/malformed URL from a misconfigured host resolver must
        // not break the rest of the preview.
        return null;
    }
}

const rawTitle = computed(() => (props.fields && props.fields.seo_title) || props.modelTitle || '');
const hasTitle = computed(() => rawTitle.value !== '');
const displayTitle = computed(() =>
    hasTitle.value ? truncateToPixelWidth(rawTitle.value, TITLE_MAX_WIDTH_PX, TITLE_FONT) : PLACEHOLDER
);

const rawDescription = computed(() => (props.fields && props.fields.seo_description) || '');
const hasDescription = computed(() => rawDescription.value !== '');
const displayDescription = computed(() =>
    hasDescription.value ? clampToWordBoundary(rawDescription.value, DESCRIPTION_MAX_CHARS[device.value]) : PLACEHOLDER
);

const breadcrumb = computed(() => breadcrumbFromUrl(props.url));
</script>

<template>
    <div class="tss-snippet" :class="`tss-snippet--${device}`">
        <div class="tss-snippet__toggle" role="tablist">
            <button
                type="button"
                class="tss-snippet__toggle-btn"
                :class="{ 'tss-snippet__toggle-btn--active': device === 'desktop' }"
                role="tab"
                :aria-selected="device === 'desktop'"
                @click="setDevice('desktop')"
            >
                Desktop
            </button>
            <button
                type="button"
                class="tss-snippet__toggle-btn"
                :class="{ 'tss-snippet__toggle-btn--active': device === 'mobile' }"
                role="tab"
                :aria-selected="device === 'mobile'"
                @click="setDevice('mobile')"
            >
                Mobile
            </button>
        </div>

        <div class="tss-snippet__preview">
            <div v-if="breadcrumb" class="tss-snippet__url">
                <span class="tss-snippet__host">{{ breadcrumb.host }}</span>
                <template v-for="(segment, index) in breadcrumb.segments" :key="index">
                    <span class="tss-snippet__sep" aria-hidden="true">›</span>
                    <span class="tss-snippet__segment">{{ segment }}</span>
                </template>
            </div>

            <div class="tss-snippet__title" :class="{ 'tss-snippet__title--placeholder': !hasTitle }">
                {{ displayTitle }}
            </div>

            <div
                class="tss-snippet__description"
                :class="{ 'tss-snippet__description--placeholder': !hasDescription }"
            >
                {{ displayDescription }}
            </div>
        </div>
    </div>
</template>
