<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import {
    ArrowRight,
    ArrowUpRight,
    Pause,
    Play,
    RotateCcw,
    Braces,
    Clock3,
    MapPin,
} from "lucide-vue-next";
const events = [
    {
        id: 42,
        title: "Community garden workshop",
        day: "21",
        month: "NOV",
        weekday: "SATURDAY",
        time: "10 AM–12 PM",
        category: "WORKSHOP",
        location: "Fremantle Community Garden",
        description:
            "A morning of seasonal planting, for all experience levels.",
        provider: "eventnest",
        origin: "Created in EventJunction",
        starts_at: "2026-11-21T02:00:00+00:00",
        ends_at: "2026-11-21T04:00:00+00:00",
        external_id: null,
        colour: "saffron",
    },
    {
        id: 43,
        title: "Local makers market",
        day: "22",
        month: "NOV",
        weekday: "SUNDAY",
        time: "9 AM–1 PM",
        category: "COMMUNITY MARKET",
        location: "Perth Town Hall",
        description:
            "Ceramics, prints and small-batch goods from local makers.",
        provider: "humanitix",
        origin: "Imported listing · Humanitix example",
        starts_at: "2026-11-22T01:00:00+00:00",
        ends_at: "2026-11-22T05:00:00+00:00",
        external_id: "example-market",
        colour: "periwinkle",
    },
];
const scene = ref(0),
    paused = ref(false),
    reduced = ref(false),
    visible = ref(false),
    foreground = ref(true),
    root = ref(null);
const event = computed(() => events[Math.min(1, Math.floor(scene.value / 2))]),
    isApi = computed(() => scene.value % 2 === 1 || scene.value === 4);
const durations = [3500, 4800, 3500, 4800, 5500];
const labels = [
    "Created event",
    "Event API",
    "Imported event",
    "Same API",
    "All sources",
];
const request = computed(
    () =>
        `GET /api/v1/events?${scene.value === 4 ? "" : `source=${event.value.provider}&`}status=published\nAuthorization: Bearer <your-token>\nAccept: application/json`,
);
const mixedResponse = () =>
    [
        "{",
        '  "data": [',
        events
            .map(
                (e) =>
                    `    { "id": ${e.id},\n      "title": ${JSON.stringify(e.title)},\n      "source": ${JSON.stringify({ provider: e.provider })} }`,
            )
            .join(",\n"),
        "  ]",
        "}",
    ].join("\n");
const response = computed(() =>
    scene.value === 4
        ? mixedResponse()
        : [
              "{",
              '  "data": [{',
              `    "id": ${event.value.id},`,
              `    "title": ${JSON.stringify(event.value.title)},`,
              `    "starts_at": ${JSON.stringify(event.value.starts_at)},`,
              '    "timezone": "Australia/Perth",',
              `    "source": ${JSON.stringify({ provider: event.value.provider, external_id: event.value.external_id })}`,
              "  }]",
              "}",
          ].join("\n"),
);
let timer, observer, preference;
const stop = () => clearTimeout(timer);
const schedule = () => {
    stop();
    if (!paused.value && !reduced.value && visible.value && foreground.value)
        timer = setTimeout(
            () => (scene.value = (scene.value + 1) % 5),
            durations[scene.value],
        );
};
watch([scene, paused, visible, foreground, reduced], schedule);
const select = (index) => {
    paused.value = true;
    scene.value = index;
};
const replay = () => {
    scene.value = 0;
    paused.value = false;
    schedule();
};
const visibility = () => {
    foreground.value = !document.hidden;
};
const motion = () => {
    reduced.value = preference.matches;
    if (reduced.value) paused.value = true;
};
onMounted(() => {
    preference = window.matchMedia("(prefers-reduced-motion: reduce)");
    motion();
    preference.addEventListener("change", motion);
    visibility();
    document.addEventListener("visibilitychange", visibility);
    observer = new IntersectionObserver(
        (entries) => {
            visible.value =
                entries[0].isIntersecting &&
                entries[0].intersectionRatio >= 0.2;
        },
        { threshold: 0.2 },
    );
    observer.observe(root.value);
});
onUnmounted(() => {
    stop();
    observer?.disconnect();
    preference?.removeEventListener("change", motion);
    document.removeEventListener("visibilitychange", visibility);
});
</script>
<template>
    <section
        ref="root"
        class="programme-demo"
        aria-label="Event to API demonstration"
        :class="{ 'is-paused': paused, 'reduced-motion': reduced }"
    >
        <div class="demo-masthead">
            <span>EVENTJUNCTION / EXAMPLE PROGRAMME</span
            ><span class="example-label">Example data</span>
        </div>
        <div class="demo-stage" @focusin="paused = true">
            <div
                class="back-sheet"
                :class="event.colour"
                aria-hidden="true"
            ></div>
            <Transition name="programme-scene" mode="out-in" appear
                ><div :key="scene" class="scene-frame" :data-scene="scene">
                    <article
                        v-if="!isApi"
                        class="demo-event"
                        :class="event.colour"
                    >
                        <header>
                            <span>{{ event.category }}</span
                            ><span>{{
                                event.provider === "eventnest"
                                    ? "Created here"
                                    : "Imported listing"
                            }}</span>
                        </header>
                        <div class="event-main">
                            <div class="date-block">
                                <span>{{ event.weekday }}</span
                                ><strong>{{ event.day }}</strong
                                ><span>{{ event.month }}</span>
                            </div>
                            <div class="event-copy">
                                <h2>{{ event.title }}</h2>
                                <p>{{ event.description }}</p>
                            </div>
                        </div>
                        <div class="event-venue">
                            <span
                                ><Clock3 aria-hidden="true" />{{
                                    event.time
                                }}</span
                            ><span
                                ><MapPin aria-hidden="true" />{{
                                    event.location
                                }}</span
                            >
                        </div>
                        <footer>
                            <span>{{ event.origin }}</span
                            ><button type="button" @click="select(scene + 1)">
                                See the API <ArrowRight aria-hidden="true" />
                            </button>
                        </footer>
                    </article>
                    <article v-else class="demo-api">
                        <header>
                            <span
                                ><Braces aria-hidden="true" />{{
                                    scene === 4
                                        ? "ALL SOURCES, ONE REQUEST"
                                        : "YOUR EVENT, AS DATA"
                                }}</span
                            ><span>200 OK</span>
                        </header>
                        <div class="request-panel">
                            <p>REQUEST</p>
                            <pre
                                tabindex="0"
                                aria-label="Demonstration API request"
                            ><code>{{request}}</code></pre>
                        </div>
                        <div class="response-label">
                            <span>RESPONSE EXCERPT</span
                            ><span>application/json</span>
                        </div>
                        <pre
                            tabindex="0"
                            aria-label="Demonstration API response"
                            class="response-code"
                        ><code>{{response}}</code></pre>
                        <footer>
                            <span>{{
                                scene === 4
                                    ? "Native + imported events"
                                    : event.title
                            }}</span
                            ><span
                                >One response format
                                <ArrowUpRight aria-hidden="true"
                            /></span>
                        </footer>
                    </article></div
            ></Transition>
        </div>
        <div
            class="scene-progress"
            :class="{
                'progress-paused': paused || !visible || !foreground || reduced,
            }"
            :key="`progress-${scene}`"
            :style="{
                '--scene-duration': durations[scene] + 'ms',
            }"
            aria-hidden="true"
        >
            <span></span>
        </div>
        <div class="demo-controls">
            <div
                class="scene-navigation"
                role="group"
                aria-label="Demonstration views"
            >
                <button
                    v-for="(label, i) in labels"
                    :key="label"
                    type="button"
                    :aria-pressed="scene === i"
                    @click="select(i)"
                >
                    <span class="scene-dot" aria-hidden="true"></span
                    >{{ label }}
                </button>
            </div>
            <div class="playback">
                <button
                    v-if="!reduced"
                    type="button"
                    :aria-label="
                        paused ? 'Play demonstration' : 'Pause demonstration'
                    "
                    @click="paused = !paused"
                >
                    <Play v-if="paused" aria-hidden="true" /><Pause
                        v-else
                        aria-hidden="true"
                    /></button
                ><button
                    type="button"
                    aria-label="Replay demonstration"
                    @click="replay"
                >
                    <RotateCcw aria-hidden="true" />
                </button>
            </div>
        </div>
        <p class="demo-disclosure">
            {{
                reduced
                    ? "Auto-play is off for reduced motion. Select a view to explore."
                    : "Events become data, then back to the next event."
            }}
            Illustrative data; no live API requests.
        </p>
    </section>
</template>
<style scoped>
.programme-demo {
    min-width: 0;
    color: #29251f;
    font-family: Arial, Helvetica, sans-serif;
}
.demo-masthead {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 0.07em;
    margin-bottom: 22px;
}
.example-label {
    font-weight: 400;
    letter-spacing: 0;
}
.demo-stage {
    position: relative;
    height: 510px;
    padding: 10px 8px 16px 0;
    isolation: isolate;
}
.back-sheet {
    position: absolute;
    inset: 12px 3px 15px 5px;
    border: 1.5px solid #605641;
    rotate: 2deg;
    opacity: 0.55;
    z-index: -1;
    transition: background-color 0.6s;
}
.saffron {
    background: #f2cb59;
}
.periwinkle {
    background: #bdc8e8;
}
.scene-frame {
    height: 100%;
    position: relative;
}
.demo-event,
.demo-api {
    height: 100%;
    border: 1.5px solid #4a4236;
    box-shadow: 0 9px 18px #3c322712;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.demo-event {
    rotate: -1deg;
    padding: 25px 26px;
}
.demo-event > header {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 0.05em;
}
.event-main {
    display: flex;
    align-items: flex-start;
    gap: 25px;
    margin: 40px 0 25px;
    flex: 1;
}
.date-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 65px;
    line-height: 1.2;
    padding-top: 5px;
}
.date-block > span {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
}
.date-block > strong {
    font-size: 64px;
    font-weight: 800;
    letter-spacing: -4px;
    line-height: 1.1;
    margin: 10px 0;
}
.event-copy {
    min-width: 0;
}
.event-copy h2 {
    font-size: clamp(28px, 2.6vw, 39px);
    font-weight: 850;
    line-height: 1.04;
    letter-spacing: -1.5px;
    margin: 0 0 22px;
}
.event-copy > p {
    font-size: 16px;
    line-height: 1.8;
    max-width: 350px;
    margin: 0;
}
.event-venue {
    border-top: 1px solid #7c6b46;
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    font-size: 12px;
    font-weight: 650;
    flex-wrap: wrap;
}
.demo-event > footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 11px;
    line-height: 1.6;
    margin-top: 20px;
}
.demo-event footer > span {
    max-width: 58%;
}
.demo-event footer button {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12px;
    font-weight: 700;
    border-bottom: 1px solid #65543b;
    padding-bottom: 3px;
    white-space: nowrap;
}
.programme-demo svg {
    width: 16px;
    height: 16px;
    display: inline-block;
    flex-shrink: 0;
}
.demo-api {
    background: #302e2a;
    color: #f7eddd;
}
.demo-api > header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 18px 22px;
    border-bottom: 1px solid #625b4d;
    font-size: 10px;
    letter-spacing: 0.06em;
}
.demo-api > header > span:first-child {
    display: flex;
    gap: 9px;
    align-items: center;
}
.demo-api > header > span + span {
    color: #c4d3b8;
}
.request-panel {
    padding: 18px 22px;
    background: #3b3730;
}
.request-panel > p,
.response-label {
    font-size: 10px;
    letter-spacing: 0.06em;
    color: #cdbda1;
}
.request-panel > p {
    margin: 0 0 10px;
}
.demo-api pre {
    font:
        12px/1.75 "Courier New",
        monospace;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    margin: 0;
    min-width: 0;
}
.response-label {
    display: flex;
    justify-content: space-between;
    padding: 17px 22px 10px;
}
.response-code {
    padding: 0 22px 20px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    animation: response-reveal 0.55s ease-out both;
}
.demo-api > footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    font-size: 10px;
    padding: 13px 22px;
    border-top: 1px solid #625b4d;
    color: #d2c1a6;
}
.demo-api footer > span:first-child {
    max-width: 55%;
}
.demo-api footer > span + span {
    display: flex;
    align-items: center;
    gap: 7px;
}
.demo-controls {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
    margin-top: 21px;
}
.scene-navigation {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.scene-navigation button {
    display: flex;
    gap: 6px;
    align-items: center;
    font-size: 10px;
    color: #766b5e;
    padding: 7px 0;
}
.scene-navigation button[aria-pressed="true"] {
    color: #383026;
    font-weight: 700;
}
.scene-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #cdbda8;
}
.scene-navigation button[aria-pressed="true"] .scene-dot {
    background: #a65d40;
}
.playback {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}
.playback button {
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    width: 36px;
    height: 36px;
    padding: 8px;
    border: 1px solid #c9b99f;
    border-radius: 50%;
    background: #fff8ec;
    color: #6c5742;
}
.demo-disclosure {
    font-size: 11px;
    line-height: 1.8;
    color: #796b59;
    margin: 12px 0 0;
}
.programme-demo button:focus-visible,
.programme-demo pre:focus-visible {
    outline: 2px solid #8f5c3f;
    outline-offset: 4px;
}
.programme-scene-enter-active {
    transition:
        opacity 0.45s ease,
        transform 0.55s cubic-bezier(0.2, 0.7, 0.2, 1);
}
.programme-scene-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
}
.programme-scene-enter-from {
    opacity: 0;
    transform: translateY(20px) rotate(1deg) scale(0.98);
}
.programme-scene-leave-to {
    opacity: 0;
    transform: translateY(-14px) rotate(-1deg) scale(0.98);
}
.is-paused .response-code {
    animation: none;
}
@keyframes response-reveal {
    from {
        opacity: 0.1;
        clip-path: inset(0 0 85% 0);
    }
    to {
        opacity: 1;
        clip-path: inset(0 0 0 0);
    }
}
@media (prefers-reduced-motion: reduce) {
    .programme-scene-enter-active,
    .programme-scene-leave-active,
    .back-sheet {
        transition: none;
    }
    .response-code {
        animation: none;
    }
    .programme-scene-enter-from,
    .programme-scene-leave-to {
        transform: none;
    }
}
@media (max-width: 1000px) {
    .demo-stage {
        height: 480px;
    }
    .event-copy h2 {
        font-size: 39px;
    }
    .event-main {
        margin-top: 38px;
    }
    .programme-demo {
        max-width: 720px;
    }
}
@media (max-width: 620px) {
    .demo-stage {
        height: 465px;
        padding-right: 5px;
    }
    .demo-event {
        padding: 22px 20px;
    }
    .event-main {
        gap: 18px;
        margin: 34px 0 20px;
    }
    .date-block {
        min-width: 49px;
    }
    .date-block > strong {
        font-size: 49px;
        letter-spacing: -3px;
    }
    .date-block > span {
        font-size: 8px;
    }
    .event-copy h2 {
        font-size: 31px;
        letter-spacing: -1px;
        margin-bottom: 20px;
    }
    .event-copy > p {
        font-size: 15px;
    }
    .demo-masthead {
        font-size: 9px;
        margin-bottom: 18px;
    }
    .event-venue {
        font-size: 11px;
        padding-top: 15px;
    }
    .demo-event > footer {
        font-size: 10px;
        gap: 10px;
        margin-top: 15px;
    }
    .demo-event footer button {
        font-size: 11px;
    }
    .demo-api > header {
        padding: 16px;
        font-size: 9px;
    }
    .request-panel {
        padding: 16px;
    }
    .demo-api pre {
        font-size: 11px;
        line-height: 1.8;
    }
    .response-label {
        padding: 15px 16px 10px;
        font-size: 9px;
    }
    .response-code {
        padding: 0 16px 18px;
    }
    .demo-api > footer {
        padding: 12px 16px;
        font-size: 9px;
    }
    .scene-navigation {
        gap: 3px 11px;
    }
    .scene-navigation button {
        font-size: 10px;
    }
    .demo-controls {
        align-items: flex-start;
    }
    .playback button {
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        width: 36px;
        height: 36px;
        padding: 7px;
    }
    .demo-disclosure {
        font-size: 10px;
    }
}
</style>

<style scoped>
.demo-stage {
    perspective: 1100px;
}
.scene-frame {
    transform-origin: 50% 65%;
}
.programme-scene-enter-active {
    transition:
        opacity 0.55s ease,
        transform 0.65s cubic-bezier(0.22, 0.7, 0.3, 1);
}
.programme-scene-leave-active {
    transition:
        opacity 0.4s ease,
        transform 0.45s ease;
}
.programme-scene-enter-from {
    opacity: 0;
    transform: translateX(14px) rotate(0.6deg) scale(0.99);
}
.programme-scene-leave-to {
    opacity: 0;
    transform: translateX(-12px) rotate(-0.5deg) scale(0.99);
}
.scene-progress {
    height: 3px;
    background: #ddccb0;
    margin-top: 17px;
    overflow: hidden;
    border-radius: 2px;
}
.scene-progress > span {
    display: block;
    width: 100%;
    height: 100%;
    background: #b96b43;
    transform-origin: left;
    animation: scene-fill var(--scene-duration) linear both;
}
.progress-paused > span {
    animation-play-state: paused;
}
.demo-controls {
    margin-top: 11px;
}
.response-code {
    animation: response-reveal 0.65s ease-out both;
}
.demo-api header > span:last-child {
    animation: status-in 0.3s ease-out 0.1s both;
}
.event-copy h2 {
    animation: event-title-in 0.35s ease-out 0.1s both;
}
.is-paused .response-code {
    animation: none;
}
@keyframes scene-fill {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}
@keyframes status-in {
    from {
        opacity: 0;
        translate: 0 -6px;
    }
    to {
        opacity: 1;
        translate: 0 0;
    }
}
@keyframes event-title-in {
    from {
        opacity: 0.3;
        translate: 0 8px;
    }
    to {
        opacity: 1;
        translate: 0 0;
    }
}
@media (prefers-reduced-motion: reduce) {
    .programme-scene-enter-active,
    .programme-scene-leave-active {
        transition: none;
    }
    .programme-scene-enter-from,
    .programme-scene-leave-to {
        transform: none;
    }
    .scene-progress > span,
    .event-copy h2,
    .demo-api header > span:last-child,
    .response-code {
        animation: none;
    }
}
</style>

<style scoped>
.demo-stage {
    height: 470px;
    padding-right: 8px;
}
.back-sheet {
    rotate: 1.1deg;
    inset: 11px 3px 14px 5px;
    opacity: 0.4;
}
.demo-event {
    rotate: -0.5deg;
    padding: 24px 26px;
}
.demo-event > header {
    align-items: center;
    border-bottom: 1px solid #6e583f38;
    padding-bottom: 16px;
    font-size: 10px;
    letter-spacing: 0.06em;
}
.demo-event > header > span:last-child {
    padding: 4px 8px;
    border: 1px solid #8c754838;
    border-radius: 3px;
    background: #fff9e752;
    font-size: 9px;
    font-weight: 500;
    letter-spacing: 0;
}
.event-main {
    gap: 22px;
    margin: 28px 0 24px;
    flex: 1;
    align-items: flex-start;
}
.date-block {
    min-width: 66px;
    padding: 12px 8px 11px;
    background: #fff8e15c;
    border: 1px solid #8c75483d;
    border-radius: 4px;
}
.date-block > strong {
    font-size: 45px;
    line-height: 1;
    letter-spacing: -2px;
    margin: 9px 0;
}
.date-block > span {
    font-size: 8px;
    letter-spacing: 0.06em;
}
.event-copy h2 {
    font-size: clamp(26px, 2.2vw, 33px);
    font-weight: 750;
    letter-spacing: -0.9px;
    line-height: 1.1;
    margin: 1px 0 17px;
}
.event-copy > p {
    font-size: 15px;
    line-height: 1.8;
    max-width: 340px;
}
.event-venue {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding-top: 16px;
    border-color: #6e583f38;
    font-size: 11px;
    font-weight: 500;
}
.event-venue > span {
    display: flex;
    align-items: center;
    gap: 9px;
}
.event-venue svg {
    width: 13px;
    height: 13px;
    opacity: 0.65;
}
.demo-event > footer {
    font-size: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #6e583f38;
}
.demo-event footer button {
    font-size: 11px;
    border-bottom: 0;
    gap: 7px;
}
.demo-event footer > span {
    color: #766044;
}
.scene-navigation button {
    font-size: 10px;
}
.demo-masthead {
    font-size: 10px;
    letter-spacing: 0.06em;
}
.demo-api pre {
    font-size: 12px;
    line-height: 1.6;
}
.demo-api > header {
    padding: 16px 22px;
}
.request-panel {
    padding: 16px 22px;
}
.response-label {
    padding-top: 14px;
}
.demo-api > footer {
    padding: 12px 22px;
}
@media (max-width: 1000px) {
    .demo-stage {
        height: 465px;
    }
    .event-copy h2 {
        font-size: 34px;
    }
    .event-main {
        margin-top: 27px;
    }
}
@media (max-width: 620px) {
    .demo-stage {
        height: 460px;
    }
    .demo-event {
        padding: 20px;
    }
    .event-main {
        gap: 16px;
        margin: 24px 0 22px;
    }
    .date-block {
        min-width: 52px;
        padding: 11px 6px;
    }
    .date-block > strong {
        font-size: 37px;
        letter-spacing: -1px;
    }
    .date-block > span {
        font-size: 7px;
    }
    .event-copy h2 {
        font-size: 27px;
        letter-spacing: -0.7px;
        line-height: 1.1;
    }
    .event-copy > p {
        font-size: 14px;
        line-height: 1.8;
    }
    .event-venue {
        font-size: 10px;
        gap: 10px;
    }
    .demo-event > footer {
        font-size: 9px;
        margin-top: 17px;
        gap: 10px;
    }
    .demo-event footer button {
        font-size: 10px;
    }
    .demo-event > header {
        font-size: 9px;
        padding-bottom: 14px;
    }
    .demo-event > header > span:last-child {
        font-size: 8px;
    }
    .demo-api pre {
        font-size: 11px;
        line-height: 1.7;
    }
    .demo-masthead {
        font-size: 9px;
    }
    .request-panel {
        padding: 16px;
    }
    .demo-api > header {
        padding: 16px;
    }
    .demo-api > footer {
        padding: 12px 16px;
    }
}
</style>
