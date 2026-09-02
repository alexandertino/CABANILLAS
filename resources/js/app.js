import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';

const appName =
    import.meta.env.VITE_APP_NAME ||
    'Clínica Cabanillas';

const pages = import.meta.glob(
    './pages/**/*.vue'
);

createInertiaApp({
    title: (title) =>
        title
            ? `${title} - ${appName}`
            : appName,

    resolve: async (name) => {
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(
                `Página Inertia no encontrada: ${name}`
            );
        }

        return (await page()).default;
    },

    progress: {
        color: '#0f766e',
    },
});