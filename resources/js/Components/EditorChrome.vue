<script setup>
import EventJunctionMark from "@/Components/EventJunctionMark.vue";
import { X } from "lucide-vue-next";
defineProps({
    context: String,
    name: String,
    title: String,
    closeLabel: String,
});
defineEmits(["close"]);
</script>
<template>
    <header class="editor-chrome">
        <div class="chrome-bar">
            <div class="chrome-context">
                <EventJunctionMark class="chrome-mark" />
                <span
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
    width: 27px;
    height: 27px;
    flex-shrink: 0;
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
    .chrome-context {
        font-size: 11px;
    }
    .chrome-slash {
        margin: 0 7px;
    }
}
</style>
