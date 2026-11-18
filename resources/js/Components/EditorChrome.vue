<script setup>
import { X } from "lucide-vue-next";
defineProps({
    context: String,
    name: String,
    title: String,
    subtitle: String,
    closeLabel: String,
});
defineEmits(["close"]);
</script>
<template>
    <header class="editor-chrome">
        <div class="chrome-bar">
            <div class="chrome-context">
                <span class="chrome-mark" aria-hidden="true">e.</span
                ><span
                    >{{ context }}<span class="chrome-slash">/</span
                    ><strong>{{ name }}</strong></span
                >
            </div>
            <button
                type="button"
                :aria-label="closeLabel"
                @click="$emit('close')"
            >
                <X class="size-5" />
            </button>
        </div>
        <div class="chrome-heading">
            <h2>{{ title }}</h2>
            <p>{{ subtitle }}</p>
        </div>
    </header>
</template>
<style scoped>
.editor-chrome {
    background: #fffdf9;
    color: #30313a;
    font-family: inherit;
}
.chrome-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 14px 26px;
    background: #f7f5fa;
    border-bottom: 1px solid #e5dfeb;
}
.chrome-context {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    font-size: 12px;
    color: #7d7188;
}
.chrome-context > span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.chrome-context strong {
    font-weight: 600;
    color: #4d4357;
}
.chrome-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0d37d;
    border: 1px solid #d3bb77;
    width: 27px;
    height: 27px;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -2px;
    flex-shrink: 0;
    color: #4f4231;
}
.chrome-slash {
    margin: 0 11px;
    color: #b8aebe;
}
.chrome-bar button {
    padding: 5px;
    color: #81758c;
    flex-shrink: 0;
}
.chrome-heading {
    padding: 19px 27px 22px;
    border-bottom: 1px solid #e5dfeb;
}
.chrome-heading h2 {
    font-size: 23px;
    font-weight: 650;
    letter-spacing: -0.5px;
    line-height: 1.3;
    margin: 0 0 8px;
}
.chrome-heading p {
    font-size: 13px;
    color: #8b8093;
    line-height: 1.7;
    margin: 0;
}
@media (max-width: 640px) {
    .chrome-bar {
        padding: 12px 19px;
    }
    .chrome-heading {
        padding: 17px 20px;
    }
    .chrome-heading h2 {
        font-size: 21px;
    }
    .chrome-heading p {
        font-size: 12px;
    }
    .chrome-context {
        font-size: 11px;
    }
    .chrome-slash {
        margin: 0 7px;
    }
}
</style>
