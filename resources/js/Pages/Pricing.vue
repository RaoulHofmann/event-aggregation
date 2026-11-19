<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ArrowUpRight, Check } from "lucide-vue-next";
import EventJunctionMark from "@/Components/EventJunctionMark.vue";

const page = usePage();
const signedIn = computed(() => !!page.props.auth?.user);
const features = [
    "Event creation, recurring schedules and templates",
    "Calendar and ticketing imports",
    "Upcoming events website widget",
    "Unified events API",
];
</script>

<template>
    <Head title="Pricing — EventJunction" />
    <div class="pricing-page">
        <header class="pricing-header">
            <a href="/" class="wordmark" aria-label="EventJunction home">
                <EventJunctionMark class="size-8 shrink-0" />EventJunction
            </a>
            <nav aria-label="Main navigation">
                <a href="/#website-widget">Widget</a>
                <a href="/#integrations">Integrations</a>
                <a href="/docs/api">API docs</a>
                <a :href="signedIn ? '/dashboard' : '/login'">{{ signedIn ? "Dashboard" : "Sign in" }}</a>
            </nav>
        </header>
        <main>
            <section class="pricing-intro" aria-labelledby="pricing-heading">
                <h1 id="pricing-heading">Pricing</h1>
                <p>EventJunction is free during early access. Future pricing is TBD.</p>
            </section>
            <div class="pricing-band">
                <section class="pricing-grid" aria-label="Pricing options">
                    <article class="current-plan">
                        <h2>Free</h2>
                        <p class="price">$0</p>
                        <ul>
                            <li v-for="feature in features" :key="feature"><Check class="size-4 shrink-0" aria-hidden="true" />{{ feature }}</li>
                        </ul>
                        <a :href="signedIn ? '/dashboard' : '/register'" class="pricing-cta">
                            {{ signedIn ? "Dashboard" : "Start your workspace" }}
                            <ArrowUpRight class="size-4" aria-hidden="true" />
                        </a>
                    </article>
                    <article v-for="plan in 2" :key="plan" class="future-plan">
                        <h2>Future plan</h2>
                        <p class="price">TBD</p>
                        <p class="plan-note">Unavailable during early access.</p>
                    </article>
                </section>
            </div>
        </main>
        <div class="footer-band">
            <footer class="pricing-footer">
                <a href="/" class="wordmark"><EventJunctionMark class="size-8 shrink-0" />EventJunction</a>
                <nav aria-label="Footer navigation">
                    <a href="/docs/api">API docs</a>
                    <a href="/privacy-policy">Privacy</a>
                    <a href="/terms-of-service">Terms</a>
                </nav>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.pricing-page {
    --page-gutter: 32px;
    min-height: 100vh;
    background: #fff0dc;
    color: #29251f;
    font: 18px/1.7 Arial, Helvetica, sans-serif;
}
.pricing-header, .pricing-intro, .pricing-grid, .pricing-footer {
    width: min(1280px, calc(100% - 2 * var(--page-gutter)));
    margin-inline: auto;
}
.pricing-header, .pricing-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px 24px;
    padding-block: 25px;
}
.pricing-header { border-bottom: 1px solid #cdbb9f; }
.wordmark {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.6px;
}
.pricing-page nav { display: flex; flex-wrap: wrap; gap: 10px 24px; font-size: 15px; font-weight: 600; }
.pricing-page a:focus-visible { outline: 2px solid #9b5f3f; outline-offset: 4px; }
.pricing-intro { padding-block: clamp(34px, 6vw, 72px); }
.pricing-intro h1 {
    font-size: clamp(44px, 6vw, 75px);
    line-height: .99;
    letter-spacing: -.05em;
    font-weight: 900;
}
.pricing-intro p { margin-top: 15px; max-width: 36rem; font-size: clamp(17px, 2vw, 21px); }
.pricing-band { background: #e9e6f1; border-top: 1px solid #d1c8dc; padding-block: clamp(30px, 5vw, 64px); }
.pricing-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) repeat(2, minmax(0, 1fr)); gap: 18px; }
.pricing-grid article {
    min-width: 0;
    padding: clamp(18px, 3vw, 30px);
    border-radius: 11px;
    border: 1px solid #cdbb9f;
}
.current-plan { background: #fffaf1; box-shadow: 0 12px 22px #3c302015; }
.pricing-grid h2 { font-size: 23px; font-weight: 750; line-height: 1.2; }
.price { font-size: clamp(37px, 6vw, 59px); font-weight: 800; letter-spacing: -.045em; line-height: 1; margin-block: 16px 20px; }
.current-plan ul { border-top: 1px solid #e2d4be; display: grid; gap: 9px; padding-block: 18px 24px; }
.current-plan li { display: flex; align-items: baseline; gap: 10px; font-size: 15px; }
.current-plan li svg { align-self: flex-start; margin-top: 4px; color: #556544; }
.pricing-cta { display: inline-flex; align-items: center; gap: 11px; padding: 11px 16px; background: #29251f; color: #fff7e9; font-size: 15px; font-weight: 700; }
.future-plan { background: #f3f0f4; color: #625d67; border-color: #d4cdd9 !important; }
.plan-note { font-size: 15px; }
.footer-band { background: #302b25; color: #f7e9d5; }
@media (max-width: 710px) {
    .pricing-page { --page-gutter: 20px; }
    .pricing-grid { grid-template-columns: minmax(0, 1fr); }
    .pricing-header .wordmark { font-size: 26px; }
    .pricing-page nav { gap: 8px 15px; }
}
</style>
