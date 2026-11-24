import { mkdir, writeFile } from "node:fs/promises";
import { createInertiaApp } from "@inertiajs/vue3";
import { createSSRApp, h } from "vue";
import { renderToString } from "vue/server-renderer";
import Welcome from "./Pages/Welcome.vue";
import Pricing from "./Pages/Pricing.vue";
import Api from "./Pages/Docs/Api.vue";
import EmbedCalendar from "./Pages/Docs/EmbedCalendar.vue";
import Blog from "./Pages/Blog.vue";

const directory = new URL("../../storage/app/public-pages/", import.meta.url);
const pages = {
    Welcome: { component: Welcome, url: "/" },
    Pricing: { component: Pricing, url: "/pricing" },
    "Docs/Api": { component: Api, url: "/docs/api" },
    "Docs/EmbedCalendar": { component: EmbedCalendar, url: "/guides/embed-event-calendar" },
    Blog: { component: Blog, url: "/blog" },
};
const routes = {
    login: "/login",
    register: "/register",
    "widgets.upcoming.example": "/embed/upcoming/example",
    "l5-swagger.default.api": "/api/documentation",
};

function route(name, parameters = {}) {
    if (!Object.hasOwn(routes, name)) {
        throw new Error(`Add the public route “${name}” to the prerender routes.`);
    }
    const query = new URLSearchParams(parameters).toString();
    return routes[name] + (query ? `?${query}` : "");
}

// Public components use the same route helper when rendered in the browser.
globalThis.route = route;
await mkdir(directory, { recursive: true });
for (const [name, entry] of Object.entries(pages)) {
    let markup;
    await createInertiaApp({
        page: { component: name, url: entry.url, version: null, props: { auth: { user: null }, pageTitle: "EventJunction" } },
        resolve: () => entry.component,
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) }).use(plugin);
            app.config.globalProperties.route = route;
            markup = renderToString(app);
            return app;
        },
    });
    await writeFile(new URL(name.replace("/", "-") + ".html", directory), await markup);
}
