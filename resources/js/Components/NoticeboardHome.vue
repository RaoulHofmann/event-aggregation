<script setup>
import { ref, computed } from "vue";
import { ArrowUpRight, ArrowRight } from "lucide-vue-next";
import EventProgrammeDemo from "@/Components/EventProgrammeDemo.vue";
import ApiExample from "@/Components/ApiExample.vue";
const exampleType = ref("workshop");
const exampleOrigin = ref("native");
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
            <a href="/" class="wordmark" aria-label="EventNest home"
                >eventnest<ArrowUpRight aria-hidden="true"
            /></a>
            <nav aria-label="Main navigation">
                <a href="#templates">Templates</a
                ><a href="/docs/api">API docs</a><a href="/pricing">Pricing</a
                ><a href="/login">Sign in</a>
            </nav>
        </header>
        <div class="work-in-progress" role="note">
            <strong>A work in progress.</strong> Access and usage are currently
            free. Future pricing is still to be determined.
            <a href="#early-access">About early access</a>
        </div>
        <main>
            <div class="hero-band">
                <section class="hero">
                    <div class="hero-copy">
                        <p class="eyebrow">FOR PEOPLE WHO PUT THINGS ON.</p>
                        <h1>ONE DIARY.<br />ALL YOUR<br /><em>EVENTS.</em></h1>
                        <p class="intro">
                            Create events, connect your event platforms, and
                            give your team one place to manage the whole
                            programme.
                        </p>
                        <a href="/register" class="dark-button"
                            >Start your workspace
                            <ArrowUpRight aria-hidden="true" /></a
                        ><a class="quiet-link" href="#api-first"
                            >Building with event data? Start with the API.</a
                        >
                    </div>
                    <EventProgrammeDemo />
                </section>
            </div>
            <div class="template-band">
                <section id="templates" class="template-story">
                    <header class="template-section-heading">
                        <div>
                            <p class="eyebrow">YOUR PROGRAMME, YOUR FIELDS</p>
                            <h2>Every event has<br />its own details.</h2>
                        </div>
                        <p>
                            Try a workshop or a community event. Your template
                            keeps the right questions together, whether you
                            create the event here or import it.
                        </p>
                    </header>
                    <div class="template-workbench">
                        <div class="workbench-top">
                            <div class="workbench-wordmark">
                                <span class="workbench-mark" aria-hidden="true"
                                    >e.</span
                                ><span
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
                            <span class="layout-note"
                                >Standard details + your custom fields</span
                            >
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
                                <p class="sample-origin">
                                    {{
                                        exampleOrigin === "native"
                                            ? "Created directly in EventNest."
                                            : "Humanitix import · example connection."
                                    }}
                                </p>
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
                                    <span class="editable-badge"
                                        >Editable in EventNest</span
                                    >
                                </div>
                                <TransitionGroup
                                    name="field-change"
                                    tag="dl"
                                    class="workbench-fields"
                                    ><div
                                        v-for="(
                                            [label, value], index
                                        ) in exampleFields"
                                        :key="label"
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
                                    </div></TransitionGroup
                                >
                                <div class="source-note">
                                    <ArrowRight
                                        aria-hidden="true"
                                        class="source-note-arrow"
                                    />
                                    <p>
                                        {{
                                            exampleOrigin === "native"
                                                ? "Start with the standard event details, then add the information your team needs."
                                                : "Match imported details to your template, then fill in the extra answers locally. Local edits are kept on refresh."
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <footer class="workbench-footer">
                            <span
                                >One consistent structure for native and
                                imported events.</span
                            ><span>No changes are sent back to Humanitix.</span>
                        </footer>
                    </div>
                    <p class="integration-caption">
                        Humanitix is the first supported event-host connection.
                        More integrations are planned.
                    </p>
                </section>
            </div>
            <div class="api-band">
                <section id="api-first" class="api-first">
                    <div class="api-copy">
                        <p class="eyebrow">ALL SOURCES. ONE RESPONSE.</p>
                        <h2>
                            Your events.<br />Your applications.<br /><em
                                >One API.</em
                            >
                        </h2>
                        <p>
                            Build a website calendar, an event directory or your
                            own app using the same event data your team manages
                            in EventNest.
                        </p>
                        <p>
                            One request returns events from every connected
                            source. Leave out the source filter to include
                            native and imported events in the same response.
                            Read event records or request a date range of
                            calendar occurrences, including repeating events and
                            local changes.
                        </p>
                        <ul>
                            <li>
                                Bearer-token access, scoped to your workspace
                            </li>
                            <li>
                                Published-event filters and paginated event
                                records
                            </li>
                            <li>
                                UTC timestamps with the event’s local timezone
                            </li>
                        </ul>
                        <a class="dark-button" href="/docs/api"
                            >Read the API docs <ArrowUpRight aria-hidden="true"
                        /></a>
                        <p class="api-note">
                            Examples use illustrative data and a token supplied
                            by you. No account data is requested here.
                        </p>
                    </div>
                    <ApiExample />
                </section>
            </div>
            <div class="access-band">
                <section id="early-access" class="early-access">
                    <p class="eyebrow">EARLY ACCESS</p>
                    <h2>Still taking shape.<br />Currently free to use.</h2>
                    <p>
                        EventNest is a work in progress. You can access and use
                        the platform for free while we develop it. Features may
                        evolve, and future pricing is still to be determined.
                    </p>
                </section>
            </div>
        </main>
        <div class="footer-band">
            <footer class="home-footer">
                <a href="/" class="wordmark"
                    >eventnest <ArrowUpRight aria-hidden="true"
                /></a>
                <p>For the things that bring people out.</p>
                <nav aria-label="Footer navigation">
                    <a href="/pricing">Pricing & early access</a
                    ><a href="/docs/api">API documentation</a
                    ><a href="/login">Sign in</a>
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
.api-first,
.early-access,
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
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.6px;
    white-space: nowrap;
}
.wordmark svg {
    margin-left: 4px;
}
.home-header nav {
    display: flex;
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
.work-in-progress a {
    text-decoration: underline;
    text-underline-offset: 4px;
    margin-left: 12px;
}
.hero {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 65px;
    align-items: center;
    padding: 58px 0 64px;
}
.eyebrow {
    font-size: 12px;
    font-weight: 750;
    letter-spacing: 0.09em;
    margin: 0 0 24px;
}
.hero h1 {
    font-size: clamp(58px, 6.2vw, 88px);
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
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 50px;
    margin-bottom: 42px;
}
.template-story h2,
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
    max-width: 390px;
    color: #625c6f;
    margin: 0 0 3px;
}
.template-workbench {
    border: 1px solid #b9b2c6;
    border-radius: 8px;
    background: #fffdf9;
    overflow: hidden;
    box-shadow: 0 24px 48px #3d324c0d;
    rotate: -0.35deg;
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
    display: flex;
    width: 30px;
    height: 30px;
    align-items: center;
    justify-content: center;
    border: 1px solid #c7b386;
    background: #f0cf6d;
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -2px;
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
.layout-note {
    font-size: 11px;
    color: #786b83;
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
    margin-bottom: 32px;
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
.editable-badge {
    font-size: 10px;
    white-space: nowrap;
    border-radius: 20px;
    border: 1px solid #ded5e6;
    background: #f7f2fa;
    color: #75627f;
    padding: 6px 10px;
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
.source-note {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    border-top: 1px dashed #dfd6e4;
    margin-top: 28px;
    padding-top: 20px;
    color: #786983;
}
.source-note > svg {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    font-size: 24px;
    line-height: 1;
}
.source-note > p {
    font-size: 12px;
    line-height: 1.9;
    margin: 0;
    max-width: 500px;
}
.workbench-footer {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 26px;
    border-top: 1px solid #e6e0e9;
    background: #f9f7fb;
    color: #74667d;
    font-size: 10px;
    line-height: 1.8;
}
.integration-caption {
    font-size: 12px;
    color: #887c93;
    line-height: 1.9;
    margin: 26px 0 0;
}
.sample-change-enter-active,
.sample-change-leave-active {
    transition:
        opacity 0.45s ease,
        transform 0.5s ease;
}
.sample-change-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.sample-change-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
.field-change-enter-active,
.field-change-leave-active {
    transition:
        opacity 0.45s ease,
        transform 0.5s ease;
}
.field-change-leave-active {
    position: absolute;
    pointer-events: none;
}
.field-change-enter-from,
.field-change-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
.api-first {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 65px;
    align-items: start;
    padding: 60px 0 65px;
    border-top: 1px solid #cdbb9f;
}
.api-copy > p:not(.eyebrow):not(.api-note) {
    font-size: 18px;
    line-height: 1.85;
    margin: 0 0 20px;
}
.api-copy ul {
    list-style: disc;
    padding-left: 22px;
    margin: 24px 0 30px;
    font-size: 16px;
    line-height: 1.9;
}
.api-copy .api-note {
    font-size: 13px;
    margin: 20px 0 0;
    line-height: 1.8;
    color: #796b59;
}
.early-access {
    padding: 35px 0 40px;
    border-top: 1px solid #cdbb9f;
}
.early-access h2 {
    font-size: 32px;
    letter-spacing: -1px;
    line-height: 1.15;
    font-weight: 800;
    margin: 0 0 22px;
}
.early-access > p:last-child {
    max-width: 780px;
    font-size: 18px;
    line-height: 1.85;
    margin: 0;
}
.home-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    border-top: 1px solid #cdbb9f;
    padding: 28px 0 35px;
}
.home-footer p {
    font-size: 14px;
    margin: 0;
    color: #796b59;
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
        font-size: 78px;
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
    .work-in-progress a {
        margin-left: 0;
        display: block;
    }
    .hero {
        padding: 35px 0 45px;
    }
    .hero h1 {
        font-size: 64px;
        letter-spacing: -3px;
    }
    .eyebrow {
        font-size: 10px;
        margin-bottom: 20px;
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
    .template-story > div > p:not(.eyebrow),
    .api-copy > p:not(.eyebrow):not(.api-note) {
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
    .early-access {
        padding: 30px 0 35px;
    }
    .early-access h2 {
        font-size: 30px;
    }
    .early-access > p:last-child {
        font-size: 17px;
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
.access-band {
    background: #f1dfb9;
    border-top: 1px solid #d8c59f;
}
.footer-band {
    background: #302b25;
    color: #f7e9d5;
}
.api-first,
.early-access,
.home-footer {
    border-top: 0;
}
.api-copy h2 {
    margin-bottom: 25px;
}
.home-footer p {
    color: #b9a88f;
}
.home-footer nav {
    color: #e2d0b6;
}
@media (max-width: 1050px) {
    .template-story {
        display: block;
    }
    .template-section-heading {
        align-items: flex-start;
        gap: 25px;
    }
    .template-section-heading > p {
        max-width: 330px;
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
    .editable-badge {
        font-size: 9px;
    }
}
@media (max-width: 700px) {
    .template-section-heading {
        flex-direction: column;
        gap: 20px;
        margin-bottom: 30px;
    }
    .template-section-heading > p {
        font-size: 16px;
        max-width: none;
    }
    .template-workbench {
        rotate: 0;
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
    .layout-note {
        font-size: 10px;
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
    .workbench-footer {
        padding: 17px 20px;
        flex-direction: column;
        gap: 7px;
    }
    .template-story {
        padding: 40px 0;
    }

    .integration-caption {
        font-size: 11px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .event-sample,
    .sample-change-enter-active,
    .sample-change-leave-active,
    .field-change-enter-active,
    .field-change-leave-active {
        transition: none;
    }
    .sample-change-enter-from,
    .sample-change-leave-to,
    .field-change-enter-from,
    .field-change-leave-to {
        transform: none;
    }
}
</style>
