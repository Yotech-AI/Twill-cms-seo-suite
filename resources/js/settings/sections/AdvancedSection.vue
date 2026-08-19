<script setup>
/**
 * Robots default directives (a small chip editor over the array
 * RobotsDirectives::for() appends after index/follow), the SearchAction
 * (sitelinks searchbox) toggle + URL template WebSitePiece reads, and the
 * uninstall data-removal flag.
 */
import SectionCard from '../SectionCard.vue';

const model = defineModel({ required: true });

defineProps({
    status: { type: Object, default: () => ({}) },
});

defineEmits(['save']);

/**
 * Comma AND Enter both commit a chip — a directive like "max-snippet:-1"
 * contains no comma of its own, so either input habit works without the two
 * ever conflicting.
 */
function onDirectiveKeydown(event) {
    if (event.key !== 'Enter' && event.key !== ',') return;

    event.preventDefault();
    commitDirective(event.target);
}

function onDirectiveBlur(event) {
    commitDirective(event.target);
}

function commitDirective(input) {
    const value = input.value.trim().replace(/,$/, '');
    input.value = '';

    if (value === '') return;

    if (!model.value.robots_default_directives.includes(value)) {
        model.value.robots_default_directives.push(value);
    }
}

function removeDirective(index) {
    model.value.robots_default_directives.splice(index, 1);
}
</script>

<template>
    <SectionCard title="Advanced" description="Robots directives, the sitelinks search box, and uninstall behavior." :status="status" @save="$emit('save')">
        <div class="tss-field">
            <span class="tss-field__label">Default robots directives</span>
            <p class="tss-field__hint">
                Appended after index/follow on every page's robots meta tag — e.g. max-snippet:-1, max-image-preview:large.
            </p>
            <div class="tss-chips">
                <span v-for="(directive, index) in model.robots_default_directives" :key="directive" class="tss-chip-tag">
                    {{ directive }}
                    <button type="button" class="tss-chip-tag__remove" aria-label="Remove" @click="removeDirective(index)">
                        &times;
                    </button>
                </span>
                <input
                    type="text"
                    class="tss-chips__input"
                    placeholder="Type a directive, then Enter or comma…"
                    @keydown="onDirectiveKeydown"
                    @blur="onDirectiveBlur"
                />
            </div>
        </div>

        <label class="tss-checkbox tss-checkbox--block">
            <input v-model="model.search_action_enabled" type="checkbox" />
            <span>
                <span class="tss-checkbox__label">Sitelinks search box</span>
                <span class="tss-checkbox__hint">Adds a SearchAction to the WebSite schema node.</span>
            </span>
        </label>

        <div class="tss-field">
            <label class="tss-field__label" for="tss-search-url-template">Search URL template</label>
            <input
                id="tss-search-url-template"
                v-model="model.search_url_template"
                type="text"
                class="tss-input"
                :disabled="!model.search_action_enabled"
                placeholder="https://example.com/search?q={search_term_string}"
            />
            <p class="tss-field__hint">Must contain the literal {search_term_string} placeholder.</p>
        </div>

        <label class="tss-checkbox tss-checkbox--block">
            <input v-model="model.uninstall_remove_data" type="checkbox" />
            <span>
                <span class="tss-checkbox__label">Remove SEO data on uninstall</span>
                <span class="tss-checkbox__hint">
                    A host-side uninstall step may read this flag to decide whether to drop the package's tables.
                    Twill SEO itself never deletes anything automatically.
                </span>
            </span>
        </label>
    </SectionCard>
</template>
