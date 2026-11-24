<main class="min-h-screen bg-[#fffaf1] px-6 py-12 text-[#29251f] sm:py-16">
    <div class="mx-auto max-w-5xl">
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Blog</h1>
        <p class="mt-3 max-w-2xl text-lg leading-7 text-slate-700">EventJunction updates and practical guides for managing and publishing events.</p>

        <div class="mt-9 grid gap-4 sm:grid-cols-2" aria-label="Published posts">
            @forelse ($posts as $post)
                <article class="rounded-xl border border-amber-900/20 bg-white p-5 sm:p-6">
                    <time class="text-sm text-slate-600" datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->setTimezone('Australia/Perth')->format('j F Y') }}</time>
                    <h2 class="mt-2 break-words text-2xl font-semibold">
                        <a class="underline-offset-4 hover:underline" href="{{ route('blog.show', $post->slug, absolute: false) }}">{{ $post->title }}</a>
                    </h2>
                    <p class="mt-2 break-words leading-7 text-slate-700">{{ $post->summary }}</p>
                </article>
            @empty
                <p class="text-slate-600">No published posts yet.</p>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <nav class="mt-8 flex flex-wrap items-center gap-4 text-sm" aria-label="Blog pages">
                @if ($posts->previousPageUrl())<a class="font-semibold underline" href="{{ $posts->previousPageUrl() }}">Previous page</a>@endif
                <span class="text-slate-600">Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
                @if ($posts->nextPageUrl())<a class="font-semibold underline" href="{{ $posts->nextPageUrl() }}">Next page</a>@endif
            </nav>
        @endif
    </div>
</main>
