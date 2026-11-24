<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({ post: Object });
const form = useForm({
    title: props.post?.title ?? "",
    summary: props.post?.summary ?? "",
    body: props.post?.body ?? "",
    status: props.post?.published_at ? "published" : "draft",
});

function save(event) {
    form.status = event.submitter?.value || (props.post?.published_at ? "published" : "draft");
    if (props.post) {
        form.put(route("admin.blog.update", props.post.id));
    } else {
        form.post(route("admin.blog.store"));
    }
}
</script>

<template>
    <AppLayout :title="post ? 'Edit blog post' : 'New blog post'">
        <Link :href="route('admin.blog.index')" class="text-sm font-medium text-emerald-800 underline">Back to blog posts</Link>
        <header class="mb-6 mt-3 flex max-w-4xl flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-semibold tracking-tight">{{ post ? "Edit blog post" : "New blog post" }}</h1>
            <a v-if="post?.published_at" :href="route('blog.show', post.slug)" class="workspace-secondary" target="_blank" rel="noopener">View published post</a>
        </header>
        <form class="workspace-panel max-w-4xl space-y-5 p-5 sm:p-7" @submit.prevent="save">
            <div>
                <label for="blog-title" class="workspace-label">Title</label>
                <input id="blog-title" v-model="form.title" class="workspace-input" maxlength="160" required />
            </div>
            <div>
                <label for="blog-summary" class="workspace-label">Summary</label>
                <input id="blog-summary" v-model="form.summary" class="workspace-input" maxlength="200" required />
                <p class="mt-1 text-sm text-slate-600">Shown in the blog list and used as the page description for search.</p>
            </div>
            <div>
                <label for="blog-body" class="workspace-label">Post body</label>
                <textarea id="blog-body" v-model="form.body" class="workspace-input font-mono text-sm" rows="15" maxlength="50000" required />
                <p class="mt-1 text-sm text-slate-600">Use Markdown for headings, links and lists.</p>
            </div>
            <p v-for="(error, field) in form.errors" :key="field" class="text-sm text-red-700" role="alert">{{ error }}</p>
            <div class="flex flex-wrap gap-3 border-t pt-4">
                <button type="submit" value="draft" class="workspace-secondary" :disabled="form.processing">{{ post?.published_at ? "Move to draft" : "Save draft" }}</button>
                <button type="submit" value="published" class="workspace-primary" :disabled="form.processing">{{ post?.published_at ? "Save changes" : "Publish post" }}</button>
            </div>
        </form>
    </AppLayout>
</template>
