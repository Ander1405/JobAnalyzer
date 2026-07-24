<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $profile->label }}</title>
    <style>
        @page { margin: 22mm 18mm; }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #1a1a1a;
        }

        h1 { font-size: 18pt; margin: 0 0 2mm; }
        h2 {
            font-size: 12pt;
            margin: 6mm 0 2mm;
            padding-bottom: 1mm;
            border-bottom: 0.5pt solid #ccc;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        h3 { font-size: 10.5pt; margin: 3mm 0 0.5mm; }
        p { margin: 0 0 2mm; }
        ul { margin: 0 0 2mm; padding-left: 5mm; }
        li { margin-bottom: 1mm; }

        .subtitle { font-size: 10pt; color: #555; margin: 0 0 4mm; }
    </style>
</head>
<body>
    <h1>{{ $profile->headline ?? $profile->label }}</h1>
    @if ($job)
        <p class="subtitle">CV adaptado para {{ $job->title }} · {{ $job->company }}</p>
    @endif

    {!! $contentHtml !!}
</body>
</html>
