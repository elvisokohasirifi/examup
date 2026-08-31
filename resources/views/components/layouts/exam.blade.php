<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,_#fef3c7,_#fff7ed_35%,_#f8fafc_70%)] text-slate-900">
    {{ $slot }}
    @livewireScripts
    <script>
        window.examActivityMonitor = (options = {}) => ({
            disableCopyPaste: options.disableCopyPaste ?? false,
            requireFullscreen: options.requireFullscreen ?? false,
            expiresAt: options.expiresAt ?? null,
            fullscreenUnsupported: false,
            lastReportedAt: 0,
            countdownInterval: null,
            fullscreenExitTimeout: null,
            fullscreenExitInterval: null,
            secondsUntilFullscreenSubmission: 0,
            timeRemaining: null,

            init() {
                this.setExamCountdown(this.expiresAt);
                this.fullscreenUnsupported = this.requireFullscreen && !this.supportsFullscreen();

                this.onVisibilityChange = () => {
                    if (document.hidden) {
                        this.report('tab_hidden');
                    }
                };

                this.onWindowBlur = () => {
                    window.setTimeout(() => {
                        if (!document.hasFocus()) {
                            this.report('window_blur');
                        }
                    });
                };

                this.onCopy = (event) => this.handleClipboardEvent(event, 'copy');
                this.onPaste = (event) => this.handleClipboardEvent(event, 'paste');
                this.onFullscreenChange = async () => {
                    const isFullscreen = Boolean(document.fullscreenElement);

                    await this.$wire.$set('fullscreenConfirmed', isFullscreen);

                    if (!this.requireFullscreen) {
                        return;
                    }

                    if (isFullscreen) {
                        this.clearFullscreenExitCountdown();
                        await this.$wire.dismissFullscreenExitWarning();

                        return;
                    }

                    this.report('fullscreen_exit', true);
                    this.startFullscreenExitCountdown();
                };
                this.onExamAttemptStarted = ({ detail }) => {
                    this.setExamCountdown(detail.expiresAt ?? null);
                };

                document.addEventListener('visibilitychange', this.onVisibilityChange);
                window.addEventListener('blur', this.onWindowBlur);
                document.addEventListener('copy', this.onCopy);
                document.addEventListener('paste', this.onPaste);
                document.addEventListener('fullscreenchange', this.onFullscreenChange);
                window.addEventListener('exam-attempt-started', this.onExamAttemptStarted);
            },

            destroy() {
                document.removeEventListener('visibilitychange', this.onVisibilityChange);
                window.removeEventListener('blur', this.onWindowBlur);
                document.removeEventListener('copy', this.onCopy);
                document.removeEventListener('paste', this.onPaste);
                document.removeEventListener('fullscreenchange', this.onFullscreenChange);
                window.removeEventListener('exam-attempt-started', this.onExamAttemptStarted);
                this.clearExamCountdown();
                this.clearFullscreenExitCountdown();
            },

            setExamCountdown(expiresAt) {
                this.clearExamCountdown();
                this.expiresAt = expiresAt;

                if (!expiresAt) {
                    return;
                }

                const expiresAtTimestamp = new Date(expiresAt).getTime();

                if (Number.isNaN(expiresAtTimestamp)) {
                    return;
                }

                const updateCountdown = () => {
                    this.timeRemaining = Math.max(0, Math.ceil((expiresAtTimestamp - Date.now()) / 1000));
                };

                updateCountdown();
                this.countdownInterval = window.setInterval(updateCountdown, 250);
            },

            clearExamCountdown() {
                if (this.countdownInterval !== null) {
                    window.clearInterval(this.countdownInterval);
                    this.countdownInterval = null;
                }

                this.timeRemaining = null;
            },

            formatDuration(seconds) {
                const totalSeconds = Math.max(0, Number(seconds) || 0);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const remainingSeconds = totalSeconds % 60;

                return [hours, minutes, remainingSeconds]
                    .map((value) => String(value).padStart(2, '0'))
                    .join(':');
            },

            handleClipboardEvent(event, eventType) {
                this.report(eventType);

                if (this.disableCopyPaste) {
                    event.preventDefault();
                }
            },

            async beginAttempt() {
                if (!await this.enterFullscreenIfRequired()) {
                    return;
                }

                await this.$wire.startAttempt();
            },

            async resumeExam() {
                if (!await this.enterFullscreenIfRequired()) {
                    return;
                }

                await this.$wire.resumeAttempt();
            },

            async enterFullscreenIfRequired() {
                if (!this.requireFullscreen || document.fullscreenElement) {
                    return true;
                }

                if (!this.supportsFullscreen()) {
                    this.fullscreenUnsupported = true;

                    return false;
                }

                try {
                    await document.documentElement.requestFullscreen();
                } catch (error) {
                    this.fullscreenUnsupported = true;

                    return false;
                }

                await this.$wire.$set('fullscreenConfirmed', Boolean(document.fullscreenElement));

                return Boolean(document.fullscreenElement);
            },

            supportsFullscreen() {
                return document.fullscreenEnabled !== false
                    && typeof document.documentElement.requestFullscreen === 'function';
            },

            async returnToFullscreen() {
                await this.enterFullscreenIfRequired();

                if (document.fullscreenElement) {
                    this.clearFullscreenExitCountdown();
                    await this.$wire.dismissFullscreenExitWarning();
                }
            },

            startFullscreenExitCountdown() {
                this.clearFullscreenExitCountdown();
                this.secondsUntilFullscreenSubmission = 15;
                this.fullscreenExitInterval = window.setInterval(() => {
                    this.secondsUntilFullscreenSubmission = Math.max(this.secondsUntilFullscreenSubmission - 1, 0);
                }, 1000);
                this.fullscreenExitTimeout = window.setTimeout(() => {
                    this.clearFullscreenExitCountdown();
                    this.$wire.submitForFullscreenExit();
                }, 15000);
            },

            clearFullscreenExitCountdown() {
                if (this.fullscreenExitTimeout !== null) {
                    window.clearTimeout(this.fullscreenExitTimeout);
                    this.fullscreenExitTimeout = null;
                }

                if (this.fullscreenExitInterval !== null) {
                    window.clearInterval(this.fullscreenExitInterval);
                    this.fullscreenExitInterval = null;
                }

                this.secondsUntilFullscreenSubmission = 0;
            },

            report(eventType, bypassCooldown = false) {
                if (!bypassCooldown && Date.now() - this.lastReportedAt < 10000) {
                    return;
                }

                this.lastReportedAt = Date.now();
                return this.$wire.logClientEvent(eventType).catch(() => {});
            },
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('exam-attempt-started', ({ attemptId, expiresAt }) => {
                const url = new URL(window.location.href);
                url.searchParams.set('attempt', attemptId);
                window.history.replaceState({}, '', url);
                window.dispatchEvent(new CustomEvent('exam-attempt-started', {
                    detail: { attemptId, expiresAt },
                }));
            });

            Livewire.interceptRequest(({ onError }) => {
                onError(({ response, preventDefault }) => {
                    if (response.status !== 419) {
                        return;
                    }

                    preventDefault();
                    window.location.reload();
                });
            });
        });
    </script>
</body>
</html>
