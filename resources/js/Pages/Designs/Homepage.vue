<script setup>
import { ref } from "vue";
import NoticeboardHome from "@/Components/NoticeboardHome.vue";
import { Head } from "@inertiajs/vue3";
import { ArrowUpRight, ArrowDown } from "lucide-vue-next";
const choices = [
    {
        id: "programme",
        name: "01 / The Programme",
        description: "Editorial, quiet, built around the events.",
    },
    {
        id: "desk",
        name: "02 / The Organiser’s Desk",
        description: "Practical, precise, visibly a working tool.",
    },
    {
        id: "noticeboard",
        name: "03 / The Noticeboard",
        description: "Bold type, local character, a little less polite.",
    },
];
const initial = new URLSearchParams(window.location.search).get("concept");
const concept = ref(
    choices.some((c) => c.id === initial) ? initial : "programme",
);
const choose = (id) => {
    concept.value = id;
    history.replaceState(null, "", `?concept=${id}`);
    window.scrollTo({ top: 0, behavior: "instant" });
};
const events = [
    {
        day: "21",
        month: "NOV",
        name: "After hours at the gallery",
        place: "Northbridge · 6–9 pm",
        source: "Humanitix",
    },
    {
        day: "22",
        month: "NOV",
        name: "The long-table lunch",
        place: "Fremantle · 12 noon",
        source: "EventJunction",
    },
    {
        day: "28",
        month: "NOV",
        name: "Open studios, open doors",
        place: "Maylands · 10 am–4 pm",
        source: "Humanitix",
    },
];
</script>
<template>
    <Head title="Homepage design studies" />
    <div class="studies">
        <div class="study-picker">
            <span class="study-label">EventJunction / Homepage studies</span>
            <div role="group" aria-label="Homepage concepts">
                <button
                    v-for="c in choices"
                    :key="c.id"
                    :aria-pressed="concept === c.id"
                    @click="choose(c.id)"
                >
                    {{ c.name }}
                </button>
            </div>
            <a href="/"
                >Current home
                <ArrowUpRight class="inline-arrow" aria-hidden="true"
            /></a>
        </div>
        <main v-if="concept === 'programme'" class="programme">
            <header class="p-header">
                <a href="/" class="p-wordmark">EventJunction</a
                ><span class="p-caption">A place for the whole programme.</span>
                <nav>
                    <a href="/login">Sign in</a
                    ><a href="/register"
                        >Start a workspace
                        <ArrowUpRight class="inline-arrow" aria-hidden="true"
                    /></a>
                </nav>
            </header>
            <section class="p-hero">
                <div>
                    <p class="p-kicker">For the people who put things on.</p>
                    <h1>Many events.<br />One programme.</h1>
                    <p class="p-intro">
                        The workshop you made here. The tickets you listed on
                        Humanitix. Bring them into one calendar, and get on with
                        making things happen.
                    </p>
                    <a href="/register" class="p-cta"
                        >Make room for your events
                        <span
                            ><ArrowUpRight
                                class="inline-arrow"
                                aria-hidden="true" /></span
                    ></a>
                    <p class="p-small">
                        Create directly. Import from Humanitix. Edit in one
                        place.
                    </p>
                </div>
                <aside class="p-note">
                    <span class="p-note-top">THE ORGANISER’S NOTEBOOK</span>
                    <p>Less copying.<br />Fewer tabs.<br />A clearer diary.</p>
                    <div class="p-rule"></div>
                    <span
                        >Built for a full programme.<br />Even when it lives in
                        different places.</span
                    ><span class="p-note-no">Nº 001</span>
                </aside>
            </section>
            <section class="p-programme">
                <div class="p-section-title">
                    <h2>A week, brought together.</h2>
                    <span>Example programme / November 2026</span>
                </div>
                <a
                    v-for="e in events"
                    :key="e.day"
                    href="/register"
                    class="p-event"
                    ><div class="p-date">
                        {{ e.day }}<small>{{ e.month }}</small>
                    </div>
                    <div class="p-event-name">
                        <h3>{{ e.name }}</h3>
                        <p>{{ e.place }}</p>
                    </div>
                    <span class="p-source">{{ e.source }}</span
                    ><span class="p-arrow"
                        ><ArrowUpRight
                            class="inline-arrow"
                            aria-hidden="true" /></span
                ></a>
            </section>
            <section class="p-bottom">
                <h2>One good place<br />to keep the details.</h2>
                <div>
                    <article>
                        <span>01</span>
                        <h3>Create an event.</h3>
                        <p>
                            Start with the essentials. Add a template when your
                            programme needs more detail.
                        </p>
                    </article>
                    <article>
                        <span>02</span>
                        <h3>Bring the others in.</h3>
                        <p>
                            Match Humanitix events to your templates. Local
                            changes stay yours.
                        </p>
                    </article>
                    <article>
                        <span>03</span>
                        <h3>Put the programme to work.</h3>
                        <p>
                            Month, week or agenda. One API for the events in
                            your workspace.
                        </p>
                    </article>
                </div>
            </section>
            <footer class="p-footer">
                <span>EventJunction</span
                ><span>For everything you’re putting on.</span
                ><a href="/register"
                    >Start a workspace
                    <ArrowUpRight class="inline-arrow" aria-hidden="true"
                /></a>
            </footer>
        </main>
        <main v-else-if="concept === 'desk'" class="desk">
            <header class="d-header">
                <a href="/" class="d-logo"
                    ><span class="d-mark"
                        >E<span
                            ><ArrowUpRight
                                class="inline-arrow"
                                aria-hidden="true" /></span></span
                    >EventJunction</a
                ><span>THE ORGANISER’S DESK</span>
                <nav>
                    <a href="/login">Sign in</a
                    ><a href="/register" class="d-button"
                        >Open your workspace
                        <ArrowUpRight class="inline-arrow" aria-hidden="true"
                    /></a>
                </nav>
            </header>
            <section class="d-hero">
                <div>
                    <p class="d-label"><span></span> Events, under one roof.</p>
                    <h1>Your programme<br />has a lot of<br />moving parts.</h1>
                    <p class="d-deck">
                        Give it one working home. Create your own events, bring
                        in Humanitix listings, and manage the details together.
                    </p>
                    <a href="/register" class="d-button"
                        >Get your desk in order
                        <ArrowUpRight class="inline-arrow" aria-hidden="true"
                    /></a>
                </div>
                <div class="d-board">
                    <div class="d-board-head">
                        <span>WORKSPACE / PROGRAMME</span
                        ><span>November 2026</span>
                    </div>
                    <div class="d-summary">
                        <h2>The week ahead</h2>
                        <span>Illustrative workspace</span>
                    </div>
                    <div v-for="e in events" :key="e.day" class="d-row">
                        <span class="d-date"
                            >{{ e.day }}<small>NOV</small></span
                        >
                        <div>
                            <h3>{{ e.name }}</h3>
                            <p>{{ e.place }}</p>
                        </div>
                        <span class="d-provider">{{ e.source }}</span>
                    </div>
                    <div class="d-board-foot">
                        <span>3 events. Two sources. One view.</span
                        ><span>MONTH / WEEK / AGENDA</span>
                    </div>
                </div>
            </section>
            <section class="d-pipeline">
                <div>
                    <p class="d-label">THE ROUTE IS SIMPLE</p>
                    <h2>
                        Bring it in.<br />Make it yours.<br />Use it everywhere.
                    </h2>
                </div>
                <div class="d-flow">
                    <div>
                        <span>01 / SOURCES</span>
                        <p>Native EventJunction events</p>
                        <p>Humanitix imports <small>GET only</small></p>
                    </div>
                    <b aria-hidden="true"
                        ><ArrowDown class="inline-arrow" aria-hidden="true"
                    /></b>
                    <div class="d-flow-core">
                        <span>02 / YOUR WORKSPACE</span>
                        <p>Templates · local edits · calendar</p>
                    </div>
                    <b aria-hidden="true"
                        ><ArrowDown class="inline-arrow" aria-hidden="true"
                    /></b>
                    <div>
                        <span>03 / ONE API</span>
                        <p class="d-code">GET /api/v1/events</p>
                        <p class="d-code">GET /api/v1/occurrences</p>
                    </div>
                </div>
            </section>
            <section class="d-details">
                <article>
                    <span>CONTROL</span>
                    <h3>Change the local copy.</h3>
                    <p>
                        Edit an imported event in EventJunction. Your changes are
                        kept on refresh. Nothing is written back to Humanitix.
                    </p>
                </article>
                <article>
                    <span>CONSISTENCY</span>
                    <h3>The details, your way.</h3>
                    <p>
                        Map imported fields to an existing template. Use the
                        same structure for events you create yourself.
                    </p>
                </article>
                <article>
                    <span>CLARITY</span>
                    <h3>A diary you can read.</h3>
                    <p>
                        Switch between the calendar and an agenda. Keep undated
                        drafts out of the way until they’re ready.
                    </p>
                </article>
            </section>
            <footer class="d-footer">
                <span>EventJunction / Your events department.</span
                ><a href="/register"
                    >Open your workspace
                    <ArrowUpRight class="inline-arrow" aria-hidden="true"
                /></a>
            </footer>
        </main>
        <NoticeboardHome v-else />
        <div class="study-footnote">
            {{ choices.find((c) => c.id === concept).description }} Sample event
            listings are illustrative.
        </div>
    </div>
</template>
<style scoped>
.studies {
    color: #20221f;
    background: #fff;
    font-family: Arial, Helvetica, sans-serif;
}
.studies a {
    text-decoration: none;
    color: inherit;
}
.study-picker {
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    background: #fff;
    border-bottom: 1px solid #ddd;
    font-size: 11px;
}
.study-label {
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.study-picker > div {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.study-picker button {
    padding: 9px 12px;
    border: 1px solid #d7d7d7;
    background: #fff;
}
.study-picker button[aria-pressed="true"] {
    background: #20221f;
    color: white;
    border-color: #20221f;
}
.study-picker a {
    border-bottom: 1px solid;
}
.study-footnote {
    padding: 18px 24px;
    font-size: 12px;
    background: #fff;
    color: #555;
    border-top: 1px solid #ddd;
}
.inline-arrow {
    display: inline-block;
    width: 1em;
    height: 1em;
    vertical-align: -0.12em;
}
.studies a:focus-visible,
.studies button:focus-visible {
    outline: 3px solid #d45729;
    outline-offset: 4px;
}
/* Programme: a printed cultural programme, with generous margins and fine rules. */
.programme {
    background: #f7f4ed;
    color: #302a30;
    padding: 0 6vw;
}
.p-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-bottom: 1px solid #bdb6b6;
    padding: 30px 0;
}
.p-wordmark {
    font:
        700 34px Georgia,
        serif;
    letter-spacing: -2px;
}
.p-wordmark span {
    font: 11px Arial;
    vertical-align: top;
    margin-left: 4px;
}
.p-caption {
    font:
        italic 15px Georgia,
        serif;
}
.p-header nav {
    display: flex;
    gap: 30px;
    font-size: 12px;
}
.p-hero {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 8vw;
    padding: 90px 0 80px;
}
.p-kicker,
.p-small,
.p-section-title > span,
.p-note-top,
.p-note-no {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}
.p-kicker {
    margin: 0 0 30px;
    color: #79516a;
}
.p-hero h1 {
    font:
        normal clamp(52px, 6.5vw, 100px) / 0.98 Georgia,
        serif;
    letter-spacing: -0.045em;
    margin: 0 0 30px;
}
.p-intro {
    max-width: 460px;
    font-size: 16px;
    line-height: 1.8;
}
.p-cta {
    display: inline-flex;
    gap: 60px;
    border-top: 1px solid #302a30;
    border-bottom: 1px solid #302a30;
    padding: 18px 0;
    margin-top: 32px;
    font-size: 13px;
}
.p-small {
    margin-top: 22px;
    color: #726a71;
    letter-spacing: 0.03em;
    text-transform: none;
}
.p-note {
    position: relative;
    align-self: center;
    transform: rotate(2deg);
    background: #e9dbe1;
    border: 1px solid #c7aebc;
    padding: 35px;
    min-height: 360px;
}
.p-note p {
    font:
        italic 42px/1.2 Georgia,
        serif;
    letter-spacing: -1px;
    margin: 40px 0 30px;
}
.p-note > span:not(.p-note-top):not(.p-note-no) {
    font-size: 12px;
    line-height: 1.7;
}
.p-rule {
    height: 1px;
    background: #b99bab;
    margin-bottom: 20px;
}
.p-note-no {
    position: absolute;
    right: 30px;
    bottom: 30px;
}
.p-section-title {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 20px;
    padding: 20px 0;
    border-top: 1px solid #302a30;
}
.p-section-title h2 {
    font:
        normal 30px Georgia,
        serif;
    margin: 0;
}
.p-event {
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 26px 0;
    border-top: 1px solid #c7c1bf;
}
.p-date {
    font:
        normal 45px Georgia,
        serif;
    line-height: 1;
    width: 65px;
}
.p-date small {
    display: block;
    font: 10px Arial;
    letter-spacing: 0.15em;
    margin-top: 8px;
}
.p-event-name {
    flex: 1;
}
.p-event h3 {
    font:
        normal 25px Georgia,
        serif;
    margin: 0 0 8px;
}
.p-event p {
    font-size: 12px;
    margin: 0;
    color: #766e72;
}
.p-source {
    font-size: 11px;
    border-bottom: 1px solid #aaa;
    padding-bottom: 4px;
}
.p-arrow {
    font-size: 28px;
    margin-left: 30px;
}
.p-bottom {
    display: grid;
    grid-template-columns: 1fr 1.7fr;
    gap: 5vw;
    border-top: 1px solid #302a30;
    margin-top: 30px;
    padding: 70px 0;
}
.p-bottom > h2 {
    font:
        normal 38px/1.1 Georgia,
        serif;
    margin: 0;
}
.p-bottom > div {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}
.p-bottom article > span {
    font-size: 11px;
    color: #8c647b;
}
.p-bottom h3 {
    font:
        normal 19px Georgia,
        serif;
    margin: 20px 0 12px;
}
.p-bottom article p {
    font-size: 12px;
    line-height: 1.8;
    color: #665f64;
}
.p-footer {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    border-top: 1px solid #302a30;
    padding: 25px 0;
    font-size: 11px;
}
/* Desk: ledger marks, squared edges and functional hierarchy. */
.desk {
    background: #f0f0e8;
    color: #172b28;
}
.d-header {
    padding: 24px 5vw;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    border-bottom: 1px solid #c9cec3;
}
.d-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -1px;
}
.d-mark {
    display: flex;
    background: #173c32;
    color: #dce9af;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    font-size: 23px;
}
.d-mark > span {
    font-size: 12px;
    align-self: flex-start;
    margin-top: 6px;
}
.d-header > span,
.d-label,
.d-board-head,
.d-flow span,
.d-details article > span {
    font:
        10px "Courier New",
        monospace;
    letter-spacing: 0.08em;
}
.d-header nav {
    display: flex;
    gap: 25px;
    align-items: center;
    font-size: 12px;
}
.d-button {
    display: inline-block;
    background: #173c32;
    color: #f8f9ed !important;
    padding: 16px 20px;
    font-size: 12px;
    font-weight: 700;
}
.d-hero {
    padding: 70px 5vw 90px;
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 6vw;
    align-items: center;
}
.d-label {
    display: flex;
    gap: 10px;
    align-items: center;
}
.d-label > span {
    width: 7px;
    height: 7px;
    background: #367f51;
}
.d-hero h1 {
    font-size: clamp(42px, 4.8vw, 74px);
    line-height: 1.02;
    letter-spacing: -0.055em;
    margin: 25px 0;
}
.d-deck {
    font-size: 15px;
    line-height: 1.8;
    max-width: 365px;
    margin-bottom: 30px;
}
.d-board {
    background: #fffef7;
    border: 1px solid #596d60;
    box-shadow: 8px 8px 0 #c8d1ba;
}
.d-board-head {
    display: flex;
    justify-content: space-between;
    padding: 16px;
    background: #173c32;
    color: #f0f2dc;
}
.d-summary {
    padding: 26px 22px 22px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    align-items: baseline;
}
.d-summary h2 {
    font-size: 25px;
    letter-spacing: -1px;
    margin: 0;
}
.d-summary > span {
    font-size: 9px;
    color: #687163;
}
.d-row {
    margin: 0 22px;
    border-top: 1px solid #d9ddcf;
    display: flex;
    gap: 18px;
    align-items: center;
    padding: 24px 0;
}
.d-date {
    font:
        24px "Courier New",
        monospace;
}
.d-date small {
    display: block;
    font-size: 9px;
}
.d-row > div {
    flex: 1;
}
.d-row h3 {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 7px;
}
.d-row p {
    font-size: 11px;
    color: #637267;
    margin: 0;
}
.d-provider {
    font:
        10px "Courier New",
        monospace;
}
.d-board-foot {
    padding: 17px 22px;
    border-top: 1px solid #596d60;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 10px;
}
.d-board-foot > span + span {
    font:
        8px "Courier New",
        monospace;
}
.d-pipeline {
    background: #dce6c6;
    padding: 70px 8vw;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8vw;
    border-top: 1px solid #a4b28f;
    border-bottom: 1px solid #a4b28f;
}
.d-pipeline h2 {
    font-size: 44px;
    line-height: 1.1;
    letter-spacing: -2px;
    margin-top: 30px;
}
.d-flow > div {
    padding: 18px 20px;
    border: 1px solid #71856a;
    background: #f3f4e7;
}
.d-flow p {
    font-size: 14px;
    margin: 12px 0 0;
}
.d-flow p small {
    font-size: 10px;
    margin-left: 10px;
}
.d-flow > b {
    display: block;
    font-size: 25px;
    padding-left: 25px;
    font-weight: 400;
}
.d-flow .d-flow-core {
    background: #173c32;
    color: #f0f2dc;
}
.d-code {
    font-family: "Courier New", monospace;
}
.d-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 50px;
    padding: 65px 8vw;
}
.d-details article {
    border-top: 2px solid #173c32;
    padding-top: 15px;
}
.d-details h3 {
    font-size: 22px;
    letter-spacing: -0.5px;
    margin: 25px 0 15px;
}
.d-details p {
    font-size: 13px;
    line-height: 1.8;
    color: #58685e;
}
.d-footer {
    display: flex;
    justify-content: space-between;
    padding: 25px 5vw;
    background: #173c32;
    color: #f0f2dc;
    font-size: 11px;
}
/* Noticeboard: typographic event posters and deliberate, hard colour. */
.noticeboard {
    background: #ffe9cf;
    color: #27231f;
}
.n-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 25px 5vw;
    border-bottom: 2px solid #27231f;
}
.n-logo {
    font-size: 32px;
    font-weight: 900;
    letter-spacing: -2px;
}
.n-logo > span {
    margin-left: 5px;
}
.n-header > span {
    font-size: 10px;
    font-weight: 700;
}
.n-header nav {
    display: flex;
    gap: 25px;
    font-size: 12px;
    font-weight: 700;
}
.n-hero {
    padding: 65px 5vw 75px;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 6vw;
}
.n-statement > p:first-child {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin: 0 0 28px;
}
.n-statement h1 {
    font-size: clamp(55px, 7.5vw, 115px);
    font-weight: 900;
    letter-spacing: -0.075em;
    line-height: 0.89;
    margin: 0;
}
.n-statement h1 em {
    color: #bd351d;
    font-style: normal;
}
.n-deck {
    max-width: 380px;
    font-size: 15px;
    line-height: 1.7;
    margin: 30px 0;
}
.n-button {
    display: inline-block;
    padding: 16px 22px;
    background: #27231f;
    color: #ffe9cf !important;
    font-size: 13px;
    font-weight: 700;
}
.n-posters {
    padding-top: 12px;
}
.n-poster {
    padding: 25px;
    border: 2px solid #27231f;
}
.n-poster > div,
.n-poster footer {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 9px;
    font-weight: 700;
}
.n-poster h2 {
    font-size: 66px;
    font-weight: 900;
    line-height: 0.9;
    letter-spacing: -4px;
    margin: 30px 0 18px;
}
.n-poster > p {
    font-size: 12px;
}
.n-poster footer {
    margin-top: 28px;
    border-top: 1px solid;
    padding-top: 14px;
}
.n-poster-one {
    background: #f5be28;
    transform: rotate(-3deg);
    position: relative;
    z-index: 1;
}
.n-poster-two {
    background: #b9c2e8;
    transform: rotate(3deg);
    margin-top: -8px;
    margin-left: 30px;
}
.n-poster-two h2 {
    font-family: Georgia, serif;
    font-size: 50px;
    font-weight: 400;
    letter-spacing: -2px;
    font-style: italic;
}
.n-strip {
    padding: 25px 5vw;
    display: flex;
    gap: 24px;
    justify-content: space-between;
    align-items: center;
    background: #bd351d;
    color: #fff7e5;
    font-size: clamp(11px, 1.5vw, 22px);
    font-weight: 900;
    border-top: 2px solid #27231f;
    border-bottom: 2px solid #27231f;
}
.n-strip > span:nth-child(even) {
    font-size: 30px;
}
.n-explainer {
    display: grid;
    grid-template-columns: 1fr 3fr;
    padding: 80px 8vw;
    gap: 30px;
}
.n-number {
    font:
        16px "Courier New",
        monospace;
}
.n-explainer h2 {
    font-size: clamp(30px, 4vw, 55px);
    line-height: 1.07;
    font-weight: 900;
    letter-spacing: -2px;
    margin: 0 0 30px;
}
.n-explainer h2 > span {
    color: #a63422;
}
.n-explainer p {
    font-size: 15px;
    line-height: 1.9;
    max-width: 560px;
    margin-bottom: 25px;
}
.n-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    background: #27231f;
    color: #ffe9cf;
    padding: 30px 5vw;
    font-size: 11px;
}
.n-footer > a:first-child {
    font-weight: 900;
    font-size: 28px;
    letter-spacing: -1px;
}
.n-footer > span {
    font-size: 9px;
}
@media (max-width: 900px) {
    .p-caption,
    .d-header > span,
    .n-header > span {
        display: none;
    }
    .p-hero {
        gap: 30px;
    }
    .p-note {
        padding: 25px;
        min-height: 300px;
    }
    .p-note p {
        font-size: 34px;
    }
    .p-bottom {
        grid-template-columns: 1fr;
    }
    .d-hero {
        grid-template-columns: 1fr;
        gap: 45px;
    }
    .d-hero h1 br:last-of-type {
        display: none;
    }
    .d-hero > div:first-child {
        max-width: 620px;
    }
    .d-deck {
        max-width: 500px;
    }
    .n-hero {
        gap: 30px;
    }
    .n-poster h2 {
        font-size: 50px;
    }
    .n-poster-two h2 {
        font-size: 40px;
    }
}
@media (max-width: 620px) {
    .study-picker {
        padding: 12px 16px;
        gap: 10px;
    }
    .study-picker button {
        font-size: 10px;
        padding: 8px;
    }
    .study-picker > a {
        display: none;
    }
    .programme {
        padding: 0 6vw;
    }
    .p-header {
        padding: 22px 0;
    }
    .p-wordmark {
        font-size: 29px;
    }
    .p-header nav {
        gap: 14px;
        font-size: 10px;
    }
    .p-hero {
        grid-template-columns: 1fr;
        padding: 45px 0;
    }
    .p-kicker {
        font-size: 9px;
        margin-bottom: 22px;
    }
    .p-hero h1 {
        font-size: 58px;
    }
    .p-intro {
        font-size: 14px;
    }
    .p-note {
        min-height: 250px;
        margin: 15px 5px 0;
    }
    .p-note p {
        margin: 25px 0;
        font-size: 38px;
    }
    .p-section-title {
        display: block;
    }
    .p-section-title h2 {
        font-size: 27px;
        margin-bottom: 10px;
    }
    .p-section-title > span {
        font-size: 8px;
    }
    .p-event {
        gap: 15px;
    }
    .p-date {
        font-size: 35px;
        width: 45px;
    }
    .p-event h3 {
        font-size: 20px;
    }
    .p-event p {
        font-size: 10px;
    }
    .p-source {
        font-size: 9px;
        max-width: 55px;
    }
    .p-arrow {
        display: none;
    }
    .p-bottom {
        padding: 40px 0;
        gap: 30px;
    }
    .p-bottom > div {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .p-bottom article {
        display: grid;
        grid-template-columns: 30px 1fr;
        column-gap: 10px;
        border-top: 1px solid #cec5c9;
        padding-top: 16px;
    }
    .p-bottom h3 {
        margin: 0;
    }
    .p-bottom article p {
        grid-column: 2;
    }
    .p-footer {
        flex-wrap: wrap;
        font-size: 10px;
    }
    .p-footer > span:nth-child(2) {
        display: none;
    }
    .d-header {
        padding: 20px 5vw;
    }
    .d-header nav {
        gap: 12px;
        font-size: 10px;
    }
    .d-header .d-button {
        padding: 12px;
        font-size: 10px;
    }
    .d-logo {
        font-size: 18px;
    }
    .d-mark {
        width: 30px;
        height: 30px;
    }
    .d-hero {
        padding: 40px 5vw 50px;
    }
    .d-hero h1 {
        font-size: 49px;
    }
    .d-board-head {
        font-size: 8px;
    }
    .d-summary {
        padding: 22px 15px;
    }
    .d-summary h2 {
        font-size: 22px;
    }
    .d-summary > span {
        font-size: 8px;
    }
    .d-row {
        margin: 0 15px;
        gap: 12px;
    }
    .d-row h3 {
        font-size: 12px;
    }
    .d-provider {
        font-size: 8px;
    }
    .d-row p {
        font-size: 9px;
    }
    .d-board-foot {
        padding: 15px;
        font-size: 9px;
    }
    .d-board-foot > span + span {
        display: none;
    }
    .d-pipeline {
        grid-template-columns: 1fr;
        padding: 40px 6vw;
        gap: 25px;
    }
    .d-pipeline h2 {
        font-size: 38px;
        margin-bottom: 0;
    }
    .d-details {
        grid-template-columns: 1fr;
        padding: 40px 6vw;
        gap: 25px;
    }
    .d-details h3 {
        margin: 15px 0 10px;
    }
    .d-footer {
        font-size: 9px;
        gap: 15px;
    }
    .n-header {
        padding: 18px 5vw;
    }
    .n-logo {
        font-size: 28px;
    }
    .n-header nav {
        gap: 14px;
        font-size: 11px;
    }
    .n-hero {
        grid-template-columns: 1fr;
        padding: 40px 6vw 50px;
        gap: 35px;
    }
    .n-statement h1 {
        font-size: 74px;
    }
    .n-statement > p:first-child {
        font-size: 8px;
    }
    .n-deck {
        font-size: 14px;
    }
    .n-posters {
        padding: 5px 6px;
    }
    .n-poster h2 {
        font-size: 65px;
    }
    .n-poster-two h2 {
        font-size: 50px;
    }
    .n-strip {
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
        padding: 18px 5vw;
    }
    .n-strip > span:nth-child(even) {
        font-size: 18px;
    }
    .n-explainer {
        grid-template-columns: 1fr;
        padding: 40px 6vw;
        gap: 25px;
    }
    .n-explainer h2 {
        font-size: 36px;
        letter-spacing: -1px;
    }
    .n-explainer p {
        font-size: 14px;
    }
    .n-footer {
        flex-wrap: wrap;
        padding: 25px 6vw;
    }
    .n-footer > span {
        display: none;
    }
}
</style>
