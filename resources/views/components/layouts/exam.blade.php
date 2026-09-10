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
            attemptActive: options.attemptActive ?? false,
            attemptId: options.attemptId ?? null,
            autosaveIntervalSeconds: options.autosaveIntervalSeconds ?? 15,
            questionExpiresAt: options.questionExpiresAt ?? null,
            questionTimerEnabled: options.questionTimerEnabled ?? false,
            fullscreenUnsupported: false,
            reportedAt: {},
            countdownInterval: null,
            questionCountdownInterval: null,
            draftAutosaveInterval: null,
            fullscreenExitTimeout: null,
            fullscreenExitInterval: null,
            secondsUntilFullscreenSubmission: 0,
            timeRemaining: null,
            questionTimeRemaining: null,
            draftStatus: 'Saving answers securely',
            navigationPending: false,
            draftSyncPending: false,
            examTimerSubmissionPending: false,
            networkStatus: 'Checking connection',
            networkStrength: 'Checking',
            networkDetail: '',
            networkConnection: null,
            examUrl: window.location.href,
            historyGuardEnabled: false,
            isLeavingExam: false,
            questionTimerSubmissionPending: false,

            init() {
                this.setExamCountdown(this.expiresAt);
                this.syncQuestionTimer(this.questionExpiresAt, this.questionTimerEnabled, this.attemptActive);
                this.fullscreenUnsupported = this.requireFullscreen && !this.supportsFullscreen();
                this.networkConnection = navigator.connection ?? navigator.mozConnection ?? navigator.webkitConnection ?? null;
                this.onNetworkChange = () => {
                    this.updateNetworkStatus();

                    if (navigator.onLine) {
                        this.syncDraft();
                    }
                };
                this.updateNetworkStatus();

                this.onVisibilityChange = () => {
                    if (document.hidden) {
                        this.report('tab_hidden');
                        this.triggerUnsupportedFullscreenDeterrent();
                    }
                };

                this.onWindowBlur = () => {
                    window.setTimeout(() => {
                        if (!document.hasFocus()) {
                            this.report('window_blur');
                            this.triggerUnsupportedFullscreenDeterrent();
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
                    this.attemptActive = true;
                    this.attemptId = detail.attemptId ?? this.attemptId;
                    this.setExamCountdown(detail.expiresAt ?? null);
                    this.enableHistoryGuard();
                    this.restoreDraft();
                    this.startDraftAutosave();
                };
                this.onPopState = () => {
                    if (!this.historyGuardEnabled || this.isLeavingExam) {
                        return;
                    }

                    window.history.pushState({ examNavigationGuard: true }, '', this.examUrl);
                    this.$wire.warnBeforeLeaving();
                };

                document.addEventListener('visibilitychange', this.onVisibilityChange);
                window.addEventListener('blur', this.onWindowBlur);
                document.addEventListener('copy', this.onCopy);
                document.addEventListener('paste', this.onPaste);
                document.addEventListener('fullscreenchange', this.onFullscreenChange);
                window.addEventListener('exam-attempt-started', this.onExamAttemptStarted);
                window.addEventListener('popstate', this.onPopState);
                window.addEventListener('online', this.onNetworkChange);
                window.addEventListener('offline', this.onNetworkChange);
                this.networkConnection?.addEventListener('change', this.onNetworkChange);

                if (this.attemptActive) {
                    this.enableHistoryGuard();
                    this.restoreDraft();
                    this.startDraftAutosave();
                }
            },

            destroy() {
                document.removeEventListener('visibilitychange', this.onVisibilityChange);
                window.removeEventListener('blur', this.onWindowBlur);
                document.removeEventListener('copy', this.onCopy);
                document.removeEventListener('paste', this.onPaste);
                document.removeEventListener('fullscreenchange', this.onFullscreenChange);
                window.removeEventListener('exam-attempt-started', this.onExamAttemptStarted);
                window.removeEventListener('popstate', this.onPopState);
                window.removeEventListener('online', this.onNetworkChange);
                window.removeEventListener('offline', this.onNetworkChange);
                this.networkConnection?.removeEventListener('change', this.onNetworkChange);
                this.clearExamCountdown();
                this.clearQuestionCountdown();
                this.clearDraftAutosave();
                this.clearFullscreenExitCountdown();
            },

            updateNetworkStatus() {
                if (!navigator.onLine) {
                    this.networkStatus = 'Offline';
                    this.networkStrength = 'Unavailable';
                    this.networkDetail = 'Reconnect to continue saving answers.';

                    return;
                }

                this.networkStatus = 'Online';

                if (!this.networkConnection) {
                    this.networkStrength = 'Unavailable';
                    this.networkDetail = 'Connection strength is not available in this browser.';

                    return;
                }

                const effectiveType = this.networkConnection.effectiveType;
                const downlink = Number(this.networkConnection.downlink);

                if (['slow-2g', '2g'].includes(effectiveType) || (Number.isFinite(downlink) && downlink < 1)) {
                    this.networkStrength = 'Weak';
                } else if (effectiveType === '3g' || (Number.isFinite(downlink) && downlink < 5)) {
                    this.networkStrength = 'Moderate';
                } else {
                    this.networkStrength = 'Strong';
                }

                const connectionType = this.networkConnection.type ?? effectiveType?.toUpperCase() ?? 'network';
                const estimatedSpeed = Number.isFinite(downlink) ? `Estimated ${downlink} Mbps` : 'Speed estimate unavailable';
                this.networkDetail = `${connectionType} connection. ${estimatedSpeed}.`;
            },

            enableHistoryGuard() {
                if (this.historyGuardEnabled) {
                    return;
                }

                window.history.replaceState({ ...window.history.state, examNavigationGuard: true }, '', this.examUrl);
                window.history.pushState({ examNavigationGuard: true }, '', this.examUrl);
                this.historyGuardEnabled = true;
            },

            disableHistoryGuard() {
                this.attemptActive = false;
                this.historyGuardEnabled = false;
            },

            leaveExam() {
                this.isLeavingExam = true;
                this.historyGuardEnabled = false;
                window.history.go(-2);
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

                    if (this.timeRemaining === 0 && this.attemptActive && !this.examTimerSubmissionPending) {
                        this.examTimerSubmissionPending = true;
                        this.$wire.syncAndSubmitForTimeLimit(this.collectDraft()).catch(() => {
                            this.draftStatus = 'Saved on this device. Reconnect to finish submitting.';
                            this.examTimerSubmissionPending = false;
                        });
                    }
                };

                updateCountdown();
                this.countdownInterval = window.setInterval(updateCountdown, 250);
            },

            syncQuestionTimer(questionExpiresAt, questionTimerEnabled, attemptActive) {
                this.questionExpiresAt = questionExpiresAt;
                this.questionTimerEnabled = questionTimerEnabled;
                this.attemptActive = attemptActive;

                if (!this.questionTimerEnabled || !this.attemptActive) {
                    this.clearQuestionCountdown();

                    return;
                }

                this.setQuestionCountdown(questionExpiresAt);
            },

            clearExamCountdown() {
                if (this.countdownInterval !== null) {
                    window.clearInterval(this.countdownInterval);
                    this.countdownInterval = null;
                }

                this.timeRemaining = null;
            },

            setQuestionCountdown(expiresAt) {
                this.clearQuestionCountdown();

                if (!expiresAt) {
                    return;
                }

                const expiresAtTimestamp = new Date(expiresAt).getTime();

                if (Number.isNaN(expiresAtTimestamp)) {
                    return;
                }

                const updateCountdown = async () => {
                    this.questionTimeRemaining = Math.max(0, Math.ceil((expiresAtTimestamp - Date.now()) / 1000));

                    if (this.questionTimeRemaining > 0 || this.questionTimerSubmissionPending) {
                        return;
                    }

                    this.questionTimerSubmissionPending = true;

                    try {
                        await this.$wire.syncAndHandleQuestionTimerExpired(this.collectDraft());
                    } finally {
                        this.questionTimerSubmissionPending = false;
                    }
                };

                updateCountdown();
                this.questionCountdownInterval = window.setInterval(updateCountdown, 250);
            },

            clearQuestionCountdown() {
                if (this.questionCountdownInterval !== null) {
                    window.clearInterval(this.questionCountdownInterval);
                    this.questionCountdownInterval = null;
                }

                this.questionTimeRemaining = null;
                this.questionTimerSubmissionPending = false;
            },

            draftStorageKey() {
                return this.attemptId ? `examup:attempt:${this.attemptId}:responses` : null;
            },

            readDraft() {
                const key = this.draftStorageKey();

                if (!key) {
                    return {};
                }

                try {
                    const draft = JSON.parse(window.localStorage.getItem(key) ?? '{}');

                    return draft && typeof draft === 'object' && !Array.isArray(draft) ? draft : {};
                } catch (_) {
                    return {};
                }
            },

            saveDraft() {
                const key = this.draftStorageKey();

                if (!key) {
                    return {};
                }

                const draft = this.readDraft();

                this.$root.querySelectorAll('[data-exam-response]').forEach((input) => {
                    const questionId = input.dataset.questionId;
                    const responseType = input.dataset.responseType;

                    if (!questionId || !responseType) {
                        return;
                    }

                    draft[questionId] ??= {};

                    if (responseType === 'multiple') {
                        draft[questionId].selected_options ??= {};
                        draft[questionId].selected_options[input.dataset.optionId] = input.checked;
                    } else if (responseType === 'single' && input.checked) {
                        draft[questionId].selected_option_id = input.value;
                    } else if (responseType === 'text') {
                        draft[questionId].answer_text = input.value;
                    }
                });

                try {
                    window.localStorage.setItem(key, JSON.stringify(draft));
                    this.draftStatus = navigator.onLine ? 'Answers saved on this device' : 'Saved on this device. Reconnect to sync.';
                } catch (_) {
                    this.draftStatus = 'Your browser could not save a local backup.';
                }

                return draft;
            },

            collectDraft() {
                return this.saveDraft();
            },

            restoreDraft() {
                const draft = this.readDraft();

                if (Object.keys(draft).length === 0) {
                    return;
                }

                this.$root.querySelectorAll('[data-exam-response]').forEach((input) => {
                    const response = draft[input.dataset.questionId] ?? {};

                    if (input.dataset.responseType === 'multiple') {
                        input.checked = Boolean(response.selected_options?.[input.dataset.optionId]);
                    } else if (input.dataset.responseType === 'single') {
                        input.checked = response.selected_option_id === input.value;
                    } else if (input.dataset.responseType === 'text') {
                        input.value = response.answer_text ?? '';
                    }
                });

                this.draftStatus = 'Restored answers saved on this device';
                this.syncDraft();
            },

            clearDraft() {
                const key = this.draftStorageKey();

                if (key) {
                    window.localStorage.removeItem(key);
                }
            },

            startDraftAutosave() {
                this.clearDraftAutosave();

                if (!this.attemptActive) {
                    return;
                }

                this.draftAutosaveInterval = window.setInterval(() => this.syncDraft(), Math.max(Number(this.autosaveIntervalSeconds) || 15, 5) * 1000);
            },

            clearDraftAutosave() {
                if (this.draftAutosaveInterval !== null) {
                    window.clearInterval(this.draftAutosaveInterval);
                    this.draftAutosaveInterval = null;
                }
            },

            async syncDraft() {
                const responses = this.collectDraft();

                if (!this.attemptActive || !navigator.onLine || this.draftSyncPending || Object.keys(responses).length === 0) {
                    return false;
                }

                this.draftSyncPending = true;

                try {
                    await this.$wire.syncResponses(responses);
                    this.draftStatus = 'Answers synchronized';

                    return true;
                } catch (_) {
                    this.draftStatus = 'Saved on this device. Retrying when connected.';

                    return false;
                } finally {
                    this.draftSyncPending = false;
                }
            },

            async nextQuestion() {
                await this.navigateWithDraft('syncAndNext');
            },

            async previousQuestion() {
                await this.navigateWithDraft('syncAndPrevious');
            },

            async requestSubmission() {
                await this.navigateWithDraft('syncAndRequestSubmission');
            },

            async submitExam() {
                await this.navigateWithDraft('syncAndSubmit');
            },

            async navigateWithDraft(method) {
                const responses = this.collectDraft();

                if (!navigator.onLine) {
                    this.draftStatus = 'Saved on this device. Reconnect before continuing.';

                    return;
                }

                this.navigationPending = true;

                try {
                    await this.$wire[method](responses);
                    this.draftStatus = 'Answers synchronized';
                } catch (_) {
                    this.draftStatus = 'Saved on this device. Retrying when connected.';
                } finally {
                    this.navigationPending = false;
                }
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
                const clipboard = event.clipboardData;
                let clipboardText = clipboard?.getData('text/plain') ?? '';

                if (eventType === 'copy' && !clipboardText) {
                    clipboardText = this.selectedText(event.target);
                }

                this.report(
                    eventType,
                    false,
                    clipboardText.slice(0, 1000),
                    Array.from(clipboard?.types ?? []).slice(0, 10),
                );

                if (this.disableCopyPaste) {
                    event.preventDefault();
                }
            },

            selectedText(target) {
                if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) {
                    return target.value.slice(target.selectionStart ?? 0, target.selectionEnd ?? 0);
                }

                return window.getSelection()?.toString() ?? '';
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
                    await this.$wire.$set('fullscreenUnsupported', true);

                    return true;
                }

                try {
                    await document.documentElement.requestFullscreen();
                } catch (error) {
                    this.fullscreenUnsupported = true;
                    await this.$wire.$set('fullscreenUnsupported', true);

                    return true;
                }

                await this.$wire.$set('fullscreenConfirmed', Boolean(document.fullscreenElement));

                return Boolean(document.fullscreenElement);
            },

            supportsFullscreen() {
                return document.fullscreenEnabled !== false
                    && typeof document.documentElement.requestFullscreen === 'function';
            },

            triggerUnsupportedFullscreenDeterrent() {
                if (!this.requireFullscreen || !this.fullscreenUnsupported || !this.attemptActive) {
                    return;
                }

                this.$wire.triggerFullscreenFallbackWarning();
                this.startFullscreenExitCountdown();
            },

            async returnToFullscreen() {
                await this.enterFullscreenIfRequired();

                if (document.fullscreenElement) {
                    this.clearFullscreenExitCountdown();
                    await this.$wire.dismissFullscreenExitWarning();
                }
            },

            startFullscreenExitCountdown() {
                if (this.fullscreenExitTimeout !== null) {
                    return;
                }

                this.clearFullscreenExitCountdown();
                this.secondsUntilFullscreenSubmission = 15;
                this.fullscreenExitInterval = window.setInterval(() => {
                    this.secondsUntilFullscreenSubmission = Math.max(this.secondsUntilFullscreenSubmission - 1, 0);
                }, 1000);
                this.fullscreenExitTimeout = window.setTimeout(() => {
                    this.clearFullscreenExitCountdown();
                    this.$wire.syncAndSubmitForFullscreenExit(this.collectDraft());
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

            report(eventType, bypassCooldown = false, clipboardText = null, clipboardTypes = []) {
                const lastReportedAt = this.reportedAt[eventType] ?? 0;

                if (!bypassCooldown && Date.now() - lastReportedAt < 10000) {
                    return;
                }

                this.reportedAt[eventType] = Date.now();
                return this.$wire.logClientEvent(eventType, clipboardText, clipboardTypes).catch(() => {});
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
