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
                <div>
                    <p class="workspace-kicker">Workspaces &amp; access</p>
                    <h2 id="workspace-heading">Separate workspaces.<br />One sign-in.</h2>
                </div>
                <p class="workspace-intro">
                    Keep a festival, a client programme, and your own events in separate workspaces.
                    Switch between the ones you belong to from the same account.
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
                        <div>
                            <p class="picker-label">Selected workspace</p>
                            <h3>{{ selected.name }}</h3>
                        </div>
                        <span class="workspace-role">{{ selected.role }}</span>
                    </div>
                    <ul class="workspace-events">
                        <li v-for="event in selected.events" :key="event">
                            <CalendarDays aria-hidden="true" />{{ event }}
                        </li>
                    </ul>
                    <p class="workspace-preview-note">
                        <LockKeyhole aria-hidden="true" />Illustrative events shown only for the selected workspace.
                    </p>
                </div>
            </div>

            <div class="workspace-rules">
                <div>
                    <UsersRound aria-hidden="true" />
                    <h3>Invite the people who need access</h3>
                    <p>Workspace owners can invite or remove members and manage connections. An invitation applies to that workspace only.</p>
                </div>
                <div>
                    <LockKeyhole aria-hidden="true" />
                    <h3>Keep data with its workspace</h3>
                    <p>Events, templates, and integrations stay separate. API reads require permission and only return data from the selected workspace your account can access.</p>
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
.workspace-kicker,
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
.workspace-option span,
.workspace-preview-note {
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
.workspace-events svg,
.workspace-preview-note svg {
    flex: none;
    color: #535f46;
}
.workspace-preview-note {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin: 0;
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
