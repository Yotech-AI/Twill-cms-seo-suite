<script setup>
/**
 * Orchestrates the four settings sections: seeds local state from the
 * server-rendered `config.bootstrap` payload (see SettingsPageController —
 * chosen over a fetch-on-mount flag, since a settings row is already the
 * authoritative current state at render time), and saves one section at a
 * time via PUT {admin}/seo/settings. Each section owns its own fields; this
 * component owns only the shared state (sections, registry, media) and the
 * per-section save/status bookkeeping.
 */
import { reactive } from 'vue';
import { createSettingsApi } from './api.js';
import GeneralSection from './sections/GeneralSection.vue';
import ContentTypesSection from './sections/ContentTypesSection.vue';
import FeaturesSection from './sections/FeaturesSection.vue';
import AdvancedSection from './sections/AdvancedSection.vue';

const props = defineProps({
    config: { type: Object, required: true },
});

const api = createSettingsApi(props.config);

const bootstrap = props.config.bootstrap || { sections: {}, registry: [], media: {} };

const sections = reactive({
    general: { social_profiles: [], ...bootstrap.sections.general },
    content_types: { ...bootstrap.sections.content_types },
    features: { ...bootstrap.sections.features },
    advanced: { robots_default_directives: [], ...bootstrap.sections.advanced },
});

const registry = bootstrap.registry || [];
const media = reactive({ logo: null, default_share: null, ...bootstrap.media });

// Per-section save bookkeeping — {saving, error, success}, keyed the same as
// `sections` above.
const status = reactive({
    general: {},
    content_types: {},
    features: {},
    advanced: {},
});

async function save(section) {
    status[section] = { saving: true, error: null, success: false };

    try {
        const data = await api.saveSection(section, sections[section]);

        Object.assign(sections[section], data.sections[section]);
        Object.assign(media, data.media);

        status[section] = { saving: false, error: null, success: true };
    } catch (err) {
        status[section] = { saving: false, error: (err && err.message) || 'Save failed.', success: false };
    }
}
</script>

<template>
    <div class="tss-settings">
        <GeneralSection
            v-model="sections.general"
            :media="media"
            :api="api"
            :status="status.general"
            @save="save('general')"
        />
        <ContentTypesSection
            v-model="sections.content_types"
            :registry="registry"
            :status="status.content_types"
            @save="save('content_types')"
        />
        <FeaturesSection v-model="sections.features" :status="status.features" @save="save('features')" />
        <AdvancedSection v-model="sections.advanced" :status="status.advanced" @save="save('advanced')" />
    </div>
</template>
