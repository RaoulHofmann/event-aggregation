<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="/embed-agenda.css">
</head>
<body>
    <main class="agenda">
        <header>
            <h1>{{ $title }}</h1>
            @if ($example)<span>Example calendar</span>@endif
        </header>
        @if ($items)
            <ol>
                @foreach ($items as $item)
                    <li>
                        <div class="date" aria-hidden="true"><strong>{{ $item['day'] }}</strong><span>{{ $item['month'] }}</span></div>
                        <div class="event">
                            <h2>{{ $item['title'] }}</h2>
                            <time datetime="{{ $item['starts'] }}">{{ $item['date'] }} · {{ $item['time'] }}</time>
                            @if ($item['location'])<p>{{ $item['location'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="empty">{{ $message ?? 'No upcoming events.' }}</p>
        @endif
        <footer><a href="/" target="_blank" rel="noopener noreferrer">EventJunction</a></footer>
    </main>
</body>
</html>
