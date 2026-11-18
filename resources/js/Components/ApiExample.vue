<script setup>
import { computed, ref } from "vue";
import { apiExamples } from "@/Lib/apiExamples";
const examples = apiExamples(window.location.origin),
    active = ref("all-sources"),
    copied = ref(false),
    failed = ref(false);
const example = computed(() => examples.find((e) => e.id === active.value));
const copy = async () => {
    try {
        await navigator.clipboard.writeText(example.value.request);
        copied.value = true;
        failed.value = false;
    } catch {
        failed.value = true;
    }
};
</script>
<template>
    <div class="api-example">
        <div class="api-tabs" role="group" aria-label="API examples">
            <button
                v-for="e in examples"
                :key="e.id"
                :aria-pressed="active === e.id"
                @click="
                    active = e.id;
                    copied = false;
                "
            >
                {{ e.label }}
            </button>
        </div>
        <div class="api-code-head">
            <span>REQUEST / cURL</span
            ><button @click="copy">
                {{ copied ? "Copied" : "Copy request" }}
            </button>
        </div>
        <p v-if="failed" role="status" class="copy-help">
            Select the request below to copy it.
        </p>
        <pre
            tabindex="0"
            aria-label="Example API request"
        ><code>{{example.request}}</code></pre>
        <p v-if="active === 'all-sources'" class="source-summary">
            One response: <strong>EventJunction + Humanitix</strong
            ><span>Example native and imported records. No source filter.</span>
        </p>
        <div class="api-code-head">
            <span>EXAMPLE RESPONSE / EXCERPT</span><span>JSON</span>
        </div>
        <pre
            tabindex="0"
            aria-label="Example API response"
            class="api-response"
        ><code>{{example.response}}</code></pre>
    </div>
</template>
<style scoped>
.api-example {
    min-width: 0;
    border: 2px solid #27231f;
    background: #27231f;
    color: #fff4e4;
}
.api-tabs {
    display: flex;
    flex-wrap: wrap;
    background: #fff4e4;
    border-bottom: 2px solid #27231f;
}
.api-tabs button {
    flex: 1;
    padding: 16px 12px;
    color: #27231f;
    font-size: 15px;
    font-weight: 700;
}
.api-tabs button[aria-pressed="true"] {
    background: #f5be28;
}
.api-code-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 22px 0;
    font:
        12px Arial,
        sans-serif;
    letter-spacing: 0.05em;
    color: #d9cab7;
}
.api-code-head button {
    color: #f5be28;
    text-decoration: underline;
    letter-spacing: 0;
}
.api-example pre {
    overflow-x: auto;
    margin: 0;
    padding: 22px;
    font:
        14px/1.8 "Courier New",
        monospace;
    white-space: pre;
    tab-size: 2;
}
.api-response {
    max-height: 330px;
    border-top: 1px solid #62574b;
    margin-top: 16px !important;
}
.copy-help {
    padding: 12px 22px;
    font-size: 14px;
}
.api-example button:focus-visible,
.api-example pre:focus-visible {
    outline: 3px solid #bd351d;
    outline-offset: -3px;
}
@media (min-width: 1100px) {
    .api-example pre {
        font-size: 15px;
    }
}
</style>

<style scoped>
.source-summary {
    margin: 0;
    padding: 14px 22px;
    background: #3b3730;
    color: #eee1c8;
    font-size: 13px;
    line-height: 1.7;
}
.source-summary span {
    display: block;
    color: #cabca6;
    font-size: 11px;
}
</style>
