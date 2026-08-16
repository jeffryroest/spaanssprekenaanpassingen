const dialogueRoot = document.querySelector('[data-panaderia-dialogue]');

if (dialogueRoot) {
    const storageKey = 'panaderia-text-dialogue-v1';
    const elements = {
        stage: dialogueRoot.querySelector('[data-dialogue-stage]'),
        levelSelect: dialogueRoot.querySelector('[data-level-select]'),
        complete: dialogueRoot.querySelector('[data-dialogue-complete]'),
        error: dialogueRoot.querySelector('[data-dialogue-error]'),
        status: dialogueRoot.querySelector('[data-dialogue-status]'),
        form: dialogueRoot.querySelector('[data-dialogue-form]'),
        response: dialogueRoot.querySelector('[data-player-response]'),
        feedback: dialogueRoot.querySelector('[data-feedback]'),
        continueButton: dialogueRoot.querySelector('[data-dialogue-continue]'),
        history: dialogueRoot.querySelector('[data-dialogue-history]'),
    };
    let content;
    let state = emptyState();
    let pendingNext = null;
    let stepAssisted = false;
    let speechConfidenceStatus = null;
    let originalSpeechTranscript = null;
    let translationVisible = false;
    const persist = () => persistState(storageKey, state);

    const setText = (selector, value) => {
        const element = dialogueRoot.querySelector(selector);
        if (element) element.textContent = value ?? '';
    };

    const validateContent = (data) => {
        if (data?.schema_version !== '1.0.0' || data?.scene !== 'panaderia_text_dialogue') {
            throw new Error('De conversatie gebruikt niet het La Espiga-contract v1.');
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
        elements.status.textContent = `Niveau ${level} gekozen. Lucía begroet je; neem rustig de tijd.`;
    };

    const renderStep = () => {
        const step = findStep(state.currentStep);
        if (!step) throw new Error('De opgeslagen dialoogstap bestaat niet meer.');

        pendingNext = null;
        stepAssisted = false;
        speechConfidenceStatus = null;
        originalSpeechTranscript = null;
        elements.feedback.hidden = true;
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
        document.dispatchEvent(new CustomEvent('panaderia:turn-changed'));
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

    const submitResponse = (event) => {
        event.preventDefault();
        const step = findStep(state.currentStep);
        const answer = elements.response.value.trim();
        const normalized = normalize(answer);
        const responseSource = elements.response.dataset.responseSource || 'typed_assist';
        if (!normalized) return;

        if (content.repair.terms.some((term) => normalized.includes(normalize(term)))) {
            state.states = unique([...state.states, 'used_repair_strategy']);
            state.history.push({
                turn: step.turn,
                player: answer,
                npc: content.repair.npc_response.es,
                repair: true,
                source: responseSource,
                confidenceStatus: responseSource === 'speech' ? speechConfidenceStatus : null,
                transcriptCorrected: responseSource === 'speech' && answer !== originalSpeechTranscript,
            });
            showSuccessfulResponse(content.repair.npc_response, content.repair.feedback, state.currentStep);
            persist();
            elements.status.textContent = 'Herstelstrategie herkend. Dat is taalvaardigheid, geen fout.';
            return;
        }

        const option = step.options.find(({ requirements }) => requirements.every((group) =>
            group.some((term) => normalized.includes(normalize(term)))));

        if (!option) {
            showFeedback(step.fallback, false);
            elements.status.textContent = 'Lucía heeft nog niet genoeg informatie. Bekijk de gerichte tip en probeer opnieuw.';
            return;
        }

        const politeness = ['por favor', 'gracias'].some((term) => normalized.includes(term));
        state.states = unique([...state.states, ...option.states, ...(politeness ? ['used_politeness'] : [])]);
        state.history.push({
            turn: step.turn,
            player: answer,
            npc: option.npc_response.es,
            repair: false,
            source: responseSource,
            confidenceStatus: responseSource === 'speech' ? speechConfidenceStatus : null,
            transcriptCorrected: responseSource === 'speech' && answer !== originalSpeechTranscript,
        });
        state.completedTurns += 1;
        if (responseSource === 'speech') state.spokenTurns += 1;
        if (stepAssisted) state.assistCount += 1;
        showSuccessfulResponse(option.npc_response, option.feedback, resolveNext(option.next));
        persist();
        elements.status.textContent = `Beurt ${step.turn} voltooid. Lees wat goed ging en ga verder.`;
    };

    const showSuccessfulResponse = (npcResponse, feedback, next) => {
        pendingNext = next;
        setNpcLine(npcResponse);
        showFeedback(feedback, true);
        elements.form.hidden = true;
        elements.continueButton.hidden = false;
        elements.continueButton.focus({ preventScroll: true });
        updateProgress();
        renderHistory();
    };

    const showFeedback = (feedback, success) => {
        elements.feedback.hidden = false;
        elements.feedback.dataset.result = success ? 'success' : 'retry';
        elements.feedback.querySelector('.bakery-feedback-icon').textContent = success ? '✓' : '↻';
        setText('[data-feedback-strength]', feedback.strength);
        setText('[data-feedback-focus]', feedback.focus);
    };

    const continueDialogue = () => {
        if (pendingNext === '@complete') {
            finishDialogue();
            return;
        }

        state.currentStep = pendingNext;
        persist();
        renderStep();
    };

    const finishDialogue = () => {
        state.completed = true;
        state.currentStep = null;
        elements.stage.hidden = true;
        elements.complete.hidden = false;
        document.dispatchEvent(new CustomEvent('panaderia:turn-changed'));
        const farewell = state.states.includes('greeted_lucia') && state.states.includes('used_politeness')
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
        elements.status.textContent = `Missie voltooid. Je verdient ${xp} XP en je eerste paspoortstempel.`;
        elements.complete.focus?.({ preventScroll: true });
    };

    const setNpcLine = (line) => {
        setText('[data-npc-line-es]', line.es);
        setText('[data-npc-line-nl]', line.nl);
        applyTranslationVisibility();
    };

    const applyTranslationVisibility = () => {
        dialogueRoot.querySelectorAll('[data-npc-line-nl], [data-farewell-nl]').forEach((element) => {
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
            player.textContent = entry.player;
            npc.lang = 'es';
            npc.textContent = `Lucía: ${entry.npc}`;
            item.append(turn, player, npc);
            return item;
        });

        elements.history.replaceChildren(...entries);
        if (entries.length === 0) {
            const empty = document.createElement('li');
            empty.textContent = 'Nog geen antwoorden. Lucía wacht rustig op je.';
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
                elements.status.textContent = 'Je eerdere bestelling is hervat bij de laatste voltooide stap.';
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
    dialogueRoot.addEventListener('panaderia:transcript-ready', (event) => {
        stepAssisted = false;
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
        document.dispatchEvent(new CustomEvent('panaderia:turn-changed'));
        elements.stage.hidden = true;
        elements.complete.hidden = true;
        elements.levelSelect.hidden = false;
        setText('[data-level-chip]', 'Niveau kiezen');
        updateProgress();
        elements.status.textContent = 'De bestelling is gewist. Kies opnieuw hoeveel steun je wilt.';
    };
    dialogueRoot.querySelector('[data-restart-dialogue]')?.addEventListener('click', restart);
    dialogueRoot.querySelector('[data-replay-dialogue]')?.addEventListener('click', restart);

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
    };
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

function readState(key) {
    try {
        return JSON.parse(window.sessionStorage.getItem(key) ?? 'null');
    } catch {
        return null;
    }
}

function persistState(key, state) {
    try {
        window.sessionStorage.setItem(key, JSON.stringify(state));
    } catch {
        // De dialoog blijft speelbaar wanneer sessieopslag niet beschikbaar is.
    }
}
