const dialogueRoot = document.querySelector('[data-scenario-dialogue]');

if (dialogueRoot) {
    const storageKey = dialogueRoot.dataset.storageKey || 'scenario-text-dialogue-v1';
    const expectedScene = dialogueRoot.dataset.scene;
    const scenarioSlug = dialogueRoot.dataset.scenarioSlug;
    const npcName = dialogueRoot.dataset.npcName || 'Je gesprekspartner';
    const sensitiveRoleplay = dialogueRoot.dataset.sensitiveRoleplay === 'true';
    const elements = {
        stage: dialogueRoot.querySelector('[data-dialogue-stage]'),
        levelSelect: dialogueRoot.querySelector('[data-level-select]'),
        complete: dialogueRoot.querySelector('[data-dialogue-complete]'),
        error: dialogueRoot.querySelector('[data-dialogue-error]'),
        status: dialogueRoot.querySelector('[data-dialogue-status]'),
        form: dialogueRoot.querySelector('[data-dialogue-form]'),
        response: dialogueRoot.querySelector('[data-player-response]'),
        submitButton: dialogueRoot.querySelector('[data-dialogue-form] button[type="submit"]'),
        feedback: dialogueRoot.querySelector('[data-feedback]'),
        feedbackDetails: dialogueRoot.querySelector('[data-feedback-details]'),
        feedbackRetry: dialogueRoot.querySelector('[data-feedback-retry]'),
        continueButton: dialogueRoot.querySelector('[data-dialogue-continue]'),
        history: dialogueRoot.querySelector('[data-dialogue-history]'),
        accountSyncTitle: dialogueRoot.querySelector('[data-account-sync-title]'),
        accountSyncMessage: dialogueRoot.querySelector('[data-account-sync-message]'),
        accountBalances: dialogueRoot.querySelector('[data-account-balances]'),
        accountSyncRetry: dialogueRoot.querySelector('[data-account-sync-retry]'),
        npcFrame: dialogueRoot.querySelector('[data-npc-state], [data-lucia-state]'),
        npcSheets: dialogueRoot.querySelectorAll('[data-npc-expression-sheet], [data-npc-expression-sheet-complete], [data-lucia-expression-sheet], [data-lucia-expression-sheet-complete]'),
    };
    let content;
    let state = emptyState();
    let pendingNext = null;
    let stepAssisted = false;
    let speechConfidenceStatus = null;
    let originalSpeechTranscript = null;
    let translationVisible = false;
    let pendingStateBeforeTurn = null;
    let isEvaluating = false;
    const persist = () => persistState(storageKey, state, sensitiveRoleplay);

    const showPreparationSummary = () => {
        const summary = dialogueRoot.querySelector('[data-preparation-summary]');
        if (!summary || !dialogueRoot.matches('[data-panaderia-dialogue]')) return;

        try {
            const preparation = JSON.parse(window.sessionStorage.getItem('madrid-mission-preparation') ?? 'null');
            if (!preparation?.bread || !preparation?.sweet) return;

            setText('[data-preparation-bread]', preparation.bread);
            setText('[data-preparation-sweet]', preparation.sweet);
            summary.hidden = false;
        } catch {
            // De dialoog blijft zonder boodschappenkaart volledig speelbaar.
        }
    };

    const setText = (selector, value) => {
        const element = dialogueRoot.querySelector(selector);
        if (element) element.textContent = value ?? '';
    };

    showPreparationSummary();

    const validateContent = (data) => {
        if (data?.schema_version !== '1.0.0' || data?.scene !== expectedScene) {
            throw new Error('De conversatie gebruikt niet het verwachte missiedagcontract v1.');
        }

        if (!Array.isArray(data.steps) || data.steps.length < 7) {
            throw new Error('De conversatie bevat onvoldoende dialoogstappen.');
        }

        const stepIds = new Set(data.steps.map(({ id }) => id));
        if (stepIds.size !== data.steps.length || !stepIds.has(data.mission?.start_step)) {
            throw new Error('De conversatie bevat dubbele of ontbrekende stap-id’s.');
        }

        if (!['A0', 'A1', 'A2'].every((level) => stepIds.has(data.level_branches?.[level]))) {
            throw new Error('Niet ieder niveau heeft een geldige complicatievariant.');
        }

        if (sensitiveRoleplay
            && (data.roleplay?.fictional !== true
                || !Array.isArray(data.roleplay?.facts)
                || data.roleplay.facts.length < 4)) {
            throw new Error('De gevoelige gesprekssituatie mist een geldige fictieve rolkaart.');
        }

        const nextTargetsAreValid = data.steps.every((step) => step.options.every(({ next }) =>
            ['@complication', '@complete'].includes(next) || stepIds.has(next)));
        if (!nextTargetsAreValid) {
            throw new Error('Een dialoogoptie verwijst naar een onbekende vervolgstap.');
        }

        return data;
    };

    const hydrateContent = (data) => {
        setText('[data-mission-title]', data.mission.title.nl);
        setText('[data-mission-objective]', data.mission.objective);
        setText('[data-progress-total]', String(data.mission.required_text_turns));
        setText('[data-npc-name]', data.npc.name);
        setText('[data-npc-role-es]', data.npc.role.es);
        setText('[data-npc-role-nl]', data.npc.role.nl);
        hydrateRoleplay(data.roleplay);
        hydrateJourney(data.journey);
        hydrateMemory(data.memory);
    };

    const applyRuntimeMedia = (media = {}) => {
        const scene = media.scene_background;
        const expressions = media.npc_expression_sheet ?? media.npc_portrait;

        if (scene?.kind === 'image' && typeof scene.url === 'string') {
            dialogueRoot.style.setProperty('--bakery-scene-image', `url("${scene.url}")`);
        }

        if (expressions?.kind === 'image' && typeof expressions.url === 'string') {
            elements.npcSheets.forEach((image) => {
                image.src = expressions.url;
            });
        }
    };

    const setNpcState = (state) => {
        if (!elements.npcFrame) return;

        const labels = {
            listening: `${npcName} luistert`,
            encouraging: `${npcName} moedigt je aan`,
            success: `${npcName} viert je resultaat`,
        };
        elements.npcFrame.dataset.npcState = state;
        elements.npcFrame.dataset.luciaState = state;
        setText('[data-npc-reaction], [data-lucia-reaction]', labels[state] ?? labels.listening);
    };

    const hydrateRoleplay = (roleplay) => {
        const card = dialogueRoot.querySelector('[data-roleplay-card]');
        if (!card) return;

        if (roleplay?.fictional !== true || !Array.isArray(roleplay.facts)) {
            card.hidden = true;
            return;
        }

        setText('[data-roleplay-title]', roleplay.title?.nl);
        setText('[data-roleplay-description]', roleplay.description);
        setText('[data-roleplay-privacy]', roleplay.privacy_notice);
        setText('[data-roleplay-disclaimer]', roleplay.medical_disclaimer);
        const list = card.querySelector('[data-roleplay-facts]');
        list.replaceChildren(...roleplay.facts.map((fact) => {
            const item = document.createElement('li');
            const spanish = document.createElement('strong');
            const dutch = document.createElement('span');
            spanish.lang = 'es';
            spanish.textContent = fact.es;
            dutch.textContent = fact.nl;
            item.append(spanish, dutch);

            return item;
        }));
        card.hidden = false;
    };

    const hydrateJourney = (journey) => {
        const card = dialogueRoot.querySelector('[data-journey-card]');
        if (!card) return;

        if (journey?.fictional !== true || !Array.isArray(journey.details) || journey.details.length < 3) {
            card.hidden = true;
            return;
        }

        setText('[data-journey-title]', journey.title?.nl);
        setText('[data-journey-notice]', journey.notice);
        const details = card.querySelector('[data-journey-details]');
        details.replaceChildren(...journey.details.map((detail) => {
            const row = document.createElement('div');
            const label = document.createElement('dt');
            const value = document.createElement('dd');
            const spanish = document.createElement('strong');
            const dutch = document.createElement('span');
            label.textContent = detail.label?.nl ?? '';
            spanish.lang = 'es';
            spanish.textContent = detail.value?.es ?? '';
            dutch.textContent = detail.value?.nl ?? '';
            value.append(spanish, dutch);
            row.append(label, value);
            return row;
        }));
        card.hidden = false;
    };

    const hydrateMemory = (memory) => {
        const card = dialogueRoot.querySelector('[data-npc-memory]');
        if (!card) return;

        const returning = dialogueRoot.dataset.memoryReturning === 'true';
        const greeting = returning ? memory?.returning_greeting : memory?.first_greeting;
        if (typeof greeting?.es === 'string') setText('[data-memory-greeting-es]', greeting.es);
        if (typeof greeting?.nl === 'string') setText('[data-memory-greeting-nl]', greeting.nl);
        if (typeof memory?.privacy_notice === 'string') setText('[data-memory-privacy]', memory.privacy_notice);
    };

    const startLevel = (level) => {
        state = emptyState();
        state.level = level;
        state.currentStep = content.mission.start_step;
        setText('[data-level-chip]', level);
        elements.levelSelect.hidden = true;
        elements.stage.hidden = false;
        renderStep();
        persist();
        elements.status.textContent = `Niveau ${level} gekozen. ${npcName} begroet je; neem rustig de tijd.`;
    };

    const renderStep = () => {
        const step = findStep(state.currentStep);
        if (!step) throw new Error('De opgeslagen dialoogstap bestaat niet meer.');

        pendingNext = null;
        pendingStateBeforeTurn = null;
        stepAssisted = false;
        speechConfidenceStatus = null;
        originalSpeechTranscript = null;
        elements.feedback.hidden = true;
        elements.feedback.removeAttribute('aria-busy');
        elements.feedbackDetails.hidden = true;
        elements.feedbackRetry.hidden = true;
        elements.continueButton.hidden = true;
        elements.form.hidden = false;
        elements.response.disabled = false;
        elements.response.value = '';
        elements.response.dataset.responseSource = 'typed_assist';
        elements.response.placeholder = step.placeholder;
        elements.response.focus({ preventScroll: true });
        setText('[data-step-prompt]', step.prompt);
        setText('[data-step-hint]', step.hint);
        dialogueRoot.querySelector('[data-step-hint]').hidden = true;
        dialogueRoot.querySelector('[data-hint-toggle]').setAttribute('aria-expanded', 'false');
        setText('[data-turn-label]', `Beurt ${step.turn}`);
        setNpcLine(step.npc_line);
        renderChoices(step.choices);
        updateProgress();
        renderHistory();
        document.dispatchEvent(new CustomEvent('scenario:turn-changed'));
        setNpcState('listening');
    };

    const renderChoices = (choices) => {
        const list = dialogueRoot.querySelector('[data-choice-list]');
        list.replaceChildren(...choices.map((answer) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.lang = 'es';
            button.textContent = answer;
            button.addEventListener('click', () => {
                elements.response.value = answer;
                elements.response.dataset.responseSource = 'choice_assist';
                stepAssisted = true;
                elements.response.focus();
                elements.status.textContent = 'Voorbeeldzin gekozen. Je mag de zin nog aanpassen.';
            });
            return button;
        }));
    };

    const submitResponse = async (event) => {
        event.preventDefault();
        if (isEvaluating) return;

        const step = findStep(state.currentStep);
        const answer = elements.response.value.trim();
        const normalized = normalize(answer);
        const responseSource = elements.response.dataset.responseSource || 'typed_assist';
        if (!normalized) return;

        if (content.repair.terms.some((term) => normalized.includes(normalize(term)))) {
            pendingStateBeforeTurn = cloneState(state);
            state.states = unique([...state.states, 'used_repair_strategy']);
            state.history.push({
                stepId: step.id,
                turn: step.turn,
                player: answer,
                npc: content.repair.npc_response.es,
                repair: true,
                source: responseSource,
                assisted: stepAssisted || responseSource === 'choice_assist',
                confidenceStatus: responseSource === 'speech' ? speechConfidenceStatus : null,
                transcriptCorrected: responseSource === 'speech' && answer !== originalSpeechTranscript,
            });
            await showSuccessfulResponse(
                content.repair.npc_response,
                content.repair.feedback,
                state.currentStep,
                step,
                answer,
                responseSource,
            );
            return;
        }

        const option = step.options.find(({ requirements }) => requirements.every((group) =>
            group.some((term) => normalized.includes(normalize(term)))));

        if (!option) {
            showFeedback(step.fallback, false);
            setNpcState('encouraging');
            elements.status.textContent = `${npcName} heeft nog niet genoeg informatie. Bekijk de gerichte tip en probeer opnieuw.`;
            return;
        }

        pendingStateBeforeTurn = cloneState(state);
        const politeness = ['por favor', 'gracias'].some((term) => normalized.includes(term));
        state.states = unique([...state.states, ...option.states, ...(politeness ? ['used_politeness'] : [])]);
        state.history.push({
            stepId: step.id,
            turn: step.turn,
            player: answer,
            npc: option.npc_response.es,
            repair: false,
            source: responseSource,
            assisted: stepAssisted || responseSource === 'choice_assist',
            confidenceStatus: responseSource === 'speech' ? speechConfidenceStatus : null,
            transcriptCorrected: responseSource === 'speech' && answer !== originalSpeechTranscript,
        });
        state.completedTurns += 1;
        if (responseSource === 'speech') state.spokenTurns += 1;
        if (stepAssisted) state.assistCount += 1;
        await showSuccessfulResponse(
            option.npc_response,
            option.feedback,
            resolveNext(option.next),
            step,
            answer,
            responseSource,
        );
    };

    const showSuccessfulResponse = async (npcResponse, authoredFeedback, next, step, answer, responseSource) => {
        pendingNext = next;
        setNpcLine(npcResponse);
        elements.form.hidden = true;
        showFeedbackLoading();
        updateProgress();
        renderHistory();
        setNpcState('encouraging');

        isEvaluating = true;
        elements.submitButton.disabled = true;
        let personalized = false;

        try {
            const feedback = await requestLayeredFeedback(step, answer, responseSource);
            showLayeredFeedback(feedback);
            const historyEntry = state.history.at(-1);
            if (historyEntry) {
                historyEntry.feedback = {
                    assessorVersion: feedback.assessor_version,
                    feedbackVersion: feedback.feedback_version,
                    overall: feedback.overall,
                };
            }
            personalized = true;
        } catch {
            showFeedback(authoredFeedback, true);
            setText('[data-feedback-note]', 'Persoonlijke feedback was niet beschikbaar. Je voortgang blijft veilig en de inhoudelijke feedback uit de Content Studio wordt getoond.');
        } finally {
            isEvaluating = false;
            elements.submitButton.disabled = false;
            elements.feedback.removeAttribute('aria-busy');
            elements.feedbackRetry.hidden = false;
            elements.continueButton.hidden = false;
            elements.continueButton.focus({ preventScroll: true });
        }

        elements.status.textContent = next === state.currentStep
            ? 'Herstelstrategie herkend. Dat is taalvaardigheid, geen fout. Je kunt doorgaan of dezelfde beurt opnieuw proberen.'
            : `Beurt ${step.turn} voltooid. ${personalized ? 'Bekijk je persoonlijke feedback' : 'Bekijk de veilige terugvalfeedback'} en ga verder of probeer opnieuw.`;
    };

    const showFeedback = (feedback, success) => {
        elements.feedback.hidden = false;
        elements.feedback.dataset.result = success ? 'success' : 'retry';
        elements.feedback.querySelector('.bakery-feedback-icon').textContent = success ? '✓' : '↻';
        setText('[data-feedback-strength]', feedback.strength);
        setText('[data-feedback-focus]', feedback.focus);
        setText('[data-feedback-example]', '');
        setText('[data-feedback-note]', '');
        elements.feedbackDetails.hidden = true;
        elements.feedbackRetry.hidden = true;
    };

    const showFeedbackLoading = () => {
        elements.feedback.hidden = false;
        elements.feedback.dataset.result = 'loading';
        elements.feedback.setAttribute('aria-busy', 'true');
        elements.feedback.querySelector('.bakery-feedback-icon').textContent = '…';
        setText('[data-feedback-strength]', 'Je antwoord wordt bekeken…');
        setText('[data-feedback-focus]', 'Eerst kijken we of je bedoeling duidelijk was; daarna volgt maximaal één concrete volgende stap.');
        setText('[data-feedback-example]', '');
        setText('[data-feedback-note]', sensitiveRoleplay
            ? 'Alleen je Spaanse taalgebruik wordt beoordeeld — niet de fictieve medische inhoud. Uitspraak wordt zonder audio-evidence niet beoordeeld.'
            : 'Uitspraak wordt niet beoordeeld, omdat alleen je transcript naar deze feedbacklaag gaat.');
        elements.feedbackDetails.hidden = true;
        elements.feedbackRetry.hidden = true;
        elements.continueButton.hidden = true;
    };

    const showLayeredFeedback = (feedback) => {
        elements.feedback.hidden = false;
        elements.feedback.dataset.result = 'success';
        elements.feedback.querySelector('.bakery-feedback-icon').textContent = '✓';
        setText('[data-feedback-strength]', feedback.summary.strength);
        setText('[data-feedback-focus]', feedback.summary.focus.message);
        setText('[data-feedback-example]', feedback.summary.focus.example_es
            ? `Probeer bijvoorbeeld: ${feedback.summary.focus.example_es}`
            : 'Probeer dezelfde bedoeling nog één keer in je eigen woorden.');
        setText('[data-feedback-note]', feedback.summary.retry_recommended
            ? 'Een herkansing is aanbevolen, maar je voortgang gaat nooit verloren.'
            : 'Je mag deze beurt vrijwillig opnieuw proberen; je voortgang gaat nooit verloren.');

        const labels = {
            task_execution: 'Taakuitvoering',
            comprehensibility: 'Begrijpelijkheid',
            vocabulary: 'Woordkeuze',
            grammar: 'Grammatica',
            pronunciation: 'Uitspraak',
            conversation_strategy: 'Gespreksstrategie',
        };
        const list = dialogueRoot.querySelector('[data-feedback-rubric]');
        const rows = Object.entries(labels).map(([dimension, label]) => {
            const item = document.createElement('li');
            const name = document.createElement('span');
            const score = document.createElement('strong');
            const result = feedback.rubric[dimension];
            name.textContent = label;
            score.textContent = result.status === 'not_assessed' ? 'Niet beoordeeld' : `${result.score}/4`;
            item.append(name, score);
            if (result.reason) {
                const reason = document.createElement('small');
                reason.textContent = result.reason;
                item.append(reason);
            }
            return item;
        });
        list.replaceChildren(...rows);
        setText('[data-feedback-overall]', `${feedback.overall.score}/4 · uitspraak niet meegerekend`);
        elements.feedbackDetails.hidden = false;
    };

    const requestLayeredFeedback = async (step, answer, responseSource) => {
        const response = await fetch(dialogueRoot.dataset.assessmentUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                scenario_slug: scenarioSlug,
                step_id: step.id,
                answer,
                level: state.level,
                source: responseSource,
                transcript_confidence_status: responseSource === 'speech' ? speechConfidenceStatus : null,
                transcript_corrected: responseSource === 'speech' && answer !== originalSpeechTranscript,
            }),
        });

        if (!response.ok) throw new Error(`Feedback niet beschikbaar (${response.status}).`);

        const payload = await response.json();
        if (payload?.schema_version !== '1.0.0'
            || payload?.meta?.progress_affecting !== false
            || payload?.meta?.audio_assessed !== false
            || payload?.data?.rubric?.pronunciation?.status !== 'not_assessed'
            || !payload?.data?.summary?.focus?.dimension) {
            throw new Error('Het feedbackcontract is niet veilig te verwerken.');
        }

        return payload.data;
    };

    const continueDialogue = () => {
        pendingStateBeforeTurn = null;
        if (pendingNext === '@complete') {
            finishDialogue();
            return;
        }

        state.currentStep = pendingNext;
        persist();
        renderStep();
    };

    const retrySuccessfulTurn = () => {
        if (!pendingStateBeforeTurn || isEvaluating) return;

        state = cloneState(pendingStateBeforeTurn);
        pendingStateBeforeTurn = null;
        pendingNext = null;
        persist();
        renderStep();
        elements.status.textContent = 'Je voortgang is veilig teruggezet naar het begin van deze beurt. Probeer het in je eigen woorden.';
    };

    const finishDialogue = () => {
        state.completed = true;
        state.currentStep = null;
        state.completionKey ||= createCompletionKey();
        elements.stage.hidden = true;
        elements.complete.hidden = false;
        setNpcState('success');
        document.dispatchEvent(new CustomEvent('scenario:turn-changed'));
        const farewell = state.states.includes('used_politeness')
            ? content.completion.polite_farewell
            : content.completion.default_farewell;
        const rewards = content.completion.rewards;
        const independentTurns = Math.max(0, content.mission.required_text_turns - state.assistCount);
        const xp = rewards.xp + Math.min(40, independentTurns * 8);
        const confidence = Math.min(3, state.spokenTurns);

        setText('[data-farewell-es]', farewell.es);
        setText('[data-farewell-nl]', farewell.nl);
        setText('[data-reward-xp]', String(xp));
        setText('[data-reward-confidence]', `+${confidence}`);
        setText('[data-reward-courage]', `+${rewards.valentia}`);
        setText('[data-reward-stamp]', rewards.stamp.nl);
        setText('[data-reward-item]', rewards.collectible.nl);
        setText('[data-reward-badge]', rewards.repair_badge.nl);
        setText('[data-spoken-turns]', String(state.spokenTurns));
        setText('[data-spoken-goal-message]', state.spokenTurns >= 3
            ? 'Spreekdoel behaald: je hebt minimaal drie antwoorden hardop gegeven.'
            : `Nog ${3 - state.spokenTurns} spreekbeurt${state.spokenTurns === 2 ? '' : 'en'} nodig. Tekst voltooit de dialoog, maar niet het spreekdoel.`);
        dialogueRoot.querySelector('[data-repair-badge]').hidden = !state.states.includes('used_repair_strategy');
        applyTranslationVisibility();
        updateProgress();
        persist();
        elements.status.textContent = `Missie voltooid. Je verdient ${xp} XP en een paspoortstempel.`;
        elements.complete.focus?.({ preventScroll: true });
        syncAccountProgress();
    };

    const syncAccountProgress = async () => {
        if (dialogueRoot.dataset.authenticated !== 'true') {
            elements.accountSyncTitle.textContent = 'Log in om deze beloning te bewaren.';
            elements.accountSyncMessage.textContent = 'Je resultaat blijft in dit tabblad beschikbaar. Na het inloggen kom je hier terug en schrijven we het veilig naar je account.';
            elements.accountBalances.hidden = true;
            elements.accountSyncRetry.hidden = true;
            return;
        }

        if (state.accountSynced) {
            showAccountSynced(state.accountBalances);
            return;
        }

        if (state.accountSyncPending) return;

        const turns = completionTurns();
        if (turns.length !== content.mission.required_text_turns) {
            showAccountSyncError(`De lokale missieroute is verouderd. Speel de ${content.mission.required_text_turns} beurten opnieuw om dit resultaat aan je account toe te voegen.`);
            return;
        }

        state.accountSyncPending = true;
        elements.accountSyncTitle.textContent = 'Je beloning wordt veilig opgeslagen…';
        elements.accountSyncMessage.textContent = 'De server controleert de gepubliceerde route en berekent je accountbeloning.';
        elements.accountBalances.hidden = true;
        elements.accountSyncRetry.hidden = true;

        try {
            const response = await fetch(dialogueRoot.dataset.completionUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    completion_key: state.completionKey,
                    level: state.level,
                    used_repair_strategy: state.states.includes('used_repair_strategy'),
                    turns,
                }),
            });
            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(payload?.error?.message ?? `Opslaan is tijdelijk niet gelukt (${response.status}).`);
            }
            if (payload?.schema_version !== '1.0.0'
                || payload?.meta?.account_persisted !== true
                || payload?.meta?.audio_persisted !== false
                || payload?.meta?.transcript_persisted !== false
                || payload?.meta?.feedback_persisted !== false
                || !payload?.data?.balances
                || !payload?.data?.mission) {
                throw new Error('De server gaf geen veilig voortgangscontract terug.');
            }

            state.accountSynced = true;
            state.accountBalances = payload.data.balances;
            showAccountSynced(payload.data.balances, payload.data.last_attempt);
            elements.status.textContent = 'Missie voltooid en blijvend opgeslagen bij je account.';
        } catch (error) {
            showAccountSyncError(error instanceof Error ? error.message : 'Opslaan is tijdelijk niet gelukt.');
        } finally {
            state.accountSyncPending = false;
            persist();
        }
    };

    const completionTurns = () => {
        return state.history
            .filter((entry) => !entry.repair)
            .slice(0, content.mission.required_text_turns)
            .map((entry) => ({
                step_id: entry.stepId,
                source: ['speech', 'typed_assist', 'choice_assist'].includes(entry.source) ? entry.source : 'typed_assist',
                assisted: Boolean(entry.assisted ?? entry.source === 'choice_assist'),
            }));
    };

    const showAccountSynced = (balances, attempt = null) => {
        const duplicate = attempt?.duplicate === true;
        elements.accountSyncTitle.textContent = duplicate ? 'Deze voltooiing stond al veilig in je account.' : 'Opgeslagen bij je account.';
        elements.accountSyncMessage.textContent = duplicate
            ? 'De idempotente opslag heeft terecht geen beloning dubbel toegevoegd.'
            : 'Je voortgang, ontgrendelingen en unieke beloningen blijven beschikbaar na opnieuw inloggen.';
        elements.accountBalances.textContent = `${balances?.xp ?? 0} XP · ${balances?.confianza ?? 0} Confianza · ${balances?.valentia ?? 0} Valentía in totaal`;
        elements.accountBalances.hidden = false;
        elements.accountSyncRetry.hidden = true;
    };

    const showAccountSyncError = (message) => {
        elements.accountSyncTitle.textContent = 'Je lokale resultaat is veilig; accountopslag wacht nog.';
        elements.accountSyncMessage.textContent = message;
        elements.accountBalances.hidden = true;
        elements.accountSyncRetry.hidden = false;
    };

    const setNpcLine = (line) => {
        setText('[data-npc-line-es]', line.es);
        setText('[data-npc-line-nl]', line.nl);
        applyTranslationVisibility();
    };

    const applyTranslationVisibility = () => {
        dialogueRoot.querySelectorAll('[data-npc-line-nl], [data-farewell-nl], [data-memory-greeting-nl]').forEach((element) => {
            element.hidden = !translationVisible;
        });
    };

    const updateProgress = () => {
        const total = content?.mission?.required_text_turns ?? 5;
        const current = Math.min(state.completedTurns, total);
        setText('[data-progress-current]', String(current));
        setText('[data-progress-label]', state.completed ? 'Voltooid' : current === 0 ? 'Beginnen' : `${current} beurten afgerond`);
        dialogueRoot.querySelector('[data-progress-bar]').style.width = `${state.completed ? 100 : (current / total) * 100}%`;
    };

    const renderHistory = () => {
        const entries = state.history.map((entry) => {
            const item = document.createElement('li');
            const turn = document.createElement('span');
            const player = document.createElement('p');
            const npc = document.createElement('small');
            const sourceLabel = entry.source === 'speech' ? 'gesproken' : entry.source === 'choice_assist' ? 'voorbeeldzin' : 'tekst';
            turn.textContent = `${entry.repair ? 'Herstelzin' : `Beurt ${entry.turn}`} · ${sourceLabel}`;
            player.lang = 'es';
            player.textContent = entry.player ?? (sensitiveRoleplay ? 'Antwoordtekst niet in browseropslag bewaard.' : '');
            npc.lang = 'es';
            npc.textContent = entry.npc
                ? `${npcName}: ${entry.npc}`
                : sensitiveRoleplay ? `${npcName} reageerde; de tekst is niet in browseropslag bewaard.` : npcName;
            item.append(turn, player, npc);
            return item;
        });

        elements.history.replaceChildren(...entries);
        if (entries.length === 0) {
            const empty = document.createElement('li');
            empty.textContent = `Nog geen antwoorden. ${npcName} wacht rustig op je.`;
            elements.history.append(empty);
        }
    };

    const resolveNext = (next) => next === '@complication' ? content.level_branches[state.level] : next;
    const findStep = (id) => content.steps.find((step) => step.id === id);

    const restoreOrPrepare = () => {
        const saved = readState(storageKey);
        if (saved?.schemaVersion === content.schema_version && saved?.level) {
            state = { ...emptyState(), ...saved };
            setText('[data-level-chip]', state.level);
            if (state.completed) {
                finishDialogue();
            } else {
                elements.stage.hidden = false;
                renderStep();
                elements.status.textContent = 'Je eerdere missie is hervat bij de laatste voltooide stap.';
            }
            return;
        }

        elements.levelSelect.hidden = false;
        elements.status.textContent = 'De dialoog is klaar. Kies hoeveel steun je tijdens deze oefening wilt.';
    };

    async function loadDialogue() {
        elements.error.hidden = true;
        elements.levelSelect.hidden = true;
        elements.stage.hidden = true;

        try {
            const response = await fetch(dialogueRoot.dataset.source, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`De conversatie kon niet worden geladen (${response.status}).`);
            const payload = await response.json();
            applyRuntimeMedia(payload?.data?.content?.media);
            content = validateContent(payload?.data?.content?.domain_data);
            hydrateContent(content);
            restoreOrPrepare();
        } catch (error) {
            elements.error.hidden = false;
            elements.status.textContent = error instanceof Error ? error.message : 'De conversatie kon niet worden geladen.';
        }
    }

    elements.form.addEventListener('submit', submitResponse);
    elements.continueButton.addEventListener('click', continueDialogue);
    elements.feedbackRetry.addEventListener('click', retrySuccessfulTurn);
    dialogueRoot.querySelectorAll('[data-level]').forEach((button) => button.addEventListener('click', () => startLevel(button.dataset.level)));
    dialogueRoot.querySelector('[data-dialogue-retry]')?.addEventListener('click', loadDialogue);
    dialogueRoot.querySelector('[data-hint-toggle]')?.addEventListener('click', (event) => {
        const open = event.currentTarget.getAttribute('aria-expanded') !== 'true';
        event.currentTarget.setAttribute('aria-expanded', String(open));
        dialogueRoot.querySelector('[data-step-hint]').hidden = !open;
        if (open) stepAssisted = true;
    });
    dialogueRoot.querySelector('[data-translation-toggle]')?.addEventListener('click', (event) => {
        translationVisible = event.currentTarget.getAttribute('aria-pressed') !== 'true';
        event.currentTarget.setAttribute('aria-pressed', String(translationVisible));
        applyTranslationVisibility();
        elements.status.textContent = translationVisible ? 'Nederlandse vertaling zichtbaar.' : 'Nederlandse vertaling verborgen.';
    });
    dialogueRoot.addEventListener('scenario:transcript-ready', (event) => {
        speechConfidenceStatus = event.detail?.confidenceStatus ?? 'unavailable';
        originalSpeechTranscript = event.detail?.transcript ?? null;
        elements.status.textContent = 'Je gesproken antwoord is getranscribeerd. Controleer de tekst en gebruik het antwoord wanneer het klopt.';
    });

    const restart = () => {
        try {
            window.sessionStorage.removeItem(storageKey);
        } catch {
            // Opnieuw beginnen blijft werken wanneer sessieopslag is geblokkeerd.
        }
        state = emptyState();
        pendingNext = null;
        pendingStateBeforeTurn = null;
        document.dispatchEvent(new CustomEvent('scenario:turn-changed'));
        elements.stage.hidden = true;
        elements.complete.hidden = true;
        elements.levelSelect.hidden = false;
        setText('[data-level-chip]', 'Niveau kiezen');
        updateProgress();
        elements.status.textContent = 'De missie is gewist. Kies opnieuw hoeveel steun je wilt.';
    };
    dialogueRoot.querySelector('[data-restart-dialogue]')?.addEventListener('click', restart);
    dialogueRoot.querySelector('[data-replay-dialogue]')?.addEventListener('click', restart);
    elements.accountSyncRetry?.addEventListener('click', syncAccountProgress);

    loadDialogue();
}

function emptyState() {
    return {
        schemaVersion: '1.0.0',
        level: null,
        currentStep: null,
        completedTurns: 0,
        spokenTurns: 0,
        assistCount: 0,
        states: [],
        history: [],
        completed: false,
        completionKey: null,
        accountSynced: false,
        accountBalances: null,
        accountSyncPending: false,
    };
}

function createCompletionKey() {
    if (typeof window.crypto?.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    if (typeof window.crypto?.getRandomValues === 'function') {
        window.crypto.getRandomValues(bytes);
    } else {
        bytes.forEach((_, index) => {
            bytes[index] = Math.floor(Math.random() * 256);
        });
    }
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function normalize(value) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function unique(values) {
    return [...new Set(values)];
}

function cloneState(state) {
    return JSON.parse(JSON.stringify(state));
}

function readState(key) {
    try {
        return JSON.parse(window.sessionStorage.getItem(key) ?? 'null');
    } catch {
        return null;
    }
}

function persistState(key, state, redactDialogueText = false) {
    try {
        const storedState = redactDialogueText
            ? {
                ...state,
                history: state.history.map(({ player: _player, npc: _npc, feedback: _feedback, ...evidence }) => evidence),
            }
            : state;
        window.sessionStorage.setItem(key, JSON.stringify(storedState));
    } catch {
        // De dialoog blijft speelbaar wanneer sessieopslag niet beschikbaar is.
    }
}
