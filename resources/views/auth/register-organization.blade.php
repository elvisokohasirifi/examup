<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your ExamUP organisation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#fef3c7,_#fff7ed_38%,_#f8fafc_74%)] p-5 text-slate-900 sm:p-10">
    <main class="mx-auto max-w-xl rounded-[2rem] bg-white p-7 shadow-2xl shadow-slate-900/10 sm:p-10">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">ExamUP for organisations</p>
        <h1 class="mt-3 font-serif text-3xl font-bold tracking-tight">Create your workspace</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Set up your organisation and its first administrator. Teams, departments, exams, and results are private to your organisation.</p>
        <form method="POST" action="{{ route('organization.register.store') }}" class="mt-8 space-y-5">
            @csrf
            @foreach (['organization_name' => 'Organisation name', 'name' => 'Your full name', 'email' => 'Email address'] as $field => $label)
                <label class="block text-sm font-medium text-slate-700">{{ $label }}
                    <input name="{{ $field }}" @if ($field === 'email') type="email" @endif value="{{ old($field) }}" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-amber-400" />
                </label>
            @endforeach
            <label class="block text-sm font-medium text-slate-700">Password
                <input name="password" type="password" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-amber-400" />
            </label>
            <label class="block text-sm font-medium text-slate-700">Confirm password
                <input name="password_confirmation" type="password" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-amber-400" />
            </label>
            @foreach ($errors->all() as $error)<p class="text-sm text-red-700">{{ $error }}</p>@endforeach
            <button class="w-full rounded-full bg-slate-950 px-5 py-3 font-semibold text-white transition hover:bg-slate-800">Create organisation</button>
        </form>
    </main>
</body>
</html>
