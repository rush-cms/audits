<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Audit Report</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        {!! $tailwindCss !!}
    </style>
</head>
<body class="bg-slate-100 p-8 print:p-0 print:bg-white">
    <div class="max-w-4xl mx-auto">
        @include('reports.audit-content', ['audit' => $audit])
    </div>
</body>
</html>
