<template>
    <main class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-5 py-12">
        <slot name="logo" />
        <div
            class="mt-8 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
        >
            <slot />
        </div>
        <p class="mt-6 text-xs text-slate-500">A home for all your events.</p>
    </main>
</template>
