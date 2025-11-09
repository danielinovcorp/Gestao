import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(ZiggyVue);

        // ✅ CORREÇÃO: Configuração do CSRF sem usar require()
        if (props.initialPage.props.csrf_token) {
            // O Inertia já usa o token automaticamente, mas podemos garantir
            // que está disponível para outras bibliotecas se necessário
            console.log("CSRF Token disponível para Inertia");
        }

        app.mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
