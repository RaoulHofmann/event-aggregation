<main class="min-h-screen bg-[#fffaf1] px-6 py-10 text-[#29251f] sm:py-14">
    <article class="mx-auto max-w-3xl">
        <a href="{{ route('blog.index', absolute: false) }}" class="text-sm font-semibold underline underline-offset-4">Back to Blog</a>
        <header class="mt-6 border-b border-amber-900/20 pb-6">
            <time class="text-sm text-slate-600" datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->setTimezone('Australia/Perth')->format('j F Y') }}</time>
            <h1 class="mt-2 break-words text-4xl font-bold tracking-tight sm:text-5xl">{{ $post->title }}</h1>
            <p class="mt-3 break-words text-lg leading-7 text-slate-700">{{ $post->summary }}</p>
        </header>
        <div class="prose mt-7 max-w-none break-words prose-slate prose-a:text-emerald-800">
            {!! $post->bodyHtml() !!}
        </div>
    </article>
</main>
