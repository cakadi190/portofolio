<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $name }} - CV</title>
    <style>
        @font-face { font-family: 'Signika'; font-weight: normal; font-style: normal; src: url('{{ resource_path('fonts/signika/Signika-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Signika'; font-weight: bold; font-style: normal; src: url('{{ resource_path('fonts/signika/Signika-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Fira Mono'; font-weight: normal; font-style: normal; src: url('{{ resource_path('fonts/fira-mono/FiraMono-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Fira Mono'; font-weight: bold; font-style: normal; src: url('{{ resource_path('fonts/fira-mono/FiraMono-Bold.ttf') }}') format('truetype'); }

        @page { margin: 38px 46px 42px; }
        body { font-family: 'Signika', sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.25; margin: 0; }
        .mono { font-family: 'Fira Mono', monospace; font-size: 7.5pt; }

        .hero { margin: -38px -46px 6px; padding: 32px 46px 20px; background: #0e4f26; color: #fff; border-bottom: 5px solid #22c55e; }
        .hero h1 { font-size: 26pt; line-height: 1.1; margin: 0 0 3px; }
        .hero .headline { font-size: 13pt; color: #86efac; font-weight: bold; margin: 0 0 9px; }
        .hero .contact { font-size: 10pt; color: #d1fae5; margin: 0; }
        .hero .contact a { color: #d1fae5; }
        .hero .contact span + span:before { content: '  |  '; color: #4ade80; }

        h2 { font-size: 11.5pt; color: #0e4f26; text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 7px; padding: 3px 0 3px 9px; border-left: 4px solid #22c55e; background: #f0fdf4; }
        .section { margin-top: 15px; }
        .entry { margin-bottom: 7px; page-break-inside: avoid; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { padding: 0; vertical-align: top; }
        .row .when { width: 125px; text-align: right; color: #475569; font-size: 9.5pt; white-space: nowrap; }
        h3 { font-size: 11.5pt; color: #111827; margin: 0; }
        .org { color: #15803d; font-weight: bold; margin: 0; }
        .sub { color: #475569; font-size: 9.5pt; margin: 0; }
        p { margin: 1px 0; }
        .chip { display: inline-block; background: #dcfce7; color: #14532d; border-radius: 7px; padding: 1px 6px; margin: 3px 3px 0 0; }
        ul { margin: 0; padding-left: 17px; }
        li { margin-bottom: 3px; }
        a { color: #15803d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>{{ $name }}</h1>
        <p class="headline">{{ $headline }}</p>
        <p class="contact">@foreach ($contacts as $item)<span>@if ($item['href'])<a href="{{ $item['href'] }}">{{ $item['label'] }}</a>@else{{ $item['label'] }}@endif</span>@endforeach</p>
    </div>

    @foreach ($sections as $section)
        <div class="section">
        @if (collect($section['entries'])->every(fn ($e) => ($e['bullet'] ?? false)))
            <div class="entry"><h2>{{ $section['title'] }}</h2>
                <ul>
                    @foreach ($section['entries'] as $entry)
                        <li>{{ $entry['lines'][0] }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            @foreach ($section['entries'] as $entry)
                <div class="entry">
                    @if ($loop->first)<h2>{{ $section['title'] }}</h2>@endif
                    @if (isset($entry['head']))<table class="row"><tr>
                        <td>@isset($entry['head'])<h3>{{ $entry['head'] }}</h3>@endisset</td>
                        @isset($entry['period'])<td class="when">{{ $entry['period'] }}</td>@endisset
                    </tr></table>@endif
                    @if (! empty($entry['org']) || ! empty($entry['meta']))
                        <p>@if (! empty($entry['org']))<span class="org">{{ $entry['org'] }}</span>@endif
                        @if (! empty($entry['org']) && ! empty($entry['meta']))<span class="sub"> · </span>@endif
                        @if (! empty($entry['meta']))<span class="sub">{{ $entry['meta'] }}</span>@endif</p>
                    @endif
                    @foreach ($entry['lines'] ?? [] as $line)<p>{{ $line }}</p>@endforeach
                    @foreach ($entry['chips'] ?? [] as $chip)<span class="chip mono">{{ $chip }}</span>@endforeach
                    @if (! empty($entry['url']))<p><a class="mono" href="{{ $entry['url'] }}">{{ $entry['urlLabel'] }}</a></p>@endif
                </div>
            @endforeach
        @endif
        </div>
    @endforeach
</body>
</html>
