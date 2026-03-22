import '../css/app.css';
import 'animate.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTheme } from '@/composables/useTheme.js';

const { initTheme } = useTheme();
initTheme();

createInertiaApp({
    title: (title) => title ? `${title} — Lambda CMS` : 'Lambda CMS',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({ methods: { route } })
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: false,
    },
});
