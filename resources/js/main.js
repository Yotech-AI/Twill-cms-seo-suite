import { createApp } from 'vue';
import PanelApp from './panel/PanelApp.vue';
import './styles.css';

// Mount-type registry, kept as a lookup table specifically so a new mount
// type is one more entry here, not a rewrite of the boot logic below.
const MOUNTS = {
    panel: PanelApp,
};

function parseConfig(element) {
    try {
        return JSON.parse(element.dataset.twillSeo || '{}');
    } catch {
        console.error('[twill-seo] invalid config payload on', element);
        return null;
    }
}

function mount(element) {
    const type = element.dataset.twillSeoMount;
    const Component = MOUNTS[type];

    if (!Component) {
        console.error(`[twill-seo] unknown mount type "${type}"`);
        return;
    }

    const config = parseConfig(element);
    if (!config) return;

    createApp(Component, { config }).mount(element);
}

function boot() {
    document.querySelectorAll('[data-twill-seo-mount]').forEach(mount);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    // The script tag is placed at the end of body (see analysis-panel.blade.php's
    // @push('extra_js')), after Twill's own bundle — DOMContentLoaded has
    // frequently already fired by the time this module evaluates, so boot()
    // must also run immediately rather than only ever waiting for an event
    // that already happened.
    boot();
}
