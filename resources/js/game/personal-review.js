const reviewRoot = document.querySelector('[data-personal-review]');

if (reviewRoot) {
    const deckSource = reviewRoot.querySelector('[data-review-deck]');
    const deck = JSON.parse(deckSource?.textContent || '{"cards":[],"meta":{}}');
    const cards = Array.isArray(deck.cards) ? deck.cards : [];
    const elements = {
        stage: reviewRoot.querySelector('[data-review-stage]'),
        complete: reviewRoot.querySelector('[data-review-complete]'),
        position: reviewRoot.querySelector('[data-review-position]'),
        total: reviewRoot.querySelector('[data-review-total]'),
        progress: reviewRoot.querySelector('[data-review-progress]'),
        due: reviewRoot.querySelector('[data-review-due]'),
        avatar: reviewRoot.querySelector('[data-review-avatar]'),
        npc: reviewRoot.querySelector('[data-review-npc]'),
        setting: reviewRoot.querySelector('[data-review-setting]'),
        npcEs: reviewRoot.querySelector('[data-review-npc-es]'),
        npcNl: reviewRoot.querySelector('[data-review-npc-nl]'),
        mission: reviewRoot.querySelector('[data-review-mission]'),
        prompt: reviewRoot.querySelector('[data-review-prompt]'),
        response: reviewRoot.querySelector('[data-review-response]'),
        help: reviewRoot.querySelector('[data-review-help]'),
        check: reviewRoot.querySelector('[data-review-check]'),
        status: reviewRoot.querySelector('[data-review-status]'),
        answer: reviewRoot.querySelector('[data-review-answer]'),
        example: reviewRoot.querySelector('[data-review-example]'),
        hint: reviewRoot.querySelector('[data-review-hint]'),
        ratings: reviewRoot.querySelector('[data-review-ratings]'),
        completeMessage: reviewRoot.querySelector('[data-review-complete-message]'),
        reward: reviewRoot.querySelector('[data-review-reward]'),
        earnedXp: reviewRoot.querySelector('[data-review-earned-xp]'),
        earnedConfidence: reviewRoot.querySelector('[data-review-earned-confidence]'),
        retrySave: reviewRoot.querySelector('[data-review-retry-save]'),
    };
    let index = 0;
    let assisted = false;
    let results = [];
    let pendingPayload = null;

    const completionKey = () => window.crypto?.randomUUID?.()
        ?? 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
            const random = Math.floor(Math.random() * 16);
            return (character === 'x' ? random : (random & 0x3) | 0x8).toString(16);
        });

    const renderCard = () => {
        const card = cards[index];
        if (!card) return;

        assisted = false;
        elements.position.textContent = `Kaart ${index + 1}`;
        elements.total.textContent = `van ${cards.length}`;
        elements.progress.style.width = `${((index + 1) / cards.length) * 100}%`;
        elements.due.textContent = card.due_state === 'due' ? 'Nu aan de beurt' : (card.due_state === 'new' ? 'Nieuwe kaart' : 'Extra oefening');
        elements.avatar.textContent = (card.npc_name || 'M').slice(0, 1).toUpperCase();
        elements.npc.textContent = card.npc_name || 'Madrid';
        elements.setting.textContent = card.setting || 'Eerdere missie';
        elements.npcEs.textContent = card.npc_line?.es || '';
        elements.npcNl.textContent = card.npc_line?.nl || '';
        elements.mission.textContent = card.mission_title || 'Persoonlijke kaart';
        elements.prompt.textContent = card.prompt || 'Zeg je antwoord in het Spaans.';
        elements.response.value = '';
        elements.response.dataset.responseSource = 'typed_assist';
        elements.answer.hidden = true;
        elements.ratings.hidden = true;
        elements.help.disabled = false;
        elements.check.disabled = false;
        elements.status.textContent = 'Probeer de zin eerst zelf. Fouten maken hoort bij ophalen uit je geheugen.';
        document.dispatchEvent(new CustomEvent('scenario:turn-changed'));
        elements.response.focus({ preventScroll: true });
    };

    const showExample = (fromHelp = false) => {
        const response = elements.response.value.trim();
        if (!response && !fromHelp) {
            elements.status.textContent = 'Spreek of typ eerst een poging; daarna vergelijk je met het voorbeeld.';
            elements.response.focus({ preventScroll: true });
            return;
        }

        const card = cards[index];
        assisted ||= fromHelp;
        elements.example.textContent = card.examples?.[0] || 'Gebruik je eigen woorden om de bedoeling duidelijk te maken.';
        elements.hint.textContent = card.hint || 'Er is meer dan één goed antwoord mogelijk.';
        elements.answer.hidden = false;
        elements.ratings.hidden = false;
        elements.help.disabled = true;
        elements.check.disabled = true;
        elements.status.textContent = 'Kies nu eerlijk hoe makkelijk je de zin uit je geheugen haalde.';
        elements.ratings.querySelector('button')?.focus({ preventScroll: true });
    };

    const save = async () => {
        pendingPayload ||= {
            completion_key: completionKey(),
            cards: results,
        };
        elements.stage.hidden = true;
        elements.complete.hidden = false;
        elements.completeMessage.textContent = 'Je planning en dagbeloning worden veilig opgeslagen…';
        elements.retrySave.hidden = true;
        elements.reward.hidden = true;
        elements.complete.focus({ preventScroll: true });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(reviewRoot.dataset.completionUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify(pendingPayload),
            });
            const payload = await response.json().catch(() => null);
            if (!response.ok) throw new Error(payload?.error?.message || payload?.message || 'Opslaan is mislukt.');

            const awarded = payload.data?.last_attempt?.awarded_now ?? {};
            elements.earnedXp.textContent = `${awarded.xp ?? 0} XP`;
            elements.earnedConfidence.textContent = `${awarded.confianza ?? 0} Confianza`;
            elements.reward.hidden = false;
            elements.completeMessage.textContent = payload.data?.review?.daily_reward_already_claimed
                ? 'Je kaarten zijn bijgewerkt. De dagbeloning had je vandaag al ontvangen.'
                : 'Je kaarten zijn bijgewerkt en komen terug wanneer dat voor jouw oefenritme helpt.';
            pendingPayload = null;
        } catch (error) {
            elements.completeMessage.textContent = error instanceof Error
                ? `${error.message} Je antwoorden zijn niet bewaard; alleen de structurele sessie wacht nog op een nieuwe poging.`
                : 'Opslaan is mislukt. Probeer de structurele sessie opnieuw te verzenden.';
            elements.retrySave.hidden = false;
            elements.retrySave.focus({ preventScroll: true });
        }
    };

    elements.help?.addEventListener('click', () => showExample(true));
    elements.check?.addEventListener('click', () => showExample(false));
    elements.ratings?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-review-rating]');
        if (!button) return;

        const card = cards[index];
        results.push({
            practice_key: card.practice_key,
            source: elements.response.dataset.responseSource === 'speech' ? 'speech' : 'typed_assist',
            assisted,
            rating: button.dataset.reviewRating,
        });
        elements.response.value = '';
        index += 1;
        if (index < cards.length) {
            renderCard();
            return;
        }
        save();
    });
    elements.retrySave?.addEventListener('click', save);
    document.addEventListener('scenario:transcript-ready', () => {
        elements.response.dataset.responseSource = 'speech';
    });

    if (cards.length > 0) renderCard();
}
