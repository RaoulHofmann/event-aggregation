<script setup>
import { Head } from "@inertiajs/vue3";
import { ArrowUpRight, Check } from "lucide-vue-next";
</script>
<template>
    <Head title="Pricing — EventNest" />
    <div class="pricing-page">
        <header>
            <a href="/" class="brand"
                >eventnest <ArrowUpRight class="inline h-6 w-6"
            /></a>
            <nav aria-label="Main navigation">
                <a href="/">Home</a><a href="/docs/api">API docs</a
                ><a href="/login">Sign in</a>
            </nav>
        </header>
        <main>
            <div class="pricing-heading">
                <p>PRICING / EARLY ACCESS</p>
                <h1>Currently free.<br />Room to grow.</h1>
                <p>
                    Access and usage are free while EventNest takes shape.
                    Future pricing is still to be determined.
                </p>
            </div>
            <section aria-label="Pricing options" class="pricing-grid">
                <article class="current-plan">
                    <div class="plan-status">AVAILABLE NOW</div>
                    <h2>Free</h2>
                    <p class="price">$0<span>currently</span></p>
                    <p class="plan-description">
                        Create your programme and bring your event data
                        together.
                    </p>
                    <ul>
                        <li
                            v-for="feature in [
                                'Native events and reusable templates',
                                'Calendar views and repeating events',
                                'Connected event imports and local edits',
                                'Unified event and occurrence API',
                            ]"
                            :key="feature"
                        >
                            <Check class="h-4 w-4 shrink-0" />{{ feature }}
                        </li>
                    </ul>
                    <a href="/register" class="pricing-cta"
                        >Start a workspace <ArrowUpRight class="h-4 w-4"
                    /></a>
                    <p class="plan-footnote">
                        Current service limits apply.
                        <a href="/docs/api#errors">View API limits.</a>
                    </p>
                </article>
                <article
                    v-for="number in [1, 2]"
                    :key="number"
                    class="future-plan"
                    :aria-label="`Future pricing option ${number}`"
                >
                    <div class="plan-status">PLANNED</div>
                    <h2>Future plan</h2>
                    <p class="price">
                        TBD<span>pricing to be determined</span>
                    </p>
                    <p class="plan-description">
                        Plan details, features and pricing have not been
                        decided.
                    </p>
                    <div class="future-details" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                    <p class="not-available">Not available yet</p>
                </article>
            </section>
            <section class="pricing-note">
                <h2>A work in progress.</h2>
                <p>
                    EventNest is in active development. The Free option
                    describes what you can use today. The other columns are
                    placeholders for possible future plans, with no prices or
                    features committed yet.
                </p>
                <p>
                    Humanitix is the first supported event-host integration.
                    Additional connectors are planned.
                </p>
            </section>
        </main>
        <footer>
            <a href="/">EventNest</a><a href="/docs/api">API documentation</a
            ><span>Work in progress · Currently free</span>
        </footer>
    </div>
</template>
<style scoped>
.pricing-page {
    background: #faf8f3;
    color: #34332f;
    min-height: 100vh;
    font:
        17px/1.8 Arial,
        Helvetica,
        sans-serif;
}
.pricing-page a {
    color: inherit;
    text-underline-offset: 4px;
}
.pricing-page header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 24px 6vw;
    border-bottom: 1px solid #d7d1c5;
}
.brand {
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.5px;
    text-decoration: none;
}
.pricing-page header nav {
    display: flex;
    gap: 25px;
    font-size: 15px;
}
.pricing-page main {
    max-width: 1400px;
    margin: auto;
    padding: 65px 6vw;
}
.pricing-heading {
    margin-bottom: 45px;
    max-width: 760px;
}
.pricing-heading > p:first-child {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
}
.pricing-heading h1 {
    font-size: clamp(42px, 5vw, 66px);
    font-weight: 800;
    line-height: 1.04;
    letter-spacing: -2.5px;
    margin: 22px 0;
}
.pricing-heading > p:last-child {
    font-size: 20px;
    line-height: 1.8;
}
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}
.pricing-grid article {
    display: flex;
    flex-direction: column;
    padding: 30px;
    border: 1px solid #d4cdbf;
    border-radius: 8px;
    min-width: 0;
}
.current-plan {
    background: #fff;
}
.plan-status {
    font-size: 11px;
    letter-spacing: 0.08em;
    font-weight: 700;
    color: #78684f;
}
.pricing-grid h2 {
    font-size: 26px;
    font-weight: 700;
    line-height: 1.3;
    margin: 22px 0;
}
.price {
    font-size: 49px;
    line-height: 1.1;
    letter-spacing: -2px;
    font-weight: 700;
    margin: 0 0 25px;
}
.price > span {
    display: block;
    font-size: 13px;
    font-weight: 400;
    letter-spacing: 0;
    line-height: 1.6;
    margin-top: 12px;
}
.plan-description {
    font-size: 16px;
    line-height: 1.8;
    min-height: 90px;
}
.current-plan ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 25px 0;
    margin-bottom: 25px;
    font-size: 14px;
}
.current-plan li {
    display: flex;
    align-items: baseline;
    gap: 10px;
}
.current-plan li svg {
    align-self: flex-start;
    margin-top: 5px;
    color: #876a42;
}
.pricing-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    padding: 13px 15px;
    background: #34332f;
    color: #fff !important;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    margin-top: auto;
    border-radius: 4px;
}
.plan-footnote {
    font-size: 12px;
    margin-top: 18px;
    color: #756b5b;
}
.future-plan {
    background: #eeefed;
    color: #696d65;
    border-color: #dcddd8 !important;
}
.future-plan .plan-status {
    color: #696d65;
}
.future-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 35px 0 50px;
}
.future-details span {
    height: 8px;
    width: 80%;
    background: #dddeda;
    border-radius: 4px;
}
.future-details span:nth-child(2) {
    width: 65%;
}
.future-details span:nth-child(3) {
    width: 73%;
}
.not-available {
    border: 1px solid #d3d5cf;
    background: #e5e7e1;
    text-align: center;
    padding: 13px 15px;
    font-size: 14px;
    margin-top: auto;
    border-radius: 4px;
}
.pricing-note {
    border-top: 1px solid #d7d1c5;
    margin-top: 55px;
    padding-top: 30px;
    max-width: 900px;
}
.pricing-note h2 {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.7px;
    margin-bottom: 20px;
}
.pricing-note p {
    font-size: 16px;
    margin-bottom: 18px;
}
.pricing-page footer {
    border-top: 1px solid #d7d1c5;
    display: flex;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
    padding: 25px 6vw;
    font-size: 14px;
}
@media (max-width: 950px) {
    .pricing-grid {
        gap: 14px;
    }
    .pricing-grid article {
        padding: 22px;
    }
    .pricing-page main {
        padding: 40px 5vw;
    }
}
@media (max-width: 700px) {
    .pricing-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .pricing-grid article {
        padding: 28px;
    }
    .plan-description {
        min-height: 0;
    }
    .future-details {
        padding: 25px 0 35px;
    }
    .pricing-page header {
        flex-wrap: wrap;
    }
    .pricing-page header nav {
        gap: 20px;
    }
    .pricing-heading h1 {
        font-size: 48px;
    }
    .pricing-heading > p:last-child {
        font-size: 18px;
    }
}
</style>
