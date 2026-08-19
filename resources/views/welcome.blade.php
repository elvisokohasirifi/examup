<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'ExamUp') }} | Online Exam Platform</title>
        <meta
            name="description"
            content="ExamUp is a production-ready online exam system for schools, academies, and certification teams with Backpack-powered administration and a polished Livewire exam experience."
        >

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-slate-950 text-slate-100 antialiased">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_80%_20%,rgba(249,115,22,0.18),transparent_26%),linear-gradient(180deg,#020617_0%,#0f172a_45%,#111827_100%)]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-10">
                <header class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-sky-300">ExamUp</p>
                        <p class="mt-2 max-w-xl text-sm text-slate-300">
                            Secure online exams for teams that need structure, speed, and reliable grading.
                        </p>
                    </div>

                    <a
                        href="{{ backpack_url('login') }}"
                        class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-sky-300/60 hover:bg-sky-400/15 hover:text-sky-100"
                    >
                        Admin Login
                    </a>
                </header>

                <main class="flex-1 py-14 lg:py-20">
                    <section class="grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <div class="inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-sm font-medium text-emerald-200">
                                Built for administrators, examiners, and real exam sessions
                            </div>

                            <h1 class="mt-8 max-w-4xl font-serif text-5xl leading-tight text-white sm:text-6xl lg:text-7xl">
                                Run beautiful, secure online exams without juggling multiple tools.
                            </h1>

                            <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-300 sm:text-xl">
                                {{ config('app.name', 'ExamUp') }} combines Backpack for administration and examiner workflows with a Livewire-powered student exam interface, so teams can create exams, deliver them securely, grade automatically, and review performance in one place.
                            </p>

                            <div class="mt-10 flex flex-wrap gap-4">
                                <a
                                    href="{{ backpack_url('login') }}"
                                    class="inline-flex items-center rounded-full bg-sky-400 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:bg-sky-300"
                                >
                                    Go to Admin Panel
                                </a>
                                <a
                                    href="#features"
                                    class="inline-flex items-center rounded-full border border-white/15 px-6 py-3 text-sm font-semibold text-slate-100 transition hover:-translate-y-0.5 hover:border-orange-300/60 hover:bg-white/5"
                                >
                                    Explore Features
                                </a>
                            </div>

                            <div class="mt-12 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur">
                                    <p class="text-3xl font-semibold text-white">2</p>
                                    <p class="mt-2 text-sm text-slate-300">Question types: multiple choice and fill-in with multiple correct answers.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur">
                                    <p class="text-3xl font-semibold text-white">Live</p>
                                    <p class="mt-2 text-sm text-slate-300">Auto-save, timed attempts, controlled score visibility, and exam expiry support.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur">
                                    <p class="text-3xl font-semibold text-white">CSV</p>
                                    <p class="mt-2 text-sm text-slate-300">Results export plus per-question analytics for fast examiner review.</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute -inset-6 rounded-[2rem] bg-sky-400/10 blur-3xl"></div>
                            <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/80 p-6 shadow-2xl shadow-slate-950/50 backdrop-blur">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-sky-300">Platform Snapshot</p>
                                        <h2 class="mt-3 text-2xl font-semibold text-white">What the app already handles</h2>
                                    </div>
                                    <div class="rounded-full border border-orange-300/30 bg-orange-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-orange-200">
                                        Production ready
                                    </div>
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div class="rounded-2xl bg-white/5 p-4">
                                        <p class="text-sm font-semibold text-white">Back office roles</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Admins manage users and settings. Examiners create exams, compose nested questions inline, distribute secure links, and review results.</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/5 p-4">
                                        <p class="text-sm font-semibold text-white">Student access</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Students are not user accounts. They enter through secure links, provide name and email, and can optionally be asked for an index number.</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/5 p-4">
                                        <p class="text-sm font-semibold text-white">Exam controls</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Show-all or one-at-a-time delivery, question shuffling, timing controls, auto-grading, auto-save, and examiner-defined result visibility.</p>
                                    </div>
                                    <div class="rounded-2xl bg-gradient-to-r from-sky-400/12 to-orange-400/12 p-4 ring-1 ring-white/10">
                                        <p class="text-sm font-semibold text-white">Access and security</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Shareable exam links, per-email invite links with prefixed email, suspicious activity logging, and anti-copy / anti-paste deterrents.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="features" class="mt-24">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-orange-300">Feature Overview</p>
                            <h2 class="mt-4 text-3xl font-semibold text-white sm:text-4xl">Everything needed to create, deliver, and review online assessments</h2>
                            <p class="mt-4 text-lg leading-8 text-slate-300">
                                The platform is designed around the exact workflow your app already supports, from exam drafting to secure delivery and analytics after submission.
                            </p>
                        </div>

                        <div class="mt-10 grid gap-6 lg:grid-cols-3">
                            <article class="rounded-[1.75rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Authoring</p>
                                <h3 class="mt-4 text-xl font-semibold text-white">Exams built around questions</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-300">
                                    Multi-step exam creation, inline question cards, repeatable answer options, descriptions, points, optional help text, and flexible scoring controls.
                                </p>
                            </article>

                            <article class="rounded-[1.75rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-300">Delivery</p>
                                <h3 class="mt-4 text-xl font-semibold text-white">Exam sessions that feel controlled</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-300">
                                    Timed attempts, show-all or step-by-step navigation, optional question shuffle, expiry date and time, preview links, and smooth Livewire-driven progress.
                                </p>
                            </article>

                            <article class="rounded-[1.75rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-300">Insights</p>
                                <h3 class="mt-4 text-xl font-semibold text-white">Results examiners can act on</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-300">
                                    Per-student scores, CSV download, averages, highest and lowest scores, completion-time metrics, and answer-distribution statistics for each question.
                                </p>
                            </article>
                        </div>
                    </section>

                    <section class="mt-24 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="rounded-[2rem] border border-white/10 bg-slate-900/70 p-8 backdrop-blur">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-sky-300">Why Teams Use It</p>
                            <h2 class="mt-4 text-3xl font-semibold text-white">One platform for the full exam lifecycle</h2>
                            <ul class="mt-8 space-y-4 text-sm leading-7 text-slate-300">
                                <li>Backpack is reserved for admins and examiners, keeping the student experience clean and focused.</li>
                                <li>Students never need admin accounts to sit for exams.</li>
                                <li>Exam access can be tightly controlled through shared links or individual email invitations.</li>
                                <li>Examiners decide when students can see scores and whether correct answers are revealed.</li>
                                <li>Logs and deterrents help surface suspicious exam behavior without making the interface heavy.</li>
                            </ul>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div class="rounded-[2rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold text-white">Student Intake</p>
                                <p class="mt-3 text-sm leading-7 text-slate-300">Name and email are captured at exam start, with an optional index number field that admins or examiners can enable per exam.</p>
                            </div>
                            <div class="rounded-[2rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold text-white">Auto Grading</p>
                                <p class="mt-3 text-sm leading-7 text-slate-300">Objective question types can be graded automatically, reducing turnaround time immediately after submission.</p>
                            </div>
                            <div class="rounded-[2rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold text-white">Exam Previews</p>
                                <p class="mt-3 text-sm leading-7 text-slate-300">Preview exam links are available from the exam listing so teams can validate structure before sharing access with students.</p>
                            </div>
                            <div class="rounded-[2rem] border border-white/10 bg-white/6 p-7 backdrop-blur">
                                <p class="text-sm font-semibold text-white">Operational Simplicity</p>
                                <p class="mt-3 text-sm leading-7 text-slate-300">A first user can be promoted as admin, then admins can add examiner accounts and manage the system from one secure panel.</p>
                            </div>
                        </div>
                    </section>

                    <section class="mt-24">
                        <div class="overflow-hidden rounded-[2.25rem] border border-white/10 bg-gradient-to-r from-sky-400/14 via-cyan-300/10 to-orange-400/14 p-8 sm:p-10">
                            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                                <div class="max-w-3xl">
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-sky-200">Ready To Manage Exams?</p>
                                    <h2 class="mt-4 text-3xl font-semibold text-white sm:text-4xl">Enter the admin and examiner workspace.</h2>
                                    <p class="mt-4 text-base leading-8 text-slate-200">
                                        Use the Backpack panel to create exams, configure access, invite candidates, preview delivery, and review performance after the exam closes.
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-4">
                                    <a
                                        href="{{ backpack_url('login') }}"
                                        class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:bg-sky-100"
                                    >
                                        Admin Login
                                    </a>
                                    <a
                                        href="#features"
                                        class="inline-flex items-center rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/10"
                                    >
                                        Review Features
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
