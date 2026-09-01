const source = document.querySelector('[data-content-builder-source]');
const root = document.querySelector('[data-content-builder-root]');

if (source && root) {
    const parse = () => {
        try {
            const value = JSON.parse(source.value || '{}');
            return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        } catch {
            return null;
        }
    };

    let data = parse();

    const element = (tag, classes = '', text = '') => {
        const node = document.createElement(tag);
        if (classes) node.className = classes;
        if (text) node.textContent = text;
        return node;
    };

    const sync = () => {
        source.value = JSON.stringify(data, null, 2);
        source.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const textField = (container, label, value, update, options = {}) => {
        const wrapper = element('div', options.wide ? 'md:col-span-2' : '');
        const id = `builder-${Math.random().toString(36).slice(2)}`;
        const labelNode = element('label', 'cs-label', label);
        labelNode.htmlFor = id;
        wrapper.append(labelNode);

        const input = options.multiline ? element('textarea', 'cs-field resize-y') : element('input', 'cs-field');
        input.id = id;
        input.value = value ?? '';
        if (options.multiline) input.rows = options.rows ?? 3;
        if (options.type) input.type = options.type;
        if (options.readonly) input.readOnly = true;
        if (options.min !== undefined) input.min = options.min;
        if (options.max !== undefined) input.max = options.max;
        if (options.lang) input.lang = options.lang;
        input.addEventListener('input', () => {
            const next = options.number ? Number(input.value) : input.value;
            update(next);
            sync();
        });
        wrapper.append(input);

        if (options.help) wrapper.append(element('p', 'cs-help', options.help));
        container.append(wrapper);
        return input;
    };

    const selectField = (container, label, value, values, update) => {
        const wrapper = element('div');
        const id = `builder-${Math.random().toString(36).slice(2)}`;
        const labelNode = element('label', 'cs-label', label);
        labelNode.htmlFor = id;
        const select = element('select', 'cs-field');
        select.id = id;
        values.forEach(([optionValue, optionLabel]) => {
            const option = element('option', '', optionLabel);
            option.value = optionValue;
            option.selected = optionValue === value;
            select.append(option);
        });
        select.addEventListener('change', () => {
            update(select.value);
            sync();
        });
        wrapper.append(labelNode, select);
        container.append(wrapper);
    };

    const lines = (value) => Array.isArray(value) ? value.join('\n') : '';
    const parseLines = (value) => value.split('\n').map((line) => line.trim()).filter(Boolean);
    const requirements = (value) => Array.isArray(value)
        ? value.map((group) => Array.isArray(group) ? group.join(' | ') : '').filter(Boolean).join('\n')
        : '';
    const parseRequirements = (value) => value.split('\n')
        .map((line) => line.split('|').map((term) => term.trim()).filter(Boolean))
        .filter((group) => group.length);

    const section = (title, description, open = false) => {
        const details = element('details', 'rounded-2xl border border-slate-200 bg-white shadow-sm');
        details.open = open;
        const summary = element('summary', 'cursor-pointer list-none px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:px-6');
        summary.append(element('span', 'block font-bold text-slate-900', title));
        if (description) summary.append(element('span', 'mt-1 block text-sm leading-5 text-slate-500', description));
        const body = element('div', 'grid gap-5 border-t border-slate-200 p-5 md:grid-cols-2 sm:p-6');
        details.append(summary, body);
        root.append(details);
        return body;
    };

    const arrayHeader = (container, title, count, addLabel, add) => {
        const header = element('div', 'mb-4 flex flex-wrap items-center justify-between gap-3');
        header.append(element('h3', 'font-bold text-slate-900', `${title} (${count})`));
        const button = element('button', 'cs-button-secondary', addLabel);
        button.type = 'button';
        button.addEventListener('click', add);
        header.append(button);
        container.append(header);
    };

    const cardActions = (container, index, items, rerender, label) => {
        const actions = element('div', 'flex flex-wrap justify-end gap-2 border-t border-slate-100 px-4 py-3');
        const move = (text, offset) => {
            const button = element('button', 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700', text);
            button.type = 'button';
            button.disabled = index + offset < 0 || index + offset >= items.length;
            button.addEventListener('click', () => {
                [items[index], items[index + offset]] = [items[index + offset], items[index]];
                sync();
                rerender();
            });
            return button;
        };
        actions.append(move('Omhoog', -1), move('Omlaag', 1));
        const remove = element('button', 'rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-bold text-red-700', `${label} verwijderen`);
        remove.type = 'button';
        remove.addEventListener('click', () => {
            if (!window.confirm(`${label} verwijderen uit dit concept?`)) return;
            items.splice(index, 1);
            sync();
            rerender();
        });
        actions.append(remove);
        container.append(actions);
    };

    const renderMadrid = () => {
        const intro = data.intro ??= {};
        const introBody = section('Wereldintro', 'Wat ziet en doet de speler bij aankomst?', true);
        textField(introBody, 'Bovenregel', intro.eyebrow, (value) => intro.eyebrow = value);
        textField(introBody, 'Titel', intro.title, (value) => intro.title = value);
        textField(introBody, 'Beschrijving', intro.description, (value) => intro.description = value, { multiline: true, wide: true });
        textField(introBody, 'Spelersdoel', intro.objective, (value) => intro.objective = value, { multiline: true, wide: true });

        const hotspotsBody = section('Hotspots', 'Locaties op de wereldkaart; coördinaten lopen van 0 tot 100.', true);
        const hotspots = data.hotspots ??= [];
        arrayHeader(hotspotsBody, 'Locaties', hotspots.length, 'Hotspot toevoegen', () => {
            hotspots.push({
                id: `madrid.location.${hotspots.length + 1}`,
                kind: 'location',
                label: { es: 'Nueva ubicación', nl: 'Nieuwe locatie' },
                description: 'Nog te beschrijven.',
                state: 'locked',
                position: { x: 50, y: 50 },
                action: { type: 'preview', target: 'mission.madrid.new' },
            });
            sync(); render();
        });
        hotspotsBody.classList.remove('md:grid-cols-2');
        hotspots.forEach((hotspot, index) => {
            hotspot.label ??= {};
            hotspot.position ??= {};
            hotspot.action ??= {};
            const card = element('article', 'overflow-hidden rounded-xl border border-slate-200');
            const grid = element('div', 'grid gap-4 p-4 md:grid-cols-2');
            textField(grid, 'Interne id', hotspot.id, (value) => hotspot.id = value);
            textField(grid, 'Soort', hotspot.kind, (value) => hotspot.kind = value);
            textField(grid, 'Naam Spaans', hotspot.label.es, (value) => hotspot.label.es = value, { lang: 'es' });
            textField(grid, 'Naam Nederlands', hotspot.label.nl, (value) => hotspot.label.nl = value);
            textField(grid, 'Beschrijving', hotspot.description, (value) => hotspot.description = value, { multiline: true, wide: true });
            selectField(grid, 'Toestand', hotspot.state, [['open', 'Open'], ['locked', 'Vergrendeld']], (value) => hotspot.state = value);
            selectField(grid, 'Actietype', hotspot.action.type, [['mission_preview', 'Missie openen'], ['preview', 'Vooruitblik']], (value) => hotspot.action.type = value);
            textField(grid, 'Positie X', hotspot.position.x, (value) => hotspot.position.x = value, { type: 'number', min: 0, max: 100, number: true });
            textField(grid, 'Positie Y', hotspot.position.y, (value) => hotspot.position.y = value, { type: 'number', min: 0, max: 100, number: true });
            textField(grid, 'Doelmissie', hotspot.action.target, (value) => hotspot.action.target = value, { wide: true });
            card.append(grid);
            cardActions(card, index, hotspots, render, 'Hotspot');
            hotspotsBody.append(card);
        });

        const inspectablesBody = section('Onderzoekspunten', 'Kleine optionele ontdekkingen vóór de missie.');
        const inspectables = data.inspectables ??= [];
        inspectablesBody.classList.remove('md:grid-cols-2');
        arrayHeader(inspectablesBody, 'Onderzoekspunten', inspectables.length, 'Onderzoekspunt toevoegen', () => {
            inspectables.push({
                id: `madrid.discovery.${inspectables.length + 1}`,
                kind: 'culture',
                title: 'Nieuwe ontdekking',
                body: 'Nog te beschrijven.',
                position: { x: 50, y: 50 },
                reward: { curiosidad: 1 },
            });
            sync(); render();
        });
        inspectables.forEach((item, index) => {
            item.position ??= {};
            item.reward ??= {};
            const card = element('article', 'overflow-hidden rounded-xl border border-slate-200');
            const grid = element('div', 'grid gap-4 p-4 md:grid-cols-2');
            textField(grid, 'Interne id', item.id, (value) => item.id = value);
            textField(grid, 'Soort', item.kind, (value) => item.kind = value);
            textField(grid, 'Titel', item.title, (value) => item.title = value, { wide: true });
            textField(grid, 'Beschrijving', item.body, (value) => item.body = value, { multiline: true, wide: true });
            textField(grid, 'Positie X', item.position.x, (value) => item.position.x = value, { type: 'number', min: 0, max: 100, number: true });
            textField(grid, 'Positie Y', item.position.y, (value) => item.position.y = value, { type: 'number', min: 0, max: 100, number: true });
            textField(grid, 'Curiosidad', item.reward.curiosidad, (value) => item.reward.curiosidad = value, { type: 'number', min: 0, max: 20, number: true });
            textField(grid, 'Woordparen', Array.isArray(item.items) ? item.items.map((entry) => `${entry.es ?? ''} | ${entry.nl ?? ''}`).join('\n') : '', (value) => {
                item.items = value.split('\n').map((line) => line.split('|').map((part) => part.trim())).filter((parts) => parts[0]).map(([es, nl]) => ({ es, nl: nl ?? '' }));
            }, { multiline: true, wide: true, help: 'Eén Spaans | Nederlands paar per regel.' });
            textField(grid, 'Luisterzin Spaans', item.language?.es ?? '', (value) => {
                item.language ??= {};
                item.language.es = value;
            }, { lang: 'es' });
            textField(grid, 'Luisterzin Nederlands', item.language?.nl ?? '', (value) => {
                item.language ??= {};
                item.language.nl = value;
            });
            card.append(grid);
            cardActions(card, index, inspectables, render, 'Onderzoekspunt');
            inspectablesBody.append(card);
        });
    };

    const renderDialogue = () => {
        const npc = data.npc ??= { role: {} };
        npc.role ??= {};
        const mission = data.mission ??= { title: {} };
        mission.title ??= {};
        const identity = section('NPC en missie', 'De gesprekspartner, context en het concrete communicatieve doel.', true);
        textField(identity, 'NPC-id', npc.id, (value) => npc.id = value);
        textField(identity, 'Naam', npc.name, (value) => npc.name = value);
        textField(identity, 'Rol Spaans', npc.role.es, (value) => npc.role.es = value, { lang: 'es' });
        textField(identity, 'Rol Nederlands', npc.role.nl, (value) => npc.role.nl = value);
        textField(identity, 'Karakter en houding', npc.description, (value) => npc.description = value, { multiline: true, wide: true });
        textField(identity, 'Missie-id', mission.id, (value) => mission.id = value, { readonly: true, help: 'Contractidentiteit; wijziging vereist een nieuwe expliciete missie-integratie.' });
        textField(identity, 'Startstap', mission.start_step, (value) => mission.start_step = value);
        textField(identity, 'Titel Spaans', mission.title.es, (value) => mission.title.es = value, { lang: 'es' });
        textField(identity, 'Titel Nederlands', mission.title.nl, (value) => mission.title.nl = value);
        textField(identity, 'Spelersdoel', mission.objective, (value) => mission.objective = value, { multiline: true, wide: true });
        textField(identity, 'Vereiste actieve beurten', mission.required_text_turns, (value) => mission.required_text_turns = value, { type: 'number', min: 5, max: 5, number: true, readonly: true });

        if (data.scene === 'station_text_dialogue') {
            const journey = data.journey ??= { fictional: true, title: {}, details: [] };
            journey.title ??= {};
            journey.details ??= [];
            const journeyBody = section('Fictieve oefenreis', 'De reisgegevens die de speler vóór het loketgesprek krijgt. Dit is nooit een actuele dienstregeling.');
            textField(journeyBody, 'Titel Spaans', journey.title.es, (value) => journey.title.es = value, { lang: 'es' });
            textField(journeyBody, 'Titel Nederlands', journey.title.nl, (value) => journey.title.nl = value);
            textField(journeyBody, 'Toelichting', journey.notice, (value) => journey.notice = value, { multiline: true, wide: true });
            textField(journeyBody, 'Reisdetails', journey.details.map((detail) => `${detail.label?.es ?? ''} | ${detail.label?.nl ?? ''} | ${detail.value?.es ?? ''} | ${detail.value?.nl ?? ''}`).join('\n'), (value) => {
                journey.details = value.split('\n').map((line) => line.split('|').map((part) => part.trim())).filter((parts) => parts[0]).map(([labelEs, labelNl, valueEs, valueNl]) => ({
                    label: { es: labelEs, nl: labelNl ?? '' },
                    value: { es: valueEs ?? '', nl: valueNl ?? '' },
                }));
            }, { multiline: true, wide: true, rows: 5, help: 'Eén regel per detail: label Spaans | label Nederlands | waarde Spaans | waarde Nederlands.' });
        }

        if (data.scene === 'final_text_dialogue') {
            const memory = data.memory ??= { returning_greeting: {}, first_greeting: {}, recap_sources: [] };
            memory.returning_greeting ??= {};
            memory.first_greeting ??= {};
            memory.recap_sources ??= [];
            const memoryBody = section('NPC-herkenning', 'Alleen voltooide missie-id’s mogen de terugkeer en terugblik bepalen. Vrije spelersinvoer is nooit een geheugenbron.', true);
            textField(memoryBody, 'Terugkerende NPC-id', memory.returning_npc_id, (value) => memory.returning_npc_id = value, { readonly: true });
            textField(memoryBody, 'Bronmissie', memory.source_mission_key, (value) => memory.source_mission_key = value, { readonly: true });
            textField(memoryBody, 'Begroeting terugkeer · Spaans', memory.returning_greeting.es, (value) => memory.returning_greeting.es = value, { multiline: true, lang: 'es' });
            textField(memoryBody, 'Begroeting terugkeer · Nederlands', memory.returning_greeting.nl, (value) => memory.returning_greeting.nl = value, { multiline: true });
            textField(memoryBody, 'Begroeting eerste bezoek · Spaans', memory.first_greeting.es, (value) => memory.first_greeting.es = value, { multiline: true, lang: 'es' });
            textField(memoryBody, 'Begroeting eerste bezoek · Nederlands', memory.first_greeting.nl, (value) => memory.first_greeting.nl = value, { multiline: true });
            textField(memoryBody, 'Privacytoelichting', memory.privacy_notice, (value) => memory.privacy_notice = value, { multiline: true, wide: true });
            textField(memoryBody, 'Terugblikbronnen', memory.recap_sources.map((source) => `${source.id ?? ''} | ${source.mission_key ?? ''} | ${source.label?.es ?? ''} | ${source.label?.nl ?? ''}`).join('\n'), (value) => {
                memory.recap_sources = value.split('\n').map((line) => line.split('|').map((part) => part.trim())).filter((parts) => parts[0]).map(([id, missionKey, labelEs, labelNl]) => ({
                    id,
                    mission_key: missionKey ?? '',
                    label: { es: labelEs ?? '', nl: labelNl ?? '' },
                }));
            }, { multiline: true, wide: true, rows: 6, help: 'Eén regel per ontmoeting: NPC-id | missie-id | label Spaans | label Nederlands.' });
        }

        const access = section('Toegang en niveauroutes', 'Koppel ieder niveau aan een bestaande vertakkingsstap.');
        data.runtime_access ??= {};
        const isPublic = data.scene === 'panaderia_text_dialogue';
        selectField(access, 'Zichtbaarheid', data.runtime_access.visibility ?? (isPublic ? 'public' : 'entitled'), isPublic ? [['public', 'Openbaar']] : [['entitled', 'Proefweekrecht vereist']], (value) => {
            data.runtime_access.visibility = value;
            if (value === 'entitled') data.runtime_access.entitlement = 'trial_week';
            if (value === 'public') delete data.runtime_access.entitlement;
        });
        textField(access, 'Recht', data.runtime_access.entitlement ?? '', (value) => data.runtime_access.entitlement = value, { help: 'Voor afgeschermde missies: trial_week.' });
        data.level_branches ??= {};
        ['A0', 'A1', 'A2'].forEach((level) => textField(access, `Route ${level}`, data.level_branches[level], (value) => data.level_branches[level] = value));

        const repair = data.repair ??= { npc_response: {}, feedback: {} };
        repair.npc_response ??= {};
        repair.feedback ??= {};
        const repairBody = section('Herstelstrategie', 'Wat gebeurt er als de speler om herhaling of verduidelijking vraagt?');
        textField(repairBody, 'Herkenningszinnen', lines(repair.terms), (value) => repair.terms = parseLines(value), { multiline: true, wide: true, help: 'Eén Spaanse herstelzin per regel.', lang: 'es' });
        textField(repairBody, 'NPC-reactie Spaans', repair.npc_response.es, (value) => repair.npc_response.es = value, { multiline: true, lang: 'es' });
        textField(repairBody, 'NPC-reactie Nederlands', repair.npc_response.nl, (value) => repair.npc_response.nl = value, { multiline: true });
        textField(repairBody, 'Sterk punt', repair.feedback.strength, (value) => repair.feedback.strength = value, { multiline: true });
        textField(repairBody, 'Eén focus', repair.feedback.focus, (value) => repair.feedback.focus = value, { multiline: true });

        const stepsBody = section('Gespreksroute', 'Alle actieve beurten en niveauvertakkingen. Iedere optie moet naar een bestaande stap of @complete verwijzen.', true);
        stepsBody.classList.remove('md:grid-cols-2');
        const steps = data.steps ??= [];
        arrayHeader(stepsBody, 'Stappen', steps.length, 'Stap toevoegen', () => {
            steps.push({
                id: `turn.new_${steps.length + 1}`,
                turn: steps.length + 1,
                npc_line: { es: 'Nueva pregunta.', nl: 'Nieuwe vraag.' },
                prompt: 'Wat moet de speler communiceren?',
                placeholder: 'Voorbeeldantwoord',
                hint: 'Korte bruikbare hint',
                choices: ['Respuesta de ejemplo.'],
                options: [{
                    id: `answer_${steps.length + 1}`,
                    requirements: [['palabra']],
                    npc_response: { es: 'Muy bien.', nl: 'Heel goed.' },
                    feedback: { strength: 'Je boodschap kwam over.', focus: 'Eén concrete verbetering.' },
                    states: ['step_completed'],
                    next: '@complete',
                }],
                fallback: { strength: 'Je bent begonnen.', focus: 'Noem het belangrijkste kernwoord.' },
            });
            sync(); render();
        });

        steps.forEach((step, index) => {
            step.npc_line ??= {};
            step.fallback ??= {};
            step.options ??= [];
            const card = element('details', 'overflow-hidden rounded-xl border border-slate-200');
            card.open = index === 0;
            const title = element('summary', 'cursor-pointer bg-slate-50 px-4 py-3 font-bold text-slate-900');
            title.textContent = `${step.turn ?? '?'} · ${step.id ?? 'Nieuwe stap'}`;
            card.append(title);
            const grid = element('div', 'grid gap-4 p-4 md:grid-cols-2');
            textField(grid, 'Stap-id', step.id, (value) => { step.id = value; title.textContent = `${step.turn ?? '?'} · ${value}`; });
            textField(grid, 'Beurtnummer', step.turn, (value) => { step.turn = value; title.textContent = `${value} · ${step.id ?? 'Nieuwe stap'}`; }, { type: 'number', min: 1, max: 30, number: true });
            textField(grid, 'NPC-regel Spaans', step.npc_line.es, (value) => step.npc_line.es = value, { multiline: true, lang: 'es' });
            textField(grid, 'NPC-regel Nederlands', step.npc_line.nl, (value) => step.npc_line.nl = value, { multiline: true });
            textField(grid, 'Opdracht', step.prompt, (value) => step.prompt = value, { multiline: true, wide: true });
            textField(grid, 'Invulvoorbeeld', step.placeholder, (value) => step.placeholder = value, { wide: true, lang: 'es' });
            textField(grid, 'Hint', step.hint, (value) => step.hint = value, { multiline: true, wide: true });
            textField(grid, 'Voorbeeldantwoorden', lines(step.choices), (value) => step.choices = parseLines(value), { multiline: true, wide: true, help: 'Eén antwoord per regel.', lang: 'es' });
            textField(grid, 'Fallback · sterk punt', step.fallback.strength, (value) => step.fallback.strength = value, { multiline: true });
            textField(grid, 'Fallback · focus', step.fallback.focus, (value) => step.fallback.focus = value, { multiline: true });
            card.append(grid);

            const options = element('div', 'space-y-4 border-t border-slate-200 bg-slate-50/70 p-4');
            arrayHeader(options, 'Geldige antwoordroutes', step.options.length, 'Routeoptie toevoegen', () => {
                step.options.push({
                    id: `option_${step.options.length + 1}`,
                    requirements: [['palabra']],
                    npc_response: { es: 'Muy bien.', nl: 'Heel goed.' },
                    feedback: { strength: 'Je boodschap kwam over.', focus: 'Eén concrete verbetering.' },
                    states: ['step_completed'],
                    next: '@complete',
                });
                sync(); render();
            });
            step.options.forEach((option, optionIndex) => {
                option.npc_response ??= {};
                option.feedback ??= {};
                const optionCard = element('div', 'overflow-hidden rounded-xl border border-slate-200 bg-white');
                const optionGrid = element('div', 'grid gap-4 p-4 md:grid-cols-2');
                textField(optionGrid, 'Optie-id', option.id, (value) => option.id = value);
                textField(optionGrid, 'Volgende stap', option.next, (value) => option.next = value, { help: 'Bestaande stap-id, @complication of @complete.' });
                textField(optionGrid, 'Herkenningsgroepen', requirements(option.requirements), (value) => option.requirements = parseRequirements(value), { multiline: true, wide: true, help: 'Iedere regel is verplicht; alternatieven op dezelfde regel scheid je met |.', lang: 'es' });
                textField(optionGrid, 'NPC-reactie Spaans', option.npc_response.es, (value) => option.npc_response.es = value, { multiline: true, lang: 'es' });
                textField(optionGrid, 'NPC-reactie Nederlands', option.npc_response.nl, (value) => option.npc_response.nl = value, { multiline: true });
                textField(optionGrid, 'Feedback · sterk punt', option.feedback.strength, (value) => option.feedback.strength = value, { multiline: true });
                textField(optionGrid, 'Feedback · focus', option.feedback.focus, (value) => option.feedback.focus = value, { multiline: true });
                textField(optionGrid, 'Nieuwe toestanden', lines(option.states), (value) => option.states = parseLines(value), { multiline: true, wide: true, help: 'Eén toestandsnaam per regel.' });
                optionCard.append(optionGrid);
                cardActions(optionCard, optionIndex, step.options, render, 'Routeoptie');
                options.append(optionCard);
            });
            card.append(options);
            cardActions(card, index, steps, render, 'Stap');
            stepsBody.append(card);
        });

        const completion = data.completion ??= { default_farewell: {}, rewards: {} };
        completion.default_farewell ??= {};
        completion.polite_farewell ??= {};
        completion.rewards ??= {};
        const completionBody = section('Afronding en beloningen', 'Server-side beloningen blijven begrensd en idempotent.');
        textField(completionBody, 'Afscheid Spaans', completion.default_farewell.es, (value) => completion.default_farewell.es = value, { lang: 'es' });
        textField(completionBody, 'Afscheid Nederlands', completion.default_farewell.nl, (value) => completion.default_farewell.nl = value);
        textField(completionBody, 'Beleefd afscheid Spaans', completion.polite_farewell.es, (value) => completion.polite_farewell.es = value, { lang: 'es' });
        textField(completionBody, 'Beleefd afscheid Nederlands', completion.polite_farewell.nl, (value) => completion.polite_farewell.nl = value);
        ['xp', 'confianza', 'valentia'].forEach((reward) => textField(completionBody, reward, completion.rewards[reward], (value) => completion.rewards[reward] = value, { type: 'number', min: 0, max: reward === 'xp' ? 500 : 10, number: true }));
        [
            ['stamp', 'Paspoortstempel'],
            ['collectible', 'Verzamelvoorwerp'],
            ['repair_badge', 'Herstelbadge'],
        ].forEach(([key, label]) => {
            completion.rewards[key] ??= {};
            textField(completionBody, `${label} Spaans`, completion.rewards[key].es, (value) => completion.rewards[key].es = value, { lang: 'es' });
            textField(completionBody, `${label} Nederlands`, completion.rewards[key].nl, (value) => completion.rewards[key].nl = value);
        });

        if (data.scene === 'health_text_dialogue') {
            const roleplay = data.roleplay ??= { fictional: true, title: {}, facts: [] };
            roleplay.title ??= {};
            const roleBody = section('Fictieve rolkaart', 'Gezondheidsinformatie moet fictief blijven en krijgt altijd onafhankelijke review.', true);
            textField(roleBody, 'Titel Spaans', roleplay.title.es, (value) => roleplay.title.es = value, { lang: 'es' });
            textField(roleBody, 'Titel Nederlands', roleplay.title.nl, (value) => roleplay.title.nl = value);
            textField(roleBody, 'Beschrijving', roleplay.description, (value) => roleplay.description = value, { multiline: true, wide: true });
            textField(roleBody, 'Feiten', Array.isArray(roleplay.facts) ? roleplay.facts.map((fact) => `${fact.es ?? ''} | ${fact.nl ?? ''}`).join('\n') : '', (value) => {
                roleplay.facts = value.split('\n').map((line) => line.split('|').map((part) => part.trim())).filter((parts) => parts[0]).map(([es, nl]) => ({ es, nl: nl ?? '' }));
            }, { multiline: true, wide: true, help: 'Eén fictief Spaans | Nederlands feit per regel.' });
            textField(roleBody, 'Privacywaarschuwing', roleplay.privacy_notice, (value) => roleplay.privacy_notice = value, { multiline: true, wide: true });
            textField(roleBody, 'Medische disclaimer', roleplay.medical_disclaimer, (value) => roleplay.medical_disclaimer = value, { multiline: true, wide: true });
            roleplay.fictional = true;
        }
    };

    const render = () => {
        root.replaceChildren();
        if (data === null) {
            root.append(element('div', 'cs-alert-error', 'De JSON bevat een syntaxisfout. Herstel de geavanceerde JSON voordat de formulierbouwer kan laden.'));
            return;
        }

        const scene = data.scene;
        if (!scene) {
            root.append(element('div', 'rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600', 'Kies bovenaan een speelstarter. Niet-speelbare content gebruikt alleen de algemene velden.'));
            return;
        }

        const heading = element('div', 'rounded-2xl border border-brand-200 bg-brand-50 p-5');
        heading.append(element('p', 'font-bold text-brand-900', scene === 'madrid_hub' ? 'Wereldbouwer actief' : 'Gespreksbouwer actief'));
        heading.append(element('p', 'mt-1 text-sm leading-6 text-brand-800', 'Wijzigingen worden direct naar hetzelfde versieerbare speelcontract geschreven. Opslaan maakt altijd een nieuwe conceptrevisie.'));
        root.append(heading);

        if (scene === 'madrid_hub') renderMadrid();
        else if (scene.endsWith('_text_dialogue')) renderDialogue();
        else root.append(element('div', 'cs-alert-error', `Het contract ${scene} heeft nog geen formulierbouwer.`));
    };

    source.addEventListener('change', () => {
        data = parse();
        render();
    });

    render();
}
