<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Preview</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-4 px-4">
            <span class="inline-flex items-center gap-2 text-sm text-slate-500">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                Preview Mode - Edit audit.blade.php to see changes
            </span>
        </div>
        @include('reports.audit-content', ['audit' => $audit])
    </div>
</body>
</html>
