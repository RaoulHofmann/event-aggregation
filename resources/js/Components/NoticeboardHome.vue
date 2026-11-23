<script setup>
import EventJunctionMark from "@/Components/EventJunctionMark.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { ArrowUpRight } from "lucide-vue-next";
import EventProgrammeDemo from "@/Components/EventProgrammeDemo.vue";
import ApiExample from "@/Components/ApiExample.vue";
import WorkspaceAccessDemo from "@/Components/WorkspaceAccessDemo.vue";
import ProviderSetupGuide from "@/Components/ProviderSetupGuide.vue";
import { integrationGuides } from "@/Lib/integrationGuides";

const page = usePage();
const signedIn = computed(() => !!page.props.auth?.user);
const accountHref = computed(() => signedIn.value ? route("dashboard") : route("login"));
const accountLabel = computed(() => signedIn.value ? "Dashboard" : "Sign in");
const exampleType = ref("workshop");
const exampleOrigin = ref("native");
const widgetTheme = ref("paper");
const widgetThemes = { paper: "Warm paper", sage: "Soft sage", lilac: "Lilac", ink: "Midnight" };
const exampleFields = computed(() =>
    exampleType.value === "workshop"
        ? [
              ["Audience", "All experience levels"],
              ["Equipment", "Gloves provided"],
              ["Session format", "Hands-on workshop"],
          ]
        : [
              ["Entry", "Free entry"],
              ["Accessibility", "Step-free access"],
              ["Organiser", "Local community group"],
          ],
);
</script>
<template>
    <div class="noticeboard-home">
        <header class="home-header">
            <a href="/" class="wordmark" aria-label="EventJunction home">
                <EventJunctionMark class="size-8 shrink-0" />EventJunction
            </a>
            <nav aria-label="Main navigation">
                <a href="#templates">Templates</a>
                <a href="#integrations">Integrations</a>
                <a href="#workspaces">Workspaces</a>
                <a href="#website-widget">Widget</a>
                <a href="/docs/api">API docs</a>
                <a href="/pricing">Pricing</a>
                <a :href="accountHref">{{ accountLabel }}</a>
            </nav>
        </header>
        <div id="early-access" class="work-in-progress" role="note">
            <strong>Early access</strong> · Free to use while in development. Pricing TBD.
        </div>
        <main>
            <div class="hero-band">
                <section class="hero">
                    <div class="hero-copy">
                        <h1>ALL YOUR<br />EVENTS.<br /><em>ONE PLACE.</em></h1>
                        <p class="intro">
                            Create and import events in one workspace. Read them through one API.
                        </p>
                        <a :href="signedIn ? accountHref : route('register')" class="dark-button"
                            >{{ signedIn ? "Dashboard" : "Start your workspace" }}
                            <ArrowUpRight aria-hidden="true" /></a
                        ><a class="quiet-link" href="#api-first"
                            >See API examples</a
                        >
                    </div>
                    <EventProgrammeDemo />
                </section>
            </div>
            <div class="template-band">
                <section id="templates" class="template-story">
                    <header class="template-section-heading">
                        <h2>Event templates</h2>
                        <p>Use the same template for events you create or import.</p>
                    </header>
                    <div class="template-workbench">
                        <div class="workbench-top">
                            <div class="workbench-wordmark">
                                <EventJunctionMark class="workbench-mark" />
                                <span
                                    >Event templates
                                    <span class="workbench-slash">/</span>
                                    <strong>{{
                                        exampleType === "workshop"
                                            ? "Workshop"
                                            : "Community event"
                                    }}</strong></span
                                >
                            </div>
                            <span class="example-stamp">Example workspace</span>
                        </div>
                        <div class="workbench-toolbar">
                            <div
                                class="template-switch"
                                role="group"
                                aria-label="Example event template"
                            >
                                <button
                                    type="button"
                                    :aria-pressed="exampleType === 'workshop'"
                                    @click="exampleType = 'workshop'"
                                >
                                    Workshop</button
                                ><button
                                    type="button"
                                    :aria-pressed="exampleType === 'community'"
                                    @click="exampleType = 'community'"
                                >
                                    Community event
                                </button>
                            </div>
                        </div>
                        <div class="workbench-body">
                            <aside class="event-sample" :class="exampleType">
                                <div class="sample-caption">
                                    <span>THE EVENT</span
                                    ><span class="sample-status"
                                        >Published</span
                                    >
                                </div>
                                <Transition name="sample-change"
                                    ><div
                                        :key="exampleType"
                                        class="sample-paper"
                                    >
                                        <div class="sample-date">
                                            <strong>{{
                                                exampleType === "workshop"
                                                    ? "21"
                                                    : "22"
                                            }}</strong
                                            ><span>NOV<br />2026</span>
                                        </div>
                                        <h3>
                                            {{
                                                exampleType === "workshop"
                                                    ? "Community garden workshop"
                                                    : "Neighbourhood open day"
                                            }}
                                        </h3>
                                        <p>
                                            {{
                                                exampleType === "workshop"
                                                    ? "Fremantle Community Garden"
                                                    : "Community hall, Maylands"
                                            }}
                                        </p>
                                        <div class="sample-time">
                                            {{
                                                exampleType === "workshop"
                                                    ? "Saturday · 10 am–12 pm"
                                                    : "Sunday · 10 am–3 pm"
                                            }}
                                        </div>
                                    </div></Transition
                                >
                                <div
                                    class="origin-switch"
                                    role="group"
                                    aria-label="Example event origin"
                                >
                                    <button
                                        type="button"
                                        :aria-pressed="
                                            exampleOrigin === 'native'
                                        "
                                        @click="exampleOrigin = 'native'"
                                    >
                                        Created here</button
                                    ><button
                                        type="button"
                                        :aria-pressed="
                                            exampleOrigin === 'imported'
                                        "
                                        @click="exampleOrigin = 'imported'"
                                    >
                                        Imported listing
                                    </button>
                                </div>
                                <p class="sample-origin">Source: {{ exampleOrigin === "native" ? "EventJunction" : "Humanitix" }}</p>
                            </aside>
                            <div class="field-canvas">
                                <div class="field-canvas-heading">
                                    <div>
                                        <p>CUSTOM FIELDS</p>
                                        <h3>
                                            {{
                                                exampleType === "workshop"
                                                    ? "The workshop details"
                                                    : "The community details"
                                            }}
                                        </h3>
                                    </div>
                                </div>
                                <dl class="workbench-fields">
                                    <div
                                        v-for="([label, value], index) in exampleFields"
                                        :key="index"
                                        :class="{ 'wide-field': index === 2 }"
                                    >
                                        <dt>
                                            {{ label
                                            }}<span>{{
                                                index === 2
                                                    ? "Long text"
                                                    : "Text"
                                            }}</span>
                                        </dt>
                                        <dd>{{ value }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <WorkspaceAccessDemo />
            <div class="api-band">
                <section id="api-first" class="api-first">
                    <div class="api-copy">
                        <h2><em>One API</em><br />for all your events.</h2>
                        <p>Fetch native and imported events in one request. Use the occurrences endpoint for a date range.</p>
                        <a class="dark-button" href="/docs/api"
                            >Read the API docs <ArrowUpRight aria-hidden="true"
                        /></a>
                    </div>
                    <ApiExample />
                </section>
            </div>
            <div class="widget-band">
                <section id="website-widget" class="widget-showcase" aria-labelledby="website-widget-heading">
                    <div>
                        <h2 id="website-widget-heading">Upcoming events on your website</h2>
                        <p>Match the widget to your site, then let visitors open event details without leaving the page.</p>
                        <p>Workspace owners can enable it from <strong>Website widget</strong> in the sidebar and paste the embed code into an HTML block.</p>
                        <div class="widget-themes" role="group" aria-label="Example widget colour theme">
                            <button v-for="(label, theme) in widgetThemes" :key="theme" type="button" :aria-pressed="widgetTheme === theme" @click="widgetTheme = theme">{{ label }}</button>
                        </div>
                    </div>
                    <iframe :src="route('widgets.upcoming.example', { theme: widgetTheme }, false)" title="Example upcoming events widget" loading="lazy"></iframe>
                </section>
            </div>
            <div class="integrations-band">
                <section id="integrations" class="integrations-section" aria-labelledby="integrations-heading">
                    <header class="integrations-heading">
                        <h2 id="integrations-heading">Available integrations</h2>
                        <a class="integrations-link" href="/integrations">
                            Open integrations <ArrowUpRight aria-hidden="true" />
                        </a>
                    </header>
                    <ul class="integration-directory">
                        <li v-for="(info, provider) in integrationGuides" :key="provider">
                            <h3>{{ info.label }}</h3>
                            <p>{{ info.text }}</p>
                            <ProviderSetupGuide :provider="provider" />
                        </li>
                    </ul>
                </section>
            </div>
        </main>
        <div class="footer-band">
            <footer class="home-footer">
                <a href="/" class="wordmark">
                    <EventJunctionMark class="size-8 shrink-0" />EventJunction
                </a>
                <nav aria-label="Footer navigation">
                    <a href="/pricing">Pricing</a
                    ><a href="/docs/api">API documentation</a
                    ><a :href="accountHref">{{ accountLabel }}</a>
                    <a href="/privacy-policy">Privacy</a>
                    <a href="/terms-of-service">Terms</a>
                </nav>
            </footer>
        </div>
    </div>
</template>
<style scoped>
.noticeboard-home {
    background: #fff0dc;
    color: #29251f;
    font:
        18px/1.7 Arial,
        Helvetica,
        sans-serif;
    --page-width: 1280px;
    --page-gutter: 32px;
}
.noticeboard-home a {
    color: inherit;
    text-decoration: none;
}
.noticeboard-home svg {
    display: inline-block;
    width: 1.1em;
    height: 1.1em;
    vertical-align: -0.15em;
}
.noticeboard-home a:focus-visible,
.noticeboard-home button:focus-visible {
    outline: 2px solid #9b5f3f;
    outline-offset: 4px;
}
.home-header,
.work-in-progress,
.hero,
.template-story,
.integrations-section,
.widget-showcase,
.api-first,
.home-footer {
    box-sizing: border-box;
    width: min(var(--page-width), calc(100% - var(--page-gutter) * 2));
    margin-inline: auto;
}
.home-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 25px 0;
    border-bottom: 1px solid #cdbb9f;
}
.wordmark {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.6px;
    white-space: nowrap;
}
.home-header nav {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    font-size: 16px;
    font-weight: 600;
}
.work-in-progress {
    padding: 15px 0;
    border-bottom: 1px solid #dcc7a7;
    font-size: 14px;
    line-height: 1.8;
}
.work-in-progress strong {
    margin-right: 6px;
}
.hero {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 65px;
    align-items: center;
    padding: 58px 0 64px;
}
.hero h1 {
    font-size: clamp(56px, 5.95vw, 84px);
    font-weight: 900;
    line-height: 0.95;
    letter-spacing: -0.065em;
    margin: 0;
}
.hero em,
.api-copy em {
    font-style: normal;
    color: #b34b33;
}
.intro {
    font-size: 21px;
    line-height: 1.75;
    max-width: 480px;
    margin: 28px 0;
}
.dark-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    background: #302b25;
    color: #fff8ec !important;
    padding: 16px 22px;
    font-size: 17px;
    font-weight: 700;
    line-height: 1.5;
}
.quiet-link {
    display: block;
    font-size: 15px;
    margin-top: 22px;
    text-decoration: underline !important;
    text-underline-offset: 5px;
}
.template-story {
    padding: 64px 0 55px;
}
.template-section-heading {
    margin-bottom: 32px;
}
.template-story h2,
.integrations-heading h2,
.widget-showcase h2,
.api-copy h2 {
    font-size: clamp(34px, 3.5vw, 48px);
    font-weight: 850;
    line-height: 1.1;
    letter-spacing: -1.6px;
    margin: 0;
}
.template-section-heading > p {
    font-size: 17px;
    line-height: 1.85;
    color: #625c6f;
    margin: 10px 0 0;
}
.template-workbench {
    border: 1px solid #b9b2c6;
    border-radius: 12px;
    background: #fffdf9;
    overflow: hidden;
    box-shadow: 0 24px 48px #3d324c0d;
}
.workbench-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-bottom: 1px solid #e6e0e9;
    padding: 20px 26px;
    background: #f8f6fb;
    font-size: 13px;
}
.workbench-wordmark {
    display: flex;
    align-items: center;
    gap: 13px;
}
.workbench-mark {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
}
.workbench-slash {
    color: #b4aabd;
    margin: 0 10px;
}
.example-stamp {
    font-size: 11px;
    color: #8a7d96;
}
.workbench-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 18px 26px;
    border-bottom: 1px solid #e6e0e9;
}
.template-switch {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.template-switch button {
    font-size: 13px;
    padding: 9px 16px;
    border-radius: 5px;
    border: 1px solid #dfd9e5;
    background: #fff;
}
.template-switch button[aria-pressed="true"] {
    background: #3f374a;
    color: #fff;
    border-color: #3f374a;
}
.workbench-body {
    display: grid;
    grid-template-columns: minmax(270px, 1fr) minmax(0, 1.8fr);
}
.event-sample {
    display: grid;
    grid-template-rows: auto minmax(220px, 1fr) auto auto;
    padding: 30px;
    border-right: 1px solid #e6e0e9;
    background: #f7edce;
    transition: background 0.5s ease;
}
.event-sample.community {
    background: #e8ecdc;
}
.sample-caption {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
}
.sample-status {
    font-size: 9px;
    letter-spacing: 0;
    color: #576948;
    border: 1px solid #a7b58c;
    background: #f6f9ed;
    padding: 3px 7px;
    border-radius: 3px;
}
.sample-date {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 28px 0 23px;
}
.sample-date strong {
    font-size: 54px;
    font-weight: 800;
    letter-spacing: -3px;
    line-height: 1;
}
.sample-date span {
    font-size: 10px;
    font-weight: 650;
    line-height: 1.8;
    letter-spacing: 0.05em;
}
.sample-paper {
    grid-area: 2 / 1;
    min-height: 220px;
}
.sample-paper h3 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.9px;
    line-height: 1.12;
    margin: 0 0 15px;
    max-width: 280px;
}
.sample-paper > p {
    font-size: 13px;
    line-height: 1.8;
    margin: 0;
}
.sample-time {
    font-size: 11px;
    border-top: 1px solid #d8cbb0;
    padding-top: 15px;
    margin-top: 20px;
}
.origin-switch {
    display: flex;
    gap: 5px;
    margin-top: 27px;
    flex-wrap: wrap;
}
.origin-switch button {
    font-size: 11px;
    padding: 7px 9px;
    border: 1px solid #d3c5a7;
    border-radius: 4px;
}
.origin-switch button[aria-pressed="true"] {
    background: #fff9ec;
    border-color: #9c8860;
    font-weight: 700;
}
.sample-origin {
    font-size: 10px;
    line-height: 1.7;
    margin: 12px 0 0;
    color: #77653f;
}
.field-canvas {
    padding: 32px;
    min-width: 0;
    background: #fffdf9;
}
.field-canvas-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 26px;
}
.field-canvas-heading p {
    font-size: 10px;
    letter-spacing: 0.08em;
    color: #786b83;
    margin: 0 0 10px;
}
.field-canvas-heading h3 {
    font-size: 23px;
    font-weight: 700;
    letter-spacing: -0.6px;
    line-height: 1.2;
    margin: 0;
}
.workbench-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px 22px;
    position: relative;
    align-content: start;
    min-height: 225px;
}
.workbench-fields .wide-field {
    grid-column: 1 / -1;
}
.workbench-fields dt {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 10px;
}
.workbench-fields dt > span {
    font-size: 10px;
    font-weight: 400;
    color: #7f7187;
}
.workbench-fields dd {
    padding: 13px 15px;
    border: 1px solid #e2dbe8;
    border-radius: 5px;
    font-size: 14px;
    line-height: 1.7;
    background: white;
    min-height: 49px;
}
.wide-field dd {
    min-height: 82px;
}
.sample-change-enter-active,
.sample-change-leave-active {
    transition: opacity 0.45s ease;
}
.sample-change-enter-from,
.sample-change-leave-to {
    opacity: 0;
}
.integrations-band {
    background: #e7ecdf;
    border-bottom: 1px solid #c9d2c0;
}
.widget-band {
    background: #eeebf5;
    border-top: 1px solid #d5cde1;
    border-bottom: 1px solid #d5cde1;
}
.widget-showcase {
    display: grid;
    grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
    align-items: center;
    gap: clamp(24px, 6vw, 72px);
    padding-block: 58px;
}
.widget-showcase p {
    max-width: 420px;
    margin-top: 14px;
    font-size: 17px;
    color: #51465d;
}
.widget-showcase iframe {
    width: 100%;
    height: 420px;
    border: 1px solid #bfb4cd;
    border-radius: 12px;
    background: #fffaf1;
    box-shadow: 0 12px 28px #40344915;
}
.widget-themes { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 20px; }
.widget-themes button { border: 1px solid #bfb4cd; border-radius: 5px; padding: 5px 10px; color: #403449; font-size: 13px; }
.widget-themes button:hover { background: #e2dceb; }
.widget-themes button[aria-pressed="true"] { color: white; background: #493c56; border-color: #493c56; }
@media (max-width: 700px) {
    .widget-showcase { grid-template-columns: minmax(0, 1fr); padding-block: 40px; }
    .widget-showcase iframe { height: 405px; }
}
.integrations-section {
    padding: 58px 0;
}
.integrations-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 24px;
    margin-bottom: 27px;
}
.integrations-link {
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
    text-decoration: underline !important;
    text-underline-offset: 4px;
}
.integration-directory {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
    gap: 14px;
}
.integration-directory > li {
    border: 1px solid #c9d2c0;
    border-radius: 12px;
    background: #fbfcf7;
    min-width: 0;
    padding: 17px 20px;
}
.integration-directory h3 {
    font-size: 18px;
    font-weight: 750;
}
.integration-directory > li > p {
    font-size: 15px;
    color: #465645;
    margin: 3px 0 12px;
}
.api-first {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 65px;
    align-items: start;
    padding: 60px 0 65px;
    border-top: 1px solid #cdbb9f;
}
.api-copy > p {
    font-size: 18px;
    line-height: 1.85;
    margin: 0 0 20px;
}
.home-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    border-top: 1px solid #cdbb9f;
    padding: 28px 0 35px;
}
.home-footer nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
    font-size: 14px;
}
@media (max-width: 1050px) {
    .hero,
    .template-story,
    .api-first {
        grid-template-columns: 1fr;
        gap: 35px;
    }
    .hero h1 {
        font-size: 75px;
    }
    .hero-copy {
        max-width: 700px;
    }
    .home-footer {
        flex-wrap: wrap;
    }
    .home-header nav {
        gap: 22px;
    }
}
@media (max-width: 640px) {
    .noticeboard-home {
        --page-gutter: 24px;
    }
    .home-header {
        padding: 20px 0;
        flex-wrap: wrap;
        gap: 15px;
    }
    .wordmark {
        font-size: 28px;
    }
    .home-header nav {
        font-size: 14px;
        gap: 20px;
        flex-wrap: wrap;
    }
    .work-in-progress {
        font-size: 13px;
    }
    .hero {
        padding: 35px 0 45px;
    }
    .hero h1 {
        font-size: clamp(40px, 14vw, 62px);
        letter-spacing: -0.047em;
    }
    .intro {
        font-size: 19px;
    }
    .dark-button {
        font-size: 16px;
        gap: 15px;
        padding: 15px 18px;
    }
    .quiet-link {
        font-size: 14px;
    }
    .template-story,
    .api-first {
        padding: 40px 0 45px;
    }
    .template-story h2,
    .api-copy h2 {
        font-size: 36px;
        letter-spacing: -1.2px;
    }
    .api-copy > p {
        font-size: 17px;
    }
    .demo-topline {
        font-size: 10px;
        padding: 16px 18px;
    }
    .template-switch {
        padding: 18px 18px 0;
    }
    .demo-title {
        padding: 20px 18px 15px;
    }
    .demo-title h3 {
        font-size: 21px;
    }
    .template-demo dl {
        padding: 0 18px;
    }
    .template-demo dl > div {
        font-size: 14px;
    }
    .demo-caption {
        padding: 16px 18px;
    }
    .home-footer {
        padding: 25px 0;
        align-items: flex-start;
        flex-direction: column;
        gap: 18px;
    }
    .home-footer nav {
        gap: 15px;
        font-size: 13px;
    }
}
</style>

<style scoped>
.hero-band {
    background: #fff0dc;
}
.template-band {
    background: #e8e6f0;
    border-top: 1px solid #cfc8dc;
    border-bottom: 1px solid #cfc8dc;
}
.api-band {
    background: #fffaf0;
}
.footer-band {
    background: #302b25;
    color: #f7e9d5;
}
.api-first,
.home-footer {
    border-top: 0;
}
.api-copy h2 {
    margin-bottom: 25px;
}
.home-footer nav {
    color: #e2d0b6;
}
@media (max-width: 1050px) {
    .template-story {
        display: block;
    }
    .workbench-body {
        grid-template-columns: minmax(240px, 1fr) minmax(0, 1.6fr);
    }
    .event-sample,
    .field-canvas {
        padding: 25px;
    }
    .field-canvas-heading {
        flex-direction: column;
        align-items: flex-start;
    }
    .workbench-fields {
        grid-template-columns: 1fr;
        min-height: 310px;
        gap: 20px;
    }
    .workbench-fields .wide-field {
        grid-column: auto;
    }
}
@media (max-width: 700px) {
    .template-section-heading {
        margin-bottom: 24px;
    }
    .template-section-heading > p {
        font-size: 16px;
    }
    .workbench-body {
        grid-template-columns: 1fr;
    }
    .event-sample {
        border-right: 0;
        border-bottom: 1px solid #e6e0e9;
    }
    .workbench-top {
        padding: 17px 18px;
        font-size: 11px;
    }
    .workbench-slash {
        margin: 0 5px;
    }
    .example-stamp {
        display: none;
    }
    .workbench-toolbar {
        padding: 15px 18px;
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }
    .event-sample,
    .field-canvas {
        padding: 24px;
    }
    .sample-date {
        margin: 23px 0 20px;
    }
    .sample-paper h3 {
        font-size: 27px;
    }
    .field-canvas-heading {
        margin-bottom: 25px;
        gap: 15px;
    }
    .template-story,
    .integrations-section {
        padding: 40px 0;
    }
    .integrations-heading {
        align-items: start;
        flex-direction: column;
        gap: 12px;
    }
    .integration-directory {
        grid-template-columns: minmax(0, 1fr);
    }
}
@media (prefers-reduced-motion: reduce) {
    .event-sample,
    .sample-change-enter-active,
    .sample-change-leave-active {
        transition: none;
    }
}
</style>
