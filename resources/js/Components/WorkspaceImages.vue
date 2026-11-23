<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";
import { ImagePlus } from "lucide-vue-next";

const props = defineProps({
    selectable: Boolean,
    canUpload: { type: Boolean, default: true },
    canDelete: Boolean,
});
const emit = defineEmits(["select", "uploading"]);
const fileInput = ref(null);
const search = ref("");
const results = ref(null);
const loading = ref(false);
const uploading = ref(false);
const deleting = ref(null);
const error = ref("");
const lookup = ref("");
const headers = { Accept: "application/json" };

function message(exception, fallback) {
    return exception.response?.data?.errors?.image?.[0]
        || exception.response?.data?.message
        || fallback;
}

async function load(page = 1) {
    loading.value = true;
    error.value = "";
    try {
        const response = await axios.get(route("assets.images.list"), {
            params: { search: lookup.value, page },
            headers,
        });
        results.value = response.data;
    } catch {
        error.value = "Could not load the image library.";
    } finally {
        loading.value = false;
    }
}

function find() {
    if (loading.value) return;
    lookup.value = search.value.trim();
    load();
}

async function upload(event) {
    const file = event.target.files[0];
    event.target.value = "";
    if (!file || uploading.value) return;
    const data = new FormData();
    data.append("image", file);
    uploading.value = true;
    emit("uploading", true);
    error.value = "";
    try {
        const response = await axios.post(route("assets.images.store"), data, { headers });
        if (props.selectable) {
            emit("select", response.data);
        } else {
            search.value = "";
            lookup.value = "";
            await load();
        }
    } catch (exception) {
        error.value = message(exception, "Could not upload this image.");
    } finally {
        uploading.value = false;
        emit("uploading", false);
    }
}

async function remove(image) {
    if (deleting.value || !window.confirm(`Delete “${image.name}” from this workspace?`)) return;
    deleting.value = image.id;
    error.value = "";
    try {
        await axios.delete(route("assets.images.destroy", image.id), { headers });
        const page = results.value.current_page;
        await load(results.value.data.length === 1 ? Math.max(1, page - 1) : page);
    } catch (exception) {
        error.value = message(exception, "Could not delete this image.");
    } finally {
        deleting.value = null;
    }
}

onMounted(() => load());
</script>

<template>
    <div class="min-w-0 space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex min-w-0 flex-1 flex-wrap items-end gap-2">
                <label class="block min-w-0 flex-1 text-sm font-medium sm:max-w-sm">
                    Search images
                    <input v-model="search" type="search" maxlength="100" class="workspace-input mt-1" @keydown.enter.prevent="find" />
                </label>
                <button type="button" class="workspace-secondary" :disabled="loading || uploading" @click="find">Search</button>
            </div>
            <div v-if="canUpload" class="shrink-0">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    :disabled="uploading"
                    @change="upload"
                />
                <button type="button" class="workspace-primary" :disabled="uploading || loading" @click="fileInput.click()">
                    <ImagePlus class="size-4" /> {{ uploading ? 'Uploading…' : 'Upload image' }}
                </button>
            </div>
        </div>
        <p v-if="canUpload" class="text-xs text-slate-600">JPEG, PNG or WebP, up to 5 MB and 4096 × 4096 pixels.</p>
        <p v-if="error" role="alert" class="text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" role="status" class="text-sm text-slate-600">Loading images…</p>
        <p v-else-if="results && !results.data.length" class="rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-600">
            {{ lookup ? 'No images match your search.' : 'No images in this workspace yet.' }}
        </p>
        <ul v-else-if="results" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            <li v-for="image in results.data" :key="image.id" class="min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <img :src="route('assets.images.show', image.id)" alt="" loading="lazy" class="aspect-video w-full bg-slate-100 object-contain" />
                <div class="space-y-2 p-3">
                    <p class="truncate text-sm font-medium" :title="image.name">{{ image.name }}</p>
                    <button
                        v-if="selectable"
                        type="button"
                        class="workspace-secondary w-full"
                        :disabled="uploading"
                        :aria-label="`Choose ${image.name}`"
                        @click="emit('select', image)"
                    >Choose image</button>
                    <button
                        v-else-if="canDelete"
                        type="button"
                        class="text-sm font-medium text-red-700 hover:underline disabled:opacity-50"
                        :disabled="Boolean(deleting) || uploading"
                        :aria-label="`Delete ${image.name}`"
                        @click="remove(image)"
                    >{{ deleting === image.id ? 'Deleting…' : 'Delete' }}</button>
                </div>
            </li>
        </ul>
        <nav v-if="results?.last_page > 1 && !loading" class="flex flex-wrap items-center gap-2" aria-label="Image library pages">
            <button type="button" class="workspace-secondary" :disabled="results.current_page === 1 || uploading || Boolean(deleting)" @click="load(results.current_page - 1)">Previous</button>
            <span class="text-sm text-slate-600">Page {{ results.current_page }} of {{ results.last_page }}</span>
            <button type="button" class="workspace-secondary" :disabled="results.current_page === results.last_page || uploading || Boolean(deleting)" @click="load(results.current_page + 1)">Next</button>
        </nav>
    </div>
</template>
