const hub = document.querySelector('[data-madrid-hub]');

if (hub) {
    const elements = {
        map: hub.querySelector('[data-hub-map]'),
        loading: hub.querySelector('[data-hub-loading]'),
        error: hub.querySelector('[data-hub-error]'),
        status: hub.querySelector('[data-hub-status]'),
        panel: hub.querySelector('[data-hub-panel]'),
        hotspots: hub.querySelector('[data-hub-hotspots]'),
        inspectables: hub.querySelector('[data-hub-inspectables]'),
        locationList: hub.querySelector('[data-hub-location-list]'),
        listView: hub.querySelector('[data-hub-list-view]'),
        arrival: hub.querySelector('[data-hub-arrival]'),
        preparation: hub.querySelector('[data-hub-preparation]'),
    };
    const explored = new Set(readExplored());
    let accountProgress = null;
    let accountProgressUnavailable = false;
    let trialWeekDays = [];
    let currentHubData = null;
    let currentMissionKind = null;
    let ambience = null;

    const setText = (selector, value) => {
        const element = hub.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    };

    const validateHub = (data) => {
        if (data?.schema_version !== '1.0.0' || data?.scene !== 'madrid_hub') {
            throw new Error('De gepubliceerde wereld gebruikt niet het Madrid-hubcontract v1.');
        }

        if (!Array.isArray(data.hotspots) || data.hotspots.length < 4) {
            throw new Error('De Madrid-hub bevat minder dan vier locaties.');
        }

        if (!Array.isArray(data.inspectables) || data.inspectables.length < 3) {
            throw new Error('De Madrid-hub bevat minder dan drie onderzoekspunten.');
        }

        const hasValidPosition = ({ position }) => Number.isFinite(position?.x)
            && Number.isFinite(position?.y)
            && position.x >= 5
            && position.x <= 95
            && position.y >= 8
            && position.y <= 88;
        const hasValidLabel = ({ label }) => typeof label?.es === 'string' && typeof label?.nl === 'string';

        if (!data.hotspots.every((hotspot) => hasValidPosition(hotspot) && hasValidLabel(hotspot))) {
            throw new Error('Een locatie op de Madrid-kaart heeft ongeldige tekst of coördinaten.');
        }

        if (!data.inspectables.every(hasValidPosition)) {
            throw new Error('Een onderzoekspunt op de Madrid-kaart heeft ongeldige coördinaten.');
        }

        return data;
    };

    const createHotspot = (hotspot, listMode = false) => {
        const item = document.createElement('li');
        const button = document.createElement('button');
        const icon = document.createElement('span');
        const copy = document.createElement('span');
        const title = document.createElement('strong');
        const translation = document.createElement('small');
        const state = document.createElement('span');

        button.type = 'button';
        button.className = listMode ? 'hub-list-button' : `hub-hotspot hub-hotspot-${hotspot.kind}`;
        button.dataset.hotspotId = hotspot.id;
        button.setAttribute('aria-label', `${hotspot.label.es}. ${hotspot.label.nl}. ${hotspot.description}`);

        if (!listMode) {
            button.style.setProperty('--hub-x', `${hotspot.position.x}%`);
            button.style.setProperty('--hub-y', `${hotspot.position.y}%`);
        }

        icon.className = 'hub-hotspot-icon';
        icon.textContent = hotspotIcon(hotspot.kind);
        icon.setAttribute('aria-hidden', 'true');
        copy.className = 'hub-hotspot-copy';
        title.lang = 'es';
        title.textContent = hotspot.label.es;
        translation.textContent = hotspot.label.nl;
        state.className = `hub-hotspot-state hub-hotspot-state-${hotspot.state}`;
        state.textContent = hotspot.state === 'open'
            ? 'Open'
            : hotspot.state === 'completed'
                ? 'Voltooid'
                : hotspot.state === 'preview'
                    ? 'Vooruitblik'
                    : 'Binnenkort';
        copy.append(title, translation);
        button.append(icon, copy, state);
        button.addEventListener('click', () => openHotspot(hotspot));
        item.append(button);

        return item;
    };

    const createInspectable = (inspectable) => {
        const item = document.createElement('li');
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'hub-inspectable';
        button.style.setProperty('--hub-x', `${inspectable.position.x}%`);
        button.style.setProperty('--hub-y', `${inspectable.position.y}%`);
        button.dataset.inspectableId = inspectable.id;
        button.setAttribute('aria-label', `Onderzoek: ${inspectable.title}`);
        button.textContent = explored.has(inspectable.id) ? '✓' : '+';
        button.addEventListener('click', () => openInspectable(inspectable, button));
        item.append(button);

        return item;
    };

    const openHotspot = (hotspot) => {
        const isOpen = ['open', 'completed'].includes(hotspot.state);
        currentMissionKind = isOpen && ['bakery', 'cafe'].includes(hotspot.kind) ? hotspot.kind : null;
        setText('[data-hub-mission-label]', currentMissionKind === 'cafe' ? 'Ga Café El Reloj binnen' : 'Bereid je bakkerijmissie voor');

        showPanel({
            kind: hotspot.state === 'completed' ? 'Missie voltooid' : isOpen ? 'Open locatie' : 'Vooruitblik',
            title: hotspot.label.es,
            body: hotspot.description,
            language: hotspot.label,
            mission: currentMissionKind !== null,
            reward: false,
        });
        elements.status.textContent = isOpen
            ? `${hotspot.label.nl} is klaar om te ontdekken.`
            : `${hotspot.label.nl} wordt in een volgende missie ontgrendeld.`;
    };

    const openInspectable = (inspectable, button) => {
        const isNew = !explored.has(inspectable.id);

        if (isNew) {
            explored.add(inspectable.id);
            writeExplored([...explored]);
            button.textContent = '✓';
        }

        showPanel({
            kind: inspectable.kind === 'vocabulary' ? 'Woorden ontdekken' : inspectable.kind === 'culture' ? 'Madrid ontdekken' : 'Luisteren',
            title: inspectable.title,
            body: inspectable.body,
            language: inspectable.language,
            items: inspectable.items,
            mission: false,
            reward: isNew && inspectable.reward?.curiosidad > 0,
        });
        updateCuriosity();
        elements.status.textContent = isNew
            ? `${inspectable.title} ontdekt. Je verdient één punt Curiosidad.`
            : `${inspectable.title} opnieuw geopend.`;
    };

    const showPanel = ({ kind, title, body, language, items = [], mission, reward }) => {
        setText('[data-hub-panel-kind]', kind);
        setText('[data-hub-panel-title]', title);
        setText('[data-hub-panel-body]', body);

        const languageCard = hub.querySelector('[data-hub-language]');
        languageCard.hidden = !language;
        if (language) {
            setText('[data-hub-language-es]', language.es);
            setText('[data-hub-language-nl]', language.nl);
        }

        const wordList = hub.querySelector('[data-hub-word-list]');
        wordList.replaceChildren(...items.map((word) => {
            const item = document.createElement('li');
            const spanish = document.createElement('strong');
            const dutch = document.createElement('span');
            spanish.lang = 'es';
            spanish.textContent = word.es;
            dutch.textContent = word.nl;
            item.append(spanish, dutch);
            return item;
        }));

        hub.querySelector('[data-hub-panel-reward]').hidden = !reward;
        hub.querySelector('[data-hub-mission-button]').hidden = !mission;
        elements.panel.hidden = false;
        elements.panel.focus({ preventScroll: true });
    };

    const updateCuriosity = () => {
        const score = Math.min(explored.size, 3);
        setText('[data-curiosity-score]', String(score));
        setText('[data-hub-progress]', `${score} van 3 ontdekt`);
    };

    const renderHub = (data) => {
        currentHubData = data;
        setText('[data-hub-eyebrow]', data.intro.eyebrow);
        setText('[data-hub-title]', data.intro.title);
        setText('[data-hub-description]', data.intro.description);
        setText('[data-hub-objective]', data.intro.objective);

        const hotspots = data.hotspots.map(applyAccountState);
        elements.hotspots.replaceChildren(...hotspots.map((hotspot) => createHotspot(hotspot)));
        elements.locationList.replaceChildren(...hotspots.map((hotspot) => createHotspot(hotspot, true)));
        elements.inspectables.replaceChildren(...data.inspectables.map(createInspectable));
        elements.loading.hidden = true;
        elements.error.hidden = true;
        elements.map.hidden = false;
        elements.status.textContent = accountProgressUnavailable
            ? 'Madrid is klaar. Je accounttotalen konden tijdelijk niet worden geladen; de kaart blijft volledig werken.'
            : 'Madrid is klaar. Kies een locatie of onderzoek een detail op de kaart.';
        updateCuriosity();
        showArrival(data);
    };

    const showArrival = (data) => {
        if (!elements.arrival || readSessionValue('madrid-hub-arrival-seen') === 'true') return;

        setText('[data-hub-arrival-description]', data.intro.description);
        openDialog(elements.arrival);
    };

    const openPreparation = () => {
        if (!elements.preparation || !currentHubData) return;

        const vocabulary = currentHubData.inspectables.find(({ kind }) => kind === 'vocabulary');
        const products = Array.isArray(vocabulary?.items) ? vocabulary.items : [];
        const bread = products.find(({ es }) => /pan|barra/i.test(es)) ?? products[0];
        const sweets = products.filter((product) => product !== bread);
        const choiceList = hub.querySelector('[data-hub-sweet-choices]');

        setText('[data-hub-preparation-objective]', currentHubData.intro.objective);
        setText('[data-hub-bread-choice]', bread?.es ?? 'el pan');
        choiceList.replaceChildren(...sweets.map((product, index) => {
            const label = document.createElement('label');
            const input = document.createElement('input');
            const copy = document.createElement('span');
            const spanish = document.createElement('strong');
            const dutch = document.createElement('small');

            input.type = 'radio';
            input.name = 'sweet-choice';
            input.value = product.es;
            input.dataset.translation = product.nl;
            input.checked = index === 0;
            spanish.lang = 'es';
            spanish.textContent = product.es;
            dutch.textContent = product.nl;
            copy.append(spanish, dutch);
            label.append(input, copy);

            return label;
        }));
        openDialog(elements.preparation);
    };

    const applyAccountState = (hotspot) => {
        const mission = accountProgress?.mission;
        const locallyCompleted = readDialogueCompletion();
        if (hotspot.id === 'madrid.panaderia' && (mission?.status === 'completed' || locallyCompleted)) {
            return {
                ...hotspot,
                state: 'completed',
                description: `${hotspot.description} Je bestelling is voltooid; vrij oefenen is ontgrendeld.`,
            };
        }

        const restaurantDay = trialWeekDays.find(({ day }) => day === 3);
        if (hotspot.id === 'madrid.cafe.reloj' && restaurantDay?.action_url) {
            return {
                ...hotspot,
                state: restaurantDay.access_state === 'completed' ? 'completed' : 'open',
                description: restaurantDay.access_state === 'completed'
                    ? 'Je diner met Carmen is voltooid. De tafel blijft beschikbaar om opnieuw te oefenen.'
                    : 'Open · vraag Carmen om een tafel en bestel je diner in het Spaans.',
            };
        }

        if (hotspot.id === 'madrid.cafe.reloj' && (mission?.states?.includes('madrid.cafe.preview_unlocked') || locallyCompleted)) {
            return {
                ...hotspot,
                state: 'preview',
                description: 'Je hebt een vooruitblik ontgrendeld. In de volgende missie oefen je bestellen en sociale taal in Café El Reloj.',
            };
        }

        return hotspot;
    };

    const loadTrialWeekStatus = async () => {
        if (hub.dataset.authenticated !== 'true') return;

        try {
            const response = await fetch(hub.dataset.trialWeekUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;

            const payload = await response.json();
            if (payload?.schema_version === '1.0.0' && Array.isArray(payload?.data?.days)) {
                trialWeekDays = payload.data.days;
            }
        } catch {
            // De openbare Madrid-wereld blijft bruikbaar zonder proefweekstatus.
        }
    };

    const loadAccountProgress = async () => {
        if (hub.dataset.authenticated !== 'true') return;

        try {
            const response = await fetch(hub.dataset.progressUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`Accountvoortgang niet beschikbaar (${response.status}).`);

            const payload = await response.json();
            if (payload?.schema_version !== '1.0.0'
                || payload?.meta?.audio_included !== false
                || payload?.meta?.transcript_included !== false
                || payload?.meta?.feedback_included !== false
                || !payload?.data?.balances) {
                throw new Error('Onveilig accountvoortgangscontract.');
            }

            accountProgress = payload.data;
            setText('[data-account-xp]', String(payload.data.balances.xp));
            setText('[data-account-confianza]', String(payload.data.balances.confianza));
            setText('[data-account-valentia]', String(payload.data.balances.valentia));
        } catch {
            accountProgressUnavailable = true;
        }
    };

    async function loadHub() {
        elements.map.hidden = false;
        elements.loading.hidden = false;
        elements.error.hidden = true;

        try {
            const [response] = await Promise.all([
                fetch(hub.dataset.source, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                }),
                loadAccountProgress(),
                loadTrialWeekStatus(),
            ]);

            if (!response.ok) {
                throw new Error(`Madrid kon niet worden geladen (${response.status}).`);
            }

            const payload = await response.json();
            renderHub(validateHub(payload?.data?.content?.domain_data));
        } catch (error) {
            elements.loading.hidden = true;
            elements.map.hidden = true;
            elements.error.hidden = false;
            elements.status.textContent = error instanceof Error ? error.message : 'Madrid kon niet worden geladen.';
        }
    }

    hub.querySelector('[data-hub-panel-close]')?.addEventListener('click', () => {
        elements.panel.hidden = true;
    });

    hub.querySelector('[data-hub-mission-button]')?.addEventListener('click', () => {
        if (currentMissionKind === 'cafe') {
            elements.status.textContent = 'Je tafel in Café El Reloj wordt klaargemaakt.';
            window.location.assign(hub.dataset.restaurantRoute);
            return;
        }

        elements.status.textContent = 'Je maakt eerst je boodschappenkaart klaar.';
        openPreparation();
    });

    hub.querySelector('[data-hub-arrival-continue]')?.addEventListener('click', () => {
        writeSessionValue('madrid-hub-arrival-seen', 'true');
        elements.arrival?.close();
        elements.map?.focus({ preventScroll: true });
    });

    hub.querySelector('[data-hub-preparation-close]')?.addEventListener('click', () => {
        elements.preparation?.close();
    });

    hub.querySelector('[data-hub-enter-bakery]')?.addEventListener('click', () => {
        const sweet = hub.querySelector('[name="sweet-choice"]:checked');
        writeSessionValue('madrid-mission-preparation', JSON.stringify({
            bread: hub.querySelector('[data-hub-bread-choice]')?.textContent ?? 'el pan',
            sweet: sweet?.value ?? '',
            sweetTranslation: sweet?.dataset.translation ?? '',
        }));
        elements.status.textContent = 'Je boodschappenkaart is klaar. De deur van La Espiga gaat open.';
        window.location.assign(hub.dataset.panaderiaRoute);
    });

    hub.querySelector('[data-hub-retry]')?.addEventListener('click', loadHub);

    hub.querySelector('[data-hub-sound]')?.addEventListener('click', (event) => {
        const button = event.currentTarget;
        const enabled = button.getAttribute('aria-pressed') !== 'true';
        button.setAttribute('aria-pressed', String(enabled));
        setText('[data-sound-label]', enabled ? 'Geluid aan' : 'Geluid uit');
        if (enabled) {
            ambience = createMadridAmbience();
            elements.status.textContent = 'Zachte straatambiance staat aan. Er zit geen essentiële informatie in het geluid.';
        } else {
            ambience?.stop();
            ambience = null;
            elements.status.textContent = 'Stille modus staat aan.';
        }
    });

    hub.querySelector('[data-hub-view]')?.addEventListener('click', (event) => {
        const button = event.currentTarget;
        const listOpen = button.getAttribute('aria-pressed') !== 'true';
        button.setAttribute('aria-pressed', String(listOpen));
        elements.listView.hidden = !listOpen;
        elements.map.hidden = listOpen;
        setText('[data-view-label]', listOpen ? 'Kaartweergave' : 'Lijstweergave');
        if (listOpen) {
            elements.listView.querySelector('h2')?.focus({ preventScroll: true });
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !elements.panel.hidden) {
            elements.panel.hidden = true;
        }
    });

    loadHub();
}

function openDialog(dialog) {
    if (dialog.open) return;

    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
    } else {
        dialog.setAttribute('open', '');
    }
}

function createMadridAmbience() {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return { stop() {} };

    const context = new AudioContext();
    const output = context.createGain();
    const filter = context.createBiquadFilter();
    const buffer = context.createBuffer(1, context.sampleRate * 3, context.sampleRate);
    const samples = buffer.getChannelData(0);

    for (let index = 0; index < samples.length; index += 1) {
        samples[index] = (Math.random() * 2 - 1) * 0.22;
    }

    const source = context.createBufferSource();
    source.buffer = buffer;
    source.loop = true;
    filter.type = 'lowpass';
    filter.frequency.value = 750;
    output.gain.value = 0.035;
    source.connect(filter).connect(output).connect(context.destination);
    source.start();

    return {
        stop() {
            source.stop();
            context.close();
        },
    };
}

function hotspotIcon(kind) {
    return {
        bakery: '🥖',
        metro: 'M',
        cafe: '☕',
        market: '▦',
    }[kind] ?? '•';
}

function readExplored() {
    try {
        return JSON.parse(window.sessionStorage.getItem('madrid-hub-explored') ?? '[]');
    } catch {
        return [];
    }
}

function writeExplored(ids) {
    try {
        window.sessionStorage.setItem('madrid-hub-explored', JSON.stringify(ids));
    } catch {
        // De hub blijft werken wanneer sessieopslag door de browser is uitgeschakeld.
    }
}

function readSessionValue(key) {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function writeSessionValue(key, value) {
    try {
        window.sessionStorage.setItem(key, value);
    } catch {
        // Tijdelijke keuzes blijven optioneel wanneer sessieopslag niet beschikbaar is.
    }
}

function readDialogueCompletion() {
    try {
        const state = JSON.parse(window.sessionStorage.getItem('panaderia-text-dialogue-v1') ?? 'null');

        return state?.completed === true;
    } catch {
        return false;
    }
}
