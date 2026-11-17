<script setup>
import { ArrowUpRight } from "lucide-vue-next";
import ApiExample from "@/Components/ApiExample.vue";
</script>
<template>
    <div class="noticeboard-home">
        <header class="home-header">
            <a href="/" class="wordmark" aria-label="EventNest home"
                >eventnest<ArrowUpRight aria-hidden="true"
            /></a>
            <nav aria-label="Main navigation">
                <a href="#how-it-works">How it works</a
                ><a href="/docs/api">API docs</a><a href="/login">Sign in</a
                ><a href="/register" class="nav-cta"
                    >Get started <ArrowUpRight aria-hidden="true"
                /></a>
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
                <div class="programme">
                    <div class="programme-caption">
                        <span>A WEEKEND IN THE DIARY</span
                        ><span>Example events</span>
                    </div>
                    <article class="event-poster garden">
                        <div class="event-date">
                            <span>SATURDAY</span><strong>21 NOV</strong
                            ><span>10 AM–12 PM</span>
                        </div>
                        <div class="event-information">
                            <p class="event-type">WORKSHOP</p>
                            <h2>Community garden<br />workshop</h2>
                            <p>
                                Learn the basics of seasonal planting at
                                Fremantle Community Garden.
                            </p>
                            <div class="event-footer">
                                <span>Fremantle, WA</span
                                ><span>Created in EventNest</span>
                            </div>
                        </div>
                    </article>
                    <article class="event-poster market">
                        <div class="event-date">
                            <span>SUNDAY</span><strong>22 NOV</strong
                            ><span>9 AM–1 PM</span>
                        </div>
                        <div class="event-information">
                            <p class="event-type">COMMUNITY MARKET</p>
                            <h2>Local makers<br />market</h2>
                            <p>
                                Ceramics, prints and small-batch goods at Perth
                                Town Hall.
                            </p>
                            <div class="event-footer">
                                <span>Perth, WA</span
                                ><span>Imported event</span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
            <section id="how-it-works" class="working-together">
                <div>
                    <p class="eyebrow">ONE WORKSPACE</p>
                    <h2>Your calendar,<br />wherever events begin.</h2>
                </div>
                <p>
                    Create directly in EventNest or import from a connected
                    platform. Use the same templates, manage repeating dates,
                    and make local edits without sending changes back to the
                    original host.
                </p>
            </section>
            <section class="organising">
                <div class="section-heading">
                    <p class="eyebrow">THE EVERYDAY WORK, IN ONE PLACE</p>
                    <h2>From the first idea<br />to the final details.</h2>
                </div>
                <div class="organising-list">
                    <article>
                        <span>01</span>
                        <div>
                            <h3>Make the event your own.</h3>
                            <p>
                                Start with a title, date and location. Add
                                custom fields and reusable templates for the
                                information your team needs.
                            </p>
                        </div>
                    </article>
                    <article>
                        <span>02</span>
                        <div>
                            <h3>Bring your listings together.</h3>
                            <p>
                                Match imported events to your templates and edit
                                the local copy. Humanitix is the first supported
                                integration; more event hosts are planned.
                            </p>
                        </div>
                    </article>
                    <article>
                        <span>03</span>
                        <div>
                            <h3>See what’s coming up.</h3>
                            <p>
                                Plan in month, week or agenda view. Preview
                                repeating dates, skip an occurrence, or move it
                                to another day.
                            </p>
                        </div>
                    </article>
                </div>
            </section>
            <section id="api-first" class="api-first">
                <div class="api-copy">
                    <p class="eyebrow">API FIRST</p>
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
                        Native and imported events share one response format.
                        Read event records or request a date range of calendar
                        occurrences, including repeating events and local
                        changes.
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
            <section class="closing">
                <h2>Give the whole<br />programme a home.</h2>
                <a href="/register" class="dark-button"
                    >Create your workspace <ArrowUpRight aria-hidden="true"
                /></a>
            </section>
        </main>
        <footer class="home-footer">
            <a href="/" class="wordmark"
                >eventnest <ArrowUpRight aria-hidden="true"
            /></a>
            <p>For the things that bring people out.</p>
            <nav aria-label="Footer navigation">
                <a href="/docs/api">API documentation</a
                ><a href="/login">Sign in</a>
            </nav>
        </footer>
    </div>
</template>
<style scoped>
.noticeboard-home {
    background: #ffe9cf;
    color: #27231f;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 18px;
    line-height: 1.65;
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
.noticeboard-home a:focus-visible {
    outline: 3px solid #bd351d;
    outline-offset: 5px;
}
.work-in-progress {
    padding: 14px 5vw;
    border-bottom: 1px solid #a78d69;
    background: #f5be28;
    font-size: 15px;
    line-height: 1.7;
}
.work-in-progress strong {
    margin-right: 6px;
}
.work-in-progress a {
    text-decoration: underline;
    margin-left: 12px;
    white-space: nowrap;
}
.early-access {
    padding: 55px 7vw;
    border-bottom: 2px solid #27231f;
    background: #bbc5e7;
}
.early-access h2 {
    font-size: 38px;
    letter-spacing: -1.5px;
    line-height: 1.12;
    font-weight: 900;
    margin: 0 0 22px;
}
.early-access > p:last-child {
    max-width: 760px;
    font-size: 19px;
    line-height: 1.8;
    margin: 0;
}
.home-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 26px 5vw;
    border-bottom: 2px solid #27231f;
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
    align-items: center;
    gap: 28px;
    font-size: 16px;
    font-weight: 700;
}
.home-header .nav-cta {
    background: #27231f;
    color: #ffe9cf;
    padding: 10px 16px;
}
.hero {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 6vw;
    padding: 65px 5vw 70px;
    max-width: 1600px;
    margin: auto;
    align-items: center;
}
.eyebrow {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.09em;
    margin: 0 0 26px;
}
.hero h1 {
    font-size: clamp(56px, 6.2vw, 88px);
    font-weight: 900;
    line-height: 0.95;
    letter-spacing: -0.065em;
    margin: 0;
}
.hero em,
.api-copy em {
    font-style: normal;
    color: #b5331e;
}
.intro {
    font-size: 21px;
    line-height: 1.7;
    max-width: 480px;
    margin: 28px 0;
}
.dark-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    background: #27231f;
    color: #fff4e4 !important;
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
.programme {
    min-width: 0;
}
.programme-caption {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    font-size: 12px;
    font-weight: 700;
}
.programme-caption > span + span {
    font-weight: 400;
    text-transform: none;
}
.event-poster {
    display: grid;
    grid-template-columns: 100px minmax(0, 1fr);
    border: 2px solid #27231f;
    margin-bottom: 20px;
}
.garden {
    background: #f5be28;
    transform: rotate(-1deg);
}
.market {
    background: #bbc5e7;
    transform: rotate(1deg);
    margin-left: 18px;
    margin-bottom: 0;
}
.event-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 20px 10px;
    border-right: 2px solid #27231f;
    text-align: center;
}
.event-date span {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
}
.event-date strong {
    font-size: 22px;
    line-height: 1.3;
}
.event-information {
    padding: 22px;
}
.event-type {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin: 0 0 12px;
}
.event-information h2 {
    font-size: 35px;
    font-weight: 900;
    letter-spacing: -1.5px;
    line-height: 1.02;
    margin: 0 0 14px;
}
.event-information > p:not(.event-type) {
    font-size: 16px;
    line-height: 1.6;
    margin: 0;
}
.event-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    border-top: 1px solid #655327;
    padding-top: 13px;
    margin-top: 18px;
    font-size: 12px;
    font-weight: 700;
}
.working-together {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8vw;
    padding: 48px 7vw;
    background: #b5331e;
    color: #fff4e4;
    border-top: 2px solid #27231f;
    border-bottom: 2px solid #27231f;
    align-items: center;
}
.working-together .eyebrow {
    margin-bottom: 12px;
}
.working-together h2 {
    font-size: clamp(30px, 3.2vw, 44px);
    line-height: 1.13;
    letter-spacing: -1.4px;
    font-weight: 800;
    margin: 0;
}
.working-together > p {
    font-size: 19px;
    line-height: 1.8;
    margin: 0;
}
.organising {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 8vw;
    padding: 75px 7vw;
    max-width: 1600px;
    margin: auto;
}
.section-heading h2,
.api-copy h2,
.closing h2 {
    font-size: clamp(34px, 3.6vw, 50px);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1.07;
    margin: 0 0 28px;
}
.organising-list article {
    display: flex;
    gap: 24px;
    padding: 24px 0;
    border-top: 2px solid #27231f;
}
.organising-list article > span {
    font:
        16px "Courier New",
        monospace;
    padding-top: 5px;
}
.organising-list h3 {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.6px;
    line-height: 1.3;
    margin: 0 0 12px;
}
.organising-list p {
    font-size: 18px;
    line-height: 1.8;
    margin: 0;
}
.api-first {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
    align-items: start;
    gap: 6vw;
    padding: 70px 7vw;
    background: #fff4e4;
    border-top: 2px solid #27231f;
    border-bottom: 2px solid #27231f;
}
.api-copy > p:not(.eyebrow):not(.api-note) {
    font-size: 18px;
    line-height: 1.8;
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
    line-height: 1.7;
    color: #62564b;
}
.closing {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
    padding: 55px 7vw;
    background: #f5be28;
}
.closing h2 {
    margin: 0;
    font-size: 40px;
}
.home-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    background: #27231f;
    color: #ffe9cf;
    padding: 32px 5vw;
}
.home-footer p {
    font-size: 14px;
    margin: 0;
}
.home-footer nav {
    display: flex;
    gap: 22px;
    font-size: 15px;
}
@media (max-width: 1000px) {
    .hero {
        grid-template-columns: 1fr;
        gap: 45px;
    }
    .hero-copy {
        max-width: 680px;
    }
    .hero h1 {
        font-size: 80px;
    }
    .programme {
        max-width: 740px;
    }
    .programme .event-information h2 br {
        display: none;
    }
    .organising {
        gap: 40px;
    }
    .home-footer {
        flex-wrap: wrap;
    }
    .api-first {
        grid-template-columns: 1fr;
    }
    .api-copy {
        max-width: 720px;
    }
    .home-header nav {
        gap: 18px;
    }
}
@media (max-width: 640px) {
    .home-header {
        padding: 18px 5vw;
        gap: 12px;
        flex-wrap: wrap;
    }
    .wordmark {
        font-size: 28px;
    }
    .home-header nav {
        width: 100%;
        justify-content: space-between;
        font-size: 14px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .home-header .nav-cta {
        padding: 8px 12px;
    }
    .hero {
        padding: 40px 6vw 45px;
    }
    .hero h1 {
        font-size: 64px;
        letter-spacing: -3px;
    }
    .eyebrow {
        font-size: 11px;
        margin-bottom: 20px;
    }
    .intro {
        font-size: 19px;
    }
    .dark-button {
        font-size: 16px;
        padding: 15px 18px;
        gap: 12px;
    }
    .quiet-link {
        font-size: 14px;
        line-height: 1.7;
    }
    .programme-caption {
        font-size: 10px;
    }
    .event-poster {
        grid-template-columns: 76px minmax(0, 1fr);
    }
    .market {
        margin-left: 0;
        margin-top: 24px;
    }
    .event-date {
        padding: 15px 7px;
        gap: 10px;
    }
    .event-date strong {
        font-size: 19px;
    }
    .event-date span {
        font-size: 9px;
    }
    .event-information {
        padding: 18px 15px;
    }
    .event-information h2 {
        font-size: 29px;
        letter-spacing: -1px;
    }
    .event-information > p:not(.event-type) {
        font-size: 15px;
    }
    .event-type {
        font-size: 9px;
    }
    .event-footer {
        font-size: 11px;
        gap: 6px;
    }
    .working-together {
        grid-template-columns: 1fr;
        padding: 35px 6vw;
        gap: 24px;
    }
    .working-together > p {
        font-size: 18px;
    }
    .organising {
        grid-template-columns: 1fr;
        padding: 45px 6vw;
        gap: 24px;
    }
    .section-heading h2,
    .api-copy h2 {
        font-size: 38px;
        letter-spacing: -1.4px;
    }
    .organising-list h3 {
        font-size: 22px;
    }
    .organising-list p {
        font-size: 17px;
    }
    .organising-list article {
        gap: 16px;
    }
    .api-first {
        padding: 45px 6vw;
        gap: 30px;
    }
    .api-copy > p:not(.eyebrow):not(.api-note) {
        font-size: 18px;
    }
    .closing {
        padding: 35px 6vw;
        align-items: flex-start;
        flex-direction: column;
    }
    .closing h2 {
        font-size: 34px;
    }
    .home-footer {
        padding: 28px 6vw;
        gap: 20px;
        align-items: flex-start;
        flex-direction: column;
    }
    .home-footer nav {
        font-size: 15px;
    }
}
</style>
