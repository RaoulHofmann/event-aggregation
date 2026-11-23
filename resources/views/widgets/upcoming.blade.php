<!DOCTYPE html>
<html lang="en" class="theme-{{ $appearance['theme'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $detail ? $detail['title'].' · '.$title : $title }}</title>
    <link rel="stylesheet" href="/embed-agenda.css">
</head>
<body>
    <main class="agenda{{ $appearance['compact'] ? ' compact' : '' }}">
        <header>
            <div>
                @if ($appearance['brand'])<p class="brand">{{ $appearance['brand'] }}</p>@endif
                @if ($detail)<p class="agenda-name">{{ $title }}</p>@else<h1>{{ $title }}</h1>@endif
            </div>
            @if ($example)<span>Example calendar</span>@endif
        </header>
        @if ($showBack)<a class="back" href="{{ $back }}">← Back to events</a>@endif
        @if ($detail)
            <article class="detail">
                <h1>{{ $detail['title'] }}</h1>
                <time datetime="{{ $detail['starts'] }}">{{ $detail['date'] }} · {{ $detail['time'] }}</time>
                @if ($detail['location'])<p class="location">{{ $detail['location'] }}</p>@endif
                @if ($detail['cover'])<img class="cover" src="{{ $detail['cover'] }}" alt="">@endif
                @if ($detail['description'])<p class="description">{{ $detail['description'] }}</p>@endif
            </article>
        @elseif ($items)
            <ol>
                @foreach ($items as $item)
                    <li>
                        <div class="date" aria-hidden="true"><strong>{{ $item['day'] }}</strong><span>{{ $item['month'] }}</span></div>
                        <div class="event">
                            <h2><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h2>
                            <time datetime="{{ $item['starts'] }}">{{ $item['date'] }} · {{ $item['time'] }}</time>
                            @if ($item['location'])<p>{{ $item['location'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="empty">{{ $message ?? 'No upcoming events.' }}</p>
        @endif
        @if ($appearance['show_credit'])
            <footer><a href="/" target="_blank" rel="noopener noreferrer">EventJunction</a></footer>
        @endif
    </main>
</body>
</html>
