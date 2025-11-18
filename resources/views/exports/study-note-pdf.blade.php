<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $note->title ?? 'Study Note' }}</title>
    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family:
                {{ isset($font) && $font ? "'" . e($font) . "'" : 'DejaVu Sans' }}
                , Arial, sans-serif;
            color: #111827;
            font-size: 12px;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 8px;
        }

        h2 {
            font-size: 16px;
            margin: 18px 0 8px;
        }

        p {
            line-height: 1.5;
            margin: 0 0 8px;
        }

        ul {
            margin: 0 0 8px 18px;
        }

        li {
            margin: 0 0 4px;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 16px;
        }

        .hr {
            height: 1px;
            background: #e5e7eb;
            margin: 12px 0;
        }

        .badge {
            display: inline-block;
            font-size: 10px;
            color: #374151;
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 999px;
        }
    </style>
</head>

<body>
    <h1>{{ $note->title ?? 'Study Note' }}</h1>
    <div class="badge">Source: {{ ucfirst((string) $note->source_type) }}</div>
    @if(!empty($note->difficulty_level))
        <div class="badge" style="background: #10b981; color: #0b3b2e; margin-left: 6px;">Difficulty:
            {{ ucfirst($note->difficulty_level) }}
        </div>
    @endif
    @if(!is_null($note->estimated_study_time))
        <span style="font-size: 10px; color: #6366f1; margin-left: 6px;">Study Time:
            {{ $note->formatted_study_time }}</span>
    @endif
    <div class="hr"></div>

    @if(!empty($note->summary))
        <h2>Summary</h2>
        <p>{{ $note->summary }}</p>
    @endif

    @if(!empty($note->key_concepts))
        <h2>Key Concepts</h2>
        <ul>
            @foreach($note->key_concepts as $concept)
                <li>
                    <strong>{{ $concept['concept'] ?? '' }}</strong>
                    @if(!empty($concept['explanation'])) - {{ $concept['explanation'] }} @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if(!empty($note->study_questions))
    <h2>Study Questions</h2>
    <ul>
        @foreach($note->study_questions as $q)
        @php($qtext = is_array($q) ? ($q['question'] ?? '') : (string) $q)
        <li>{{ $qtext }}</li>
        @endforeach
    </ul>
    @endif

    <div class="hr"></div>
    <div class="meta">
        Generated from {{ ucfirst((string) $note->source_type) }} ·
        @if($note->created_at)
            Created {{ $note->created_at->toDayDateTimeString() }}
        @endif
    </div>
</body>

</html>