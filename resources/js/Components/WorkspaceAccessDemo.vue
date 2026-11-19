<script setup>
import { computed, ref } from "vue";
import { CalendarDays, Check, LockKeyhole, UsersRound } from "lucide-vue-next";

const examples = [
    {
        id: "studio",
        name: "Studio programme",
        initials: "SP",
        role: "Owner",
        events: ["Saturday pottery workshop", "Winter studio open day"],
    },
    {
        id: "neighbourhood",
        name: "Neighbourhood festival",
        initials: "NF",
        role: "Member",
        events: ["Community market", "Sunday garden tour"],
    },
];
const selectedId = ref(examples[0].id);
const selected = computed(() => examples.find((workspace) => workspace.id === selectedId.value));
</script>

<template>
    <div class="workspace-band">
        <section id="workspaces" class="workspace-section" aria-labelledby="workspace-heading">
            <header class="workspace-heading">
                <h2 id="workspace-heading">Separate workspaces.<br />One sign-in.</h2>
                <p class="workspace-intro">
                    Keep each festival, client programme or team in its own workspace.
                </p>
            </header>

            <div class="workspace-demo">
                <div class="workspace-picker" role="group" aria-label="Example workspace switcher">
                    <p class="picker-label">Example workspaces</p>
                    <button
                        v-for="workspace in examples"
                        :key="workspace.id"
                        type="button"
                        :aria-pressed="selectedId === workspace.id"
                        @click="selectedId = workspace.id"
                    >
                        <span class="workspace-initials" aria-hidden="true">{{ workspace.initials }}</span>
                        <span class="workspace-option">
                            <strong>{{ workspace.name }}</strong>
                            <span>{{ workspace.role }}</span>
                        </span>
                        <Check v-if="selectedId === workspace.id" class="workspace-check" aria-hidden="true" />
                    </button>
                </div>

                <div class="workspace-preview" aria-live="polite">
                    <div class="workspace-preview-title">
                        <h3>{{ selected.name }}</h3>
                        <span class="workspace-role">{{ selected.role }}</span>
                    </div>
                    <ul class="workspace-events">
                        <li v-for="event in selected.events" :key="event">
                            <CalendarDays aria-hidden="true" />{{ event }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="workspace-rules">
                <div>
                    <UsersRound aria-hidden="true" />
                    <h3>Manage access</h3>
                    <p>Owners manage members and integrations. Each invitation gives access to one workspace.</p>
                </div>
                <div>
                    <LockKeyhole aria-hidden="true" />
                    <h3>Workspace data</h3>
                    <p>Events, templates and integrations are scoped to each workspace. API access requires permission for that workspace.</p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.workspace-band {
    background: #edf0e4;
    border-bottom: 1px solid #ced4c2;
}
.workspace-section {
    box-sizing: border-box;
    width: min(var(--page-width), calc(100% - var(--page-gutter) * 2));
    margin-inline: auto;
    padding-block: 64px;
}
.workspace-heading {
    display: grid;
    grid-template-columns: 1.25fr 1fr;
    align-items: end;
    gap: 28px;
    margin-bottom: 30px;
}
.picker-label {
    margin: 0 0 12px;
    font-size: 12px;
    font-weight: 750;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}
.workspace-heading h2 {
    margin: 0;
    font-size: clamp(34px, 4vw, 54px);
    font-weight: 800;
    line-height: 1.03;
    letter-spacing: -0.045em;
}
.workspace-intro {
    max-width: 480px;
    margin: 0;
    color: #4f5548;
    font-size: 17px;
    line-height: 1.7;
}
.workspace-demo {
    display: grid;
    grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.35fr);
    overflow: hidden;
    border: 1px solid #bfae91;
    border-radius: 12px;
    background: #fffcf6;
    box-shadow: 0 14px 30px #3c342017;
}
.workspace-picker,
.workspace-preview {
    padding: clamp(20px, 3vw, 32px);
}
.workspace-picker {
    display: grid;
    align-content: start;
    gap: 10px;
    background: #f1dfc0;
    border-right: 1px solid #bfae91;
}
.workspace-picker button {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid #baaa8f;
    border-radius: 7px;
    text-align: left;
    color: #29251f;
}
.workspace-picker button[aria-pressed="true"] {
    border-color: #8e7047;
    background: #fff8ed;
}
.workspace-initials,
.workspace-role {
    flex: none;
    border-radius: 5px;
    background: #dfe6d3;
    color: #34432b;
    font-size: 12px;
    font-weight: 750;
}
.workspace-initials {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
}
.workspace-option {
    display: grid;
    flex: 1;
    min-width: 0;
    line-height: 1.3;
}
.workspace-option strong {
    font-size: 15px;
    overflow-wrap: anywhere;
}
.workspace-option span {
    color: #4f5548;
    font-size: 13px;
}
.workspace-check {
    flex: none;
    color: #34432b;
}
.workspace-preview-title {
    display: flex;
    align-items: start;
    flex-wrap: wrap;
    gap: 8px 14px;
    justify-content: space-between;
}
.workspace-preview-title h3,
.workspace-rules h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 750;
    line-height: 1.25;
}
.workspace-role {
    padding: 3px 9px;
}
.workspace-events {
    margin: 14px 0;
    padding: 0;
    list-style: none;
}
.workspace-events li {
    display: flex;
    align-items: baseline;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #dde2d3;
    font-size: 15px;
}
.workspace-events svg {
    flex: none;
    color: #535f46;
}
.workspace-rules {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    margin-top: 28px;
}
.workspace-rules > div {
    padding-top: 16px;
    border-top: 1px solid #b5bca8;
}
.workspace-rules svg {
    margin-bottom: 8px;
    color: #535f46;
}
.workspace-rules p {
    max-width: 495px;
    margin: 6px 0 0;
    color: #4f5548;
    font-size: 15px;
    line-height: 1.65;
}
@media (max-width: 700px) {
    .workspace-section {
        padding-block: 42px;
    }
    .workspace-heading,
    .workspace-demo,
    .workspace-rules {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .workspace-demo {
        gap: 0;
    }
    .workspace-picker {
        border-right: 0;
        border-bottom: 1px solid #bfae91;
    }
}
</style>
