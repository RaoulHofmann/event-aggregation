<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({ posts: Object });

const publishedDate = (date) => new Intl.DateTimeFormat("en-AU", {
    dateStyle: "medium",
    timeZone: "Australia/Perth",
}).format(new Date(date));
</script>

<template>
    <AppLayout title="Blog posts">
        <Link :href="route('admin.index')" class="text-sm font-medium text-emerald-800 underline">Back to Administration</Link>
        <header class="mb-6 mt-3 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold tracking-tight">Blog posts</h1>
            <div class="flex flex-wrap gap-2">
                <a href="/blog" class="workspace-secondary" target="_blank" rel="noopener">View blog</a>
                <Link :href="route('admin.blog.create')" class="workspace-primary">New post</Link>
            </div>
        </header>
        <section class="workspace-panel overflow-hidden" aria-label="Blog posts">
            <p v-if="!posts.data.length" class="p-6 text-sm text-slate-600">No posts yet.</p>
            <ul v-else class="divide-y divide-slate-200">
                <li v-for="post in posts.data" :key="post.id" class="flex flex-wrap items-center justify-between gap-3 p-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="break-words font-semibold">
                            <Link :href="route('admin.blog.edit', post.id)" class="hover:underline">{{ post.title }}</Link>
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">{{ post.published_at ? `Published · ${publishedDate(post.published_at)}` : "Draft" }}</p>
                    </div>
                    <Link :href="route('admin.blog.edit', post.id)" class="workspace-secondary" :aria-label="`Edit ${post.title}`">Edit</Link>
                </li>
            </ul>
            <nav v-if="posts.last_page > 1" class="flex flex-wrap items-center gap-3 border-t p-4 sm:px-6" aria-label="Post pages">
                <Link v-if="posts.prev_page_url" :href="posts.prev_page_url" class="workspace-secondary">Previous page</Link>
                <span class="text-sm text-slate-600">Page {{ posts.current_page }} of {{ posts.last_page }}</span>
                <Link v-if="posts.next_page_url" :href="posts.next_page_url" class="workspace-secondary">Next page</Link>
            </nav>
        </section>
    </AppLayout>
</template>
