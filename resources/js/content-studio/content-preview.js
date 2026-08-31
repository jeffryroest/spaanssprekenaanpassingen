const preview = document.querySelector('[data-content-preview]');

if (preview) {
    const frame = preview.querySelector('[data-preview-frame]');
    const widths = { mobile: '390px', tablet: '768px', desktop: '1280px' };

    preview.querySelectorAll('[data-preview-device]').forEach((button) => {
        button.addEventListener('click', () => {
            const device = button.dataset.previewDevice;
            frame.style.maxWidth = widths[device] ?? widths.desktop;
            frame.dataset.previewWidth = device;
            preview.querySelectorAll('[data-preview-device]').forEach((candidate) => {
                candidate.setAttribute('aria-pressed', String(candidate === button));
                candidate.classList.toggle('bg-white', candidate === button);
            });
        });
    });

    const worldFeedback = preview.querySelector('[data-preview-world-feedback]');
    preview.querySelectorAll('[data-preview-hotspot]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!worldFeedback) return;
            const state = button.dataset.previewState === 'open' ? 'Open en speelbaar' : 'Nog vergrendeld';
            worldFeedback.textContent = `${state}: ${button.dataset.previewDescription}`;
        });
    });

    if (preview.dataset.previewScene?.endsWith('_text_dialogue')) {
        const data = JSON.parse(preview.querySelector('[data-preview-domain-data]').textContent);
        const stepPanel = preview.querySelector('[data-preview-step]');
        const answer = preview.querySelector('[data-preview-answer]');
        const feedback = preview.querySelector('[data-preview-feedback]');
        const history = preview.querySelector('[data-preview-history]');
        const level = preview.querySelector('[data-preview-level]');
        const steps = new Map(data.steps.map((step) => [step.id, step]));
        let currentId = data.mission.start_step;
        let completed = false;

        const normalize = (value) => String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9ñü\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        const matches = (option, value) => (option.requirements ?? []).every((group) =>
            group.some((term) => normalize(value).includes(normalize(term))),
        );

        const currentStep = () => steps.get(currentId);

        const renderStep = () => {
            const step = currentStep();
            if (!step) {
                const error = document.createElement('div');
                error.className = 'rounded-xl bg-red-100 p-4 font-bold text-red-800';
                error.textContent = 'De route verwijst naar een ontbrekende stap.';
                stepPanel.replaceChildren(error);
                return;
            }
            stepPanel.replaceChildren();
            const badge = document.createElement('p');
            badge.className = 'text-xs font-black uppercase tracking-[0.15em] text-[#a8432b]';
            badge.textContent = `Beurt ${step.turn}`;
            const npcLine = document.createElement('p');
            npcLine.className = 'mt-2 rounded-2xl bg-[#edf2e9] p-4 text-base font-bold leading-7 text-[#233a32]';
            npcLine.lang = 'es';
            npcLine.textContent = step.npc_line.es;
            const translation = document.createElement('p');
            translation.className = 'mt-2 text-sm text-[#6c7b73]';
            translation.textContent = step.npc_line.nl;
            const prompt = document.createElement('p');
            prompt.className = 'mt-4 text-sm font-bold text-[#694124]';
            prompt.textContent = step.prompt;
            const hint = document.createElement('p');
            hint.className = 'mt-2 text-xs text-[#6c7b73]';
            hint.textContent = `Hint: ${step.hint}`;
            stepPanel.append(badge, npcLine, translation, prompt, hint);
            answer.placeholder = step.placeholder ?? 'Typ een Spaans antwoord…';
            answer.disabled = false;
            answer.focus();
        };

        const addHistory = (step, value) => {
            const item = document.createElement('li');
            item.className = 'rounded-xl bg-white/70 p-3 text-[#53675e]';
            item.textContent = `Beurt ${step.turn}: ${value}`;
            history.append(item);
        };

        const showFeedback = (strength, focus, success) => {
            feedback.className = `mt-5 rounded-2xl border p-4 text-sm ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950'}`;
            feedback.replaceChildren();
            const strong = document.createElement('p');
            strong.className = 'font-black';
            strong.textContent = strength;
            const next = document.createElement('p');
            next.className = 'mt-1 leading-6';
            next.textContent = focus;
            feedback.append(strong, next);
        };

        const advance = (next) => {
            if (next === '@complete') {
                completed = true;
                const panel = document.createElement('div');
                panel.className = 'rounded-2xl bg-emerald-100 p-5 text-emerald-950';
                const label = document.createElement('p');
                label.className = 'text-xs font-black uppercase tracking-wide';
                label.textContent = 'Route voltooid';
                const spanish = document.createElement('p');
                spanish.className = 'mt-2 font-black';
                spanish.lang = 'es';
                spanish.textContent = data.completion.default_farewell.es;
                const dutch = document.createElement('p');
                dutch.className = 'mt-1 text-sm';
                dutch.textContent = data.completion.default_farewell.nl;
                const safety = document.createElement('p');
                safety.className = 'mt-3 text-xs font-bold';
                safety.textContent = 'Preview: XP en beloningen worden niet opgeslagen.';
                panel.append(label, spanish, dutch, safety);
                stepPanel.replaceChildren(panel);
                answer.value = '';
                answer.disabled = true;
                return;
            }
            currentId = next === '@complication' ? data.level_branches[level.value] : next;
            answer.value = '';
            renderStep();
        };

        preview.querySelector('[data-preview-submit]').addEventListener('click', () => {
            if (completed || !answer.value.trim()) return;
            const step = currentStep();
            const repairMatch = (data.repair.terms ?? []).some((term) => normalize(answer.value).includes(normalize(term)));
            addHistory(step, answer.value.trim());
            if (repairMatch) {
                showFeedback(data.repair.feedback.strength, data.repair.feedback.focus, true);
                answer.value = '';
                return;
            }
            const option = (step.options ?? []).find((candidate) => matches(candidate, answer.value));
            if (!option) {
                showFeedback(step.fallback.strength, step.fallback.focus, false);
                return;
            }
            showFeedback(option.feedback.strength, option.feedback.focus, true);
            advance(option.next);
        });

        preview.querySelector('[data-preview-example]').addEventListener('click', () => {
            answer.value = currentStep()?.choices?.[0] ?? '';
            answer.focus();
        });

        const reset = () => {
            currentId = data.mission.start_step;
            completed = false;
            answer.value = '';
            feedback.replaceChildren();
            feedback.className = 'mt-5';
            history.replaceChildren();
            renderStep();
        };
        preview.querySelector('[data-preview-reset]').addEventListener('click', reset);
        level.addEventListener('change', reset);
        renderStep();
    }
}
