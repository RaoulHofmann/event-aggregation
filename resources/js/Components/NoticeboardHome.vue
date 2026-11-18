<script setup>
import { ref, computed } from "vue";
import { ArrowUpRight } from "lucide-vue-next";
import EventProgrammeDemo from "@/Components/EventProgrammeDemo.vue";
import ApiExample from "@/Components/ApiExample.vue";
const exampleType = ref("workshop");
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
            <section class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">FOR PEOPLE WHO PUT THINGS ON.</p>
                    <h1>ONE DIARY.<br />ALL YOUR<br /><em>EVENTS.</em></h1>
                    <p class="intro">
                        Create events, connect your event platforms, and give
                        your team one place to manage the whole programme.
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
            <section id="templates" class="template-story">
                <div>
                    <p class="eyebrow">YOUR FIELDS. YOUR PROGRAMME.</p>
                    <h2>The same details,<br />wherever it started.</h2>
                    <p>
                        A workshop needs different information from a community
                        event. Build a template around those details, then use
                        it for events you create and listings you import.
                    </p>
                    <p>
                        Map the fields a connected host supplies. Fill in the
                        rest locally, and keep your edits when the source
                        refreshes.
                    </p>
                    <p class="integration-note">
                        Humanitix is the first supported connection. More event
                        hosts are planned.
                    </p>
                </div>
                <div class="template-demo">
                    <div class="demo-topline">
                        <span>EXAMPLE TEMPLATE</span
                        ><span>EventNest workspace</span>
                    </div>
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
                    <div class="demo-title">
                        <span>EVENT TITLE</span>
                        <h3>
                            {{
                                exampleType === "workshop"
                                    ? "Community garden workshop"
                                    : "Neighbourhood open day"
                            }}
                        </h3>
                    </div>
                    <dl>
                        <div
                            v-for="[label, value] in exampleFields"
                            :key="label"
                        >
                            <dt>{{ label }}</dt>
                            <dd>{{ value }}</dd>
                        </div>
                    </dl>
                    <p class="demo-caption">
                        One template for native and imported events. Extra
                        details can be completed in EventNest.
                    </p>
                </div>
            </section>
            <section id="api-first" class="api-first">
                <div class="api-copy">
                    <p class="eyebrow">ALL SOURCES. ONE RESPONSE.</p>
                    <h2>
                        Your events.<br />Your applications.<br /><em
                            >One API.</em
                        >
                    </h2>
                    <p>
                        Build a website calendar, an event directory or your own
                        app using the same event data your team manages in
                        EventNest.
                    </p>
                    <p>
                        One request returns events from every connected source.
                        Leave out the source filter to include native and
                        imported events in the same response. Read event records
                        or request a date range of calendar occurrences,
                        including repeating events and local changes.
                    </p>
                    <ul>
                        <li>Bearer-token access, scoped to your workspace</li>
                        <li>
                            Published-event filters and paginated event records
                        </li>
                        <li>UTC timestamps with the event’s local timezone</li>
                    </ul>
                    <a class="dark-button" href="/docs/api"
                        >Read the API docs <ArrowUpRight aria-hidden="true"
                    /></a>
                    <p class="api-note">
                        Examples use illustrative data and a token supplied by
                        you. No account data is requested here.
                    </p>
                </div>
                <ApiExample />
            </section>
            <section id="early-access" class="early-access">
                <p class="eyebrow">EARLY ACCESS</p>
                <h2>Still taking shape.<br />Currently free to use.</h2>
                <p>
                    EventNest is a work in progress. You can access and use the
                    platform for free while we develop it. Features may evolve,
                    and future pricing is still to be determined.
                </p>
            </section>
        </main>
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
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 65px;
    align-items: center;
    padding: 55px 0 65px;
    border-top: 1px solid #cdbb9f;
}
.template-story h2,
.api-copy h2 {
    font-size: clamp(34px, 3.5vw, 48px);
    font-weight: 850;
    line-height: 1.1;
    letter-spacing: -1.6px;
    margin: 0 0 25px;
}
.template-story > div > p:not(.eyebrow) {
    font-size: 18px;
    line-height: 1.85;
    margin: 0 0 20px;
}
.template-story .integration-note {
    font-size: 14px !important;
    color: #796b59;
}
.template-demo {
    border: 1px solid #bfb198;
    background: #fffdf9;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 12px 35px #34332f08;
    min-width: 0;
}
.demo-topline {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 18px 24px;
    background: #f1e4cc;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
}
.demo-topline > span + span {
    font-weight: 400;
}
.template-switch {
    display: flex;
    padding: 20px 24px 0;
    gap: 8px;
    flex-wrap: wrap;
}
.template-switch button {
    padding: 8px 14px;
    border: 1px solid #d6c3a5;
    border-radius: 4px;
    font-size: 14px;
}
.template-switch button[aria-pressed="true"] {
    background: #f0cf6d;
    border-color: #c5a954;
}
.demo-title {
    padding: 24px 24px 15px;
}
.demo-title > span {
    font-size: 10px;
    letter-spacing: 0.08em;
    color: #796b59;
}
.demo-title h3 {
    font-size: 23px;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -0.5px;
    margin-top: 8px;
}
.template-demo dl {
    padding: 0 24px;
}
.template-demo dl > div {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 15px;
    padding: 16px 0;
    border-top: 1px solid #e8ddcd;
    font-size: 15px;
}
.template-demo dt {
    color: #796b59;
}
.template-demo dd {
    font-weight: 500;
}
.demo-caption {
    font-size: 13px;
    line-height: 1.8;
    margin: 0;
    padding: 18px 24px;
    background: #faf0df;
    color: #796b59;
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
