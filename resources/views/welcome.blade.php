<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'ExamUp') }} | Modern online exams</title>
        <meta name="description" content="Create, deliver, and review secure online exams with ExamUp.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-[#fffaf3] font-sans text-[#1d2a3a] antialiased">
        <div class="relative isolate overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[42rem] bg-[radial-gradient(circle_at_12%_18%,rgba(255,206,125,0.72),transparent_22%),radial-gradient(circle_at_82%_7%,rgba(112,218,207,0.55),transparent_24%),linear-gradient(145deg,#fffaf3_8%,#f5f6ff_56%,#f5fffd_100%)]"></div>
            <div class="pointer-events-none absolute left-[-8rem] top-[34rem] -z-10 h-72 w-72 rounded-full bg-[#f7a58d]/25 blur-3xl"></div>
            <div class="pointer-events-none absolute right-[-8rem] top-[44rem] -z-10 h-80 w-80 rounded-full bg-[#9d8df2]/20 blur-3xl"></div>

            <div class="mx-auto max-w-7xl px-5 py-5 sm:px-8 lg:px-10">
                <header class="flex items-center justify-between rounded-full border border-[#1d2a3a]/8 bg-white/70 px-4 py-3 shadow-[0_8px_28px_rgba(46,58,78,0.06)] backdrop-blur sm:px-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ config('app.name', 'ExamUp') }} home">
                        <span class="grid size-10 place-items-center rounded-2xl bg-[#243b53] text-lg font-black text-[#ffd166] shadow-lg shadow-[#243b53]/15">E</span>
                        <span class="font-serif text-xl font-bold tracking-tight text-[#243b53]">ExamUp</span>
                    </a>

                    <a href="{{ backpack_url('dashboard') }}" class="inline-flex items-center gap-2 rounded-full bg-[#243b53] px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#243b53]/15 transition duration-200 hover:-translate-y-0.5 hover:bg-[#35516e] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f26d5b] focus-visible:ring-offset-2">
                        Admin login <span aria-hidden="true">&rarr;</span>
                    </a>
                </header>

                <main>
                    <section class="grid items-center gap-12 py-16 sm:py-20 lg:grid-cols-[0.92fr_1.08fr] lg:py-24">
                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 rounded-full border border-[#efb949]/30 bg-[#fff3d7] px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-[#8a5a00]">
                                <span class="size-2 rounded-full bg-[#f26d5b]"></span>
                                Assess with confidence
                            </div>

                            <h1 class="mt-6 font-serif text-5xl font-bold leading-[0.95] tracking-[-0.055em] text-[#243b53] sm:text-6xl lg:text-7xl">
                                Exams that feel
                                <span class="whitespace-nowrap text-[#f26d5b]">effortless.</span>
                            </h1>

                            <p class="mt-6 max-w-xl text-lg leading-8 text-[#536477] sm:text-xl">Build polished assessments, share them safely, and get the insight to make every result count.</p>

                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="{{ backpack_url('login') }}" class="inline-flex items-center justify-center rounded-full bg-[#f26d5b] px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-[#f26d5b]/25 transition duration-200 hover:-translate-y-0.5 hover:bg-[#dd5848] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f26d5b] focus-visible:ring-offset-2">Manage exams</a>
                                <a href="#features" class="inline-flex items-center justify-center rounded-full border border-[#243b53]/15 bg-white/70 px-6 py-3.5 text-sm font-bold text-[#243b53] transition duration-200 hover:-translate-y-0.5 hover:border-[#243b53]/30 hover:bg-white">See what&apos;s inside</a>
                            </div>

                            <div class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold text-[#536477]">
                                <span class="inline-flex items-center gap-2"><span class="text-[#1e9c89]">&#10003;</span> No student accounts</span>
                                <span class="inline-flex items-center gap-2"><span class="text-[#1e9c89]">&#10003;</span> Question banks</span>
                                <span class="inline-flex items-center gap-2"><span class="text-[#1e9c89]">&#10003;</span> Secure sharing</span>
                            </div>
                        </div>

                        <div class="relative mx-auto w-full max-w-2xl lg:max-w-none">
                            <div class="absolute -right-3 -top-5 grid size-20 place-items-center rounded-[1.6rem] bg-[#ffd166] text-3xl shadow-xl shadow-[#d89a21]/20 rotate-12 sm:-right-6 sm:-top-8">&#10022;</div>
                            <div class="absolute -bottom-5 -left-3 size-24 rounded-full border-[10px] border-[#72dacf] bg-[#effdf9] sm:-bottom-8 sm:-left-7"></div>

                            <div class="relative rounded-[2rem] border-[6px] border-white bg-[#243b53] p-4 shadow-[0_28px_60px_rgba(36,59,83,0.25)] sm:p-5">
                                <div class="rounded-[1.35rem] bg-[#f8fbff] p-4 sm:p-6">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-10 place-items-center rounded-xl bg-[#eeeaff] text-lg">&#128214;</span>
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#8a98a9]">Live exam</p>
                                                <p class="font-serif text-lg font-bold text-[#243b53]">World History</p>
                                            </div>
                                        </div>
                                        <span class="rounded-full bg-[#fff0ed] px-3 py-1.5 text-xs font-bold text-[#d74c3c]">18:42 left</span>
                                    </div>

                                    <div class="mt-6 h-2 overflow-hidden rounded-full bg-[#e2e8ef]"><div class="h-full w-[62%] rounded-full bg-gradient-to-r from-[#72dacf] to-[#3db7aa]"></div></div>

                                    <div class="mt-6 rounded-2xl border border-[#dfe8ef] bg-white p-5 shadow-sm">
                                        <div class="flex items-center justify-between gap-4 text-xs font-bold uppercase tracking-[0.12em] text-[#8492a3]"><span>Question 6 of 10</span><span class="text-[#1e9c89]">Saved</span></div>
                                        <p class="mt-4 font-serif text-xl font-bold leading-snug text-[#243b53]">Which event marked the beginning of the Renaissance?</p>

                                        <div class="mt-5 grid gap-3">
                                            <div class="flex items-center gap-3 rounded-xl border border-[#dfe8ef] px-4 py-3 text-sm font-semibold text-[#536477]"><span class="grid size-5 place-items-center rounded-full border border-[#aebcca]"></span>The fall of Rome</div>
                                            <div class="flex items-center gap-3 rounded-xl border-2 border-[#72dacf] bg-[#effdf9] px-4 py-3 text-sm font-bold text-[#243b53]"><span class="grid size-5 place-items-center rounded-full bg-[#1e9c89] text-[10px] text-white">&#10003;</span>A revival of learning and art</div>
                                            <div class="flex items-center gap-3 rounded-xl border border-[#dfe8ef] px-4 py-3 text-sm font-semibold text-[#536477]"><span class="grid size-5 place-items-center rounded-full border border-[#aebcca]"></span>The Industrial Revolution</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="features" class="pb-20 sm:pb-28">
                        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#f26d5b]">The full exam flow</p>
                                <h2 class="mt-2 font-serif text-4xl font-bold tracking-[-0.04em] text-[#243b53] sm:text-5xl">Create. Deliver. Learn.</h2>
                            </div>
                            <p class="max-w-md text-base leading-7 text-[#657487]">Everything important, in one friendly workspace.</p>
                        </div>

                        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <article class="rounded-[1.7rem] bg-[#243b53] p-6 text-white shadow-xl shadow-[#243b53]/10 transition duration-200 hover:-translate-y-1"><span class="grid size-12 place-items-center rounded-2xl bg-white/12 text-2xl">&#9998;</span><h3 class="mt-8 font-serif text-2xl font-bold">Build</h3><p class="mt-2 text-sm leading-6 text-[#d5e2ec]">Question cards, answer choices, and TXT imports.</p></article>
                            <article class="rounded-[1.7rem] bg-[#ffd166] p-6 text-[#243b53] shadow-xl shadow-[#d89a21]/10 transition duration-200 hover:-translate-y-1"><span class="grid size-12 place-items-center rounded-2xl bg-white/45 text-2xl">&#127922;</span><h3 class="mt-8 font-serif text-2xl font-bold">Randomize</h3><p class="mt-2 text-sm leading-6 text-[#654b16]">Draw a fresh, fair exam from a question bank.</p></article>
                            <article class="rounded-[1.7rem] bg-[#72dacf] p-6 text-[#173e44] shadow-xl shadow-[#1e9c89]/10 transition duration-200 hover:-translate-y-1"><span class="grid size-12 place-items-center rounded-2xl bg-white/45 text-2xl">&#9201;</span><h3 class="mt-8 font-serif text-2xl font-bold">Deliver</h3><p class="mt-2 text-sm leading-6 text-[#28555a]">Personal links, timers, auto-save, and retakes.</p></article>
                            <article class="rounded-[1.7rem] bg-[#eeeaff] p-6 text-[#3f356c] shadow-xl shadow-[#9d8df2]/10 transition duration-200 hover:-translate-y-1"><span class="grid size-12 place-items-center rounded-2xl bg-white/60 text-2xl">&#128200;</span><h3 class="mt-8 font-serif text-2xl font-bold">Review</h3><p class="mt-2 text-sm leading-6 text-[#615889]">Answers, scores, activity logs, and CSV exports.</p></article>
                        </div>

                        <div class="mt-5 grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
                            <article class="overflow-hidden rounded-[1.8rem] bg-white p-6 shadow-[0_16px_45px_rgba(51,75,95,0.08)] sm:p-8">
                                <div class="flex items-center justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#8a98a9]">At a glance</p><h3 class="mt-1 font-serif text-3xl font-bold text-[#243b53]">Results that speak clearly.</h3></div><span class="rounded-2xl bg-[#fff0ed] px-3 py-2 text-xs font-bold text-[#d74c3c]">Auto-graded</span></div>
                                <div class="mt-8 grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl bg-[#f4f8fb] p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8492a3]">Average</p><p class="mt-2 font-serif text-3xl font-bold text-[#243b53]">82%</p></div>
                                    <div class="rounded-2xl bg-[#effdf9] p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#54968d]">Best</p><p class="mt-2 font-serif text-3xl font-bold text-[#1e9c89]">100%</p></div>
                                    <div class="rounded-2xl bg-[#fff8df] p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#a17c20]">Time</p><p class="mt-2 font-serif text-3xl font-bold text-[#9a7210]">24m</p></div>
                                </div>
                            </article>

                            <article class="rounded-[1.8rem] bg-[#f26d5b] p-6 text-white shadow-xl shadow-[#f26d5b]/15 sm:p-8"><span class="grid size-12 place-items-center rounded-2xl bg-white/15 text-2xl">&#128274;</span><h3 class="mt-7 font-serif text-3xl font-bold">Secure by design.</h3><p class="mt-3 max-w-sm text-sm leading-6 text-[#fff0ed]">Expiry rules, fullscreen checks, clipboard deterrents, and suspicious activity logging.</p></article>
                        </div>
                    </section>

                    <section class="mb-12 overflow-hidden rounded-[2rem] bg-[#243b53] px-6 py-10 text-center text-white shadow-2xl shadow-[#243b53]/20 sm:mb-16 sm:px-10">
                        <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#ffd166]">Your exam workspace</p>
                        <h2 class="mx-auto mt-3 max-w-2xl font-serif text-4xl font-bold tracking-[-0.04em] sm:text-5xl">Ready when you are.</h2>
                        <a href="{{ backpack_url('login') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#ffd166] px-6 py-3.5 text-sm font-bold text-[#243b53] transition duration-200 hover:-translate-y-0.5 hover:bg-[#ffe09a] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#243b53]">Admin login <span aria-hidden="true">&rarr;</span></a>
                    </section>
                </main>

                <footer class="pb-8 text-center text-sm font-medium text-[#7b8997]">{{ config('app.name', 'ExamUp') }} &middot; Online assessment, made clearer.</footer>
            </div>
        </div>
    </body>
</html>
