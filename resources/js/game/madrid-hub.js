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
    };
    const explored = new Set(readExplored());

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
        state.textContent = hotspot.state === 'open' ? 'Open' : hotspot.state === 'completed' ? 'Voltooid' : 'Binnenkort';
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

        showPanel({
            kind: isOpen ? 'Open locatie' : 'Vooruitblik',
            title: hotspot.label.es,
            body: hotspot.description,
            language: hotspot.label,
            mission: isOpen,
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
        setText('[data-hub-eyebrow]', data.intro.eyebrow);
        setText('[data-hub-title]', data.intro.title);
        setText('[data-hub-description]', data.intro.description);
        setText('[data-hub-objective]', data.intro.objective);

        elements.hotspots.replaceChildren(...data.hotspots.map((hotspot) => createHotspot(hotspot)));
        elements.locationList.replaceChildren(...data.hotspots.map((hotspot) => createHotspot(hotspot, true)));
        elements.inspectables.replaceChildren(...data.inspectables.map(createInspectable));
        elements.loading.hidden = true;
        elements.error.hidden = true;
        elements.map.hidden = false;
        elements.status.textContent = 'Madrid is klaar. Kies een locatie of onderzoek een detail op de kaart.';
        updateCuriosity();
    };

    async function loadHub() {
        elements.map.hidden = false;
        elements.loading.hidden = false;
        elements.error.hidden = true;

        try {
            const response = await fetch(hub.dataset.source, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

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
        elements.status.textContent = 'De ingang van La Espiga opent in fase 2B. De hub en contentverbinding zijn nu gereed.';
        elements.panel.hidden = true;
    });

    hub.querySelector('[data-hub-retry]')?.addEventListener('click', loadHub);

    hub.querySelector('[data-hub-sound]')?.addEventListener('click', (event) => {
        const button = event.currentTarget;
        const enabled = button.getAttribute('aria-pressed') !== 'true';
        button.setAttribute('aria-pressed', String(enabled));
        setText('[data-sound-label]', enabled ? 'Geluid aan' : 'Geluid uit');
        elements.status.textContent = enabled ? 'Geluid staat aan.' : 'Stille modus staat aan.';
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
