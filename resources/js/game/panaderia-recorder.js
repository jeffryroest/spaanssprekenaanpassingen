const recorderRoot = document.querySelector('[data-speech-recorder]');

if (recorderRoot) {
    const maximumSeconds = Number(recorderRoot.dataset.maximumSeconds || 12);
    const elements = {
        start: recorderRoot.querySelector('[data-record-start]'),
        stop: recorderRoot.querySelector('[data-record-stop]'),
        retry: recorderRoot.querySelector('[data-record-retry]'),
        transcribe: recorderRoot.querySelector('[data-record-transcribe]'),
        timer: recorderRoot.querySelector('[data-recording-timer]'),
        status: recorderRoot.querySelector('[data-recorder-status]'),
        preview: recorderRoot.querySelector('[data-recording-preview]'),
        playback: recorderRoot.querySelector('[data-recording-playback]'),
        transcriptNote: recorderRoot.querySelector('[data-transcript-note]'),
        response: document.querySelector('[data-player-response]'),
    };

    let recorder = null;
    let stream = null;
    let chunks = [];
    let recording = null;
    let recordingUrl = null;
    let recordingStartedAt = 0;
    let recordedSeconds = 0;
    let timerId = null;
    let uploadController = null;

    const setStatus = (message) => {
        elements.status.textContent = message;
    };

    const formatTime = (seconds) => `0:${String(Math.floor(seconds)).padStart(2, '0')} / 0:${String(maximumSeconds).padStart(2, '0')}`;

    const updateTimer = () => {
        const elapsed = Math.min(maximumSeconds, (performance.now() - recordingStartedAt) / 1000);
        elements.timer.textContent = formatTime(elapsed);
        if (elapsed >= maximumSeconds) stopRecording(true);
    };

    const stopTracks = () => {
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const stopRecording = (automatic = false) => {
        if (!recorder || recorder.state !== 'recording') return;

        recordedSeconds = Math.min(maximumSeconds, (performance.now() - recordingStartedAt) / 1000);
        recorder.stop();
        window.clearInterval(timerId);
        timerId = null;
        stopTracks();
        elements.stop.hidden = true;
        setStatus(automatic ? 'De opname is na 12 seconden automatisch gestopt.' : 'Opname gestopt. Luister terug voordat je het transcript maakt.');
    };

    const releaseRecording = () => {
        uploadController?.abort();
        uploadController = null;
        window.clearInterval(timerId);
        timerId = null;
        if (recorder?.state === 'recording') recorder.stop();
        stopTracks();
        if (recordingUrl) URL.revokeObjectURL(recordingUrl);
        recordingUrl = null;
        recording = null;
        chunks = [];
        recorder = null;
        recordedSeconds = 0;
        elements.playback.removeAttribute('src');
        elements.playback.load();
    };

    const resetRecorder = ({ announce = true } = {}) => {
        releaseRecording();
        recorderRoot.dataset.state = 'idle';
        elements.timer.textContent = formatTime(0);
        elements.start.hidden = false;
        elements.start.disabled = !navigator.mediaDevices?.getUserMedia || !window.MediaRecorder || !supportedMimeType();
        elements.stop.hidden = true;
        elements.preview.hidden = true;
        elements.retry.disabled = false;
        elements.transcribe.disabled = false;
        elements.transcriptNote.hidden = true;
        elements.transcriptNote.removeAttribute('data-confidence');
        if (announce) setStatus('De microfoon start pas wanneer jij op opnemen drukt.');
    };

    const supportedMimeType = () => {
        if (!window.MediaRecorder?.isTypeSupported) return null;

        return ['audio/webm;codecs=opus', 'audio/webm']
            .find((type) => window.MediaRecorder.isTypeSupported(type)) ?? null;
    };

    const startRecording = async () => {
        const mimeType = supportedMimeType();
        if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder || !mimeType) {
            setStatus('Deze browser kan geen WebM/Opus opnemen. Typ je antwoord hieronder; je voortgang blijft behouden.');
            return;
        }

        elements.start.disabled = true;
        setStatus('Wacht op je microfoontoestemming…');

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                    channelCount: 1,
                },
            });
            chunks = [];
            recorder = new MediaRecorder(stream, { mimeType });
            recorder.addEventListener('dataavailable', (event) => {
                if (event.data.size > 0) chunks.push(event.data);
            });
            recorder.addEventListener('stop', () => {
                recording = new Blob(chunks, { type: 'audio/webm' });
                recordingUrl = URL.createObjectURL(recording);
                elements.playback.src = recordingUrl;
                elements.preview.hidden = false;
                elements.start.hidden = true;
                recorderRoot.dataset.state = 'ready';
                elements.transcribe.focus({ preventScroll: true });
            }, { once: true });
            recorder.addEventListener('error', () => {
                resetRecorder({ announce: false });
                setStatus('De opname is onderbroken. Probeer opnieuw of typ je antwoord.');
            }, { once: true });

            recorder.start(250);
            recordingStartedAt = performance.now();
            recorderRoot.dataset.state = 'recording';
            elements.start.hidden = true;
            elements.stop.hidden = false;
            elements.timer.textContent = formatTime(0);
            setStatus('Opname actief. Spreek je Spaanse antwoord; je kunt eerder stoppen.');
            timerId = window.setInterval(updateTimer, 100);
            elements.stop.focus({ preventScroll: true });
        } catch (error) {
            stopTracks();
            elements.start.disabled = false;
            const denied = error instanceof DOMException && ['NotAllowedError', 'SecurityError'].includes(error.name);
            setStatus(denied
                ? 'Microfoontoestemming is geweigerd. Typ je antwoord hieronder; de missie blijft volledig speelbaar.'
                : 'De microfoon kon niet worden gestart. Probeer opnieuw of typ je antwoord.');
        }
    };

    const transcribeRecording = async () => {
        if (!recording || recordedSeconds < 0.2) {
            setStatus('De opname is te kort. Neem je Spaanse zin opnieuw op.');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const body = new FormData();
        body.append('audio', recording, 'spreekpoging.webm');
        body.append('duration_seconds', recordedSeconds.toFixed(2));
        uploadController = new AbortController();
        recorderRoot.dataset.state = 'uploading';
        elements.retry.disabled = true;
        elements.transcribe.disabled = true;
        setStatus('Opname wordt veilig verzonden en in het Spaans getranscribeerd…');

        try {
            const response = await fetch(recorderRoot.dataset.transcriptionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body,
                credentials: 'same-origin',
                signal: uploadController.signal,
            });
            const payload = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(payload?.error?.message || payload?.message || 'Het transcript kon niet worden gemaakt.');
            }

            const transcript = payload?.data?.transcript?.trim();
            if (!transcript) throw new Error('Het transcript is leeg. Neem je zin opnieuw op.');

            elements.response.value = transcript;
            elements.response.dataset.responseSource = 'speech';
            const confidenceStatus = payload.data.confidence_status || 'unavailable';
            elements.transcriptNote.dataset.confidence = confidenceStatus;
            elements.transcriptNote.textContent = confidenceStatus === 'low'
                ? 'Controleer het transcript goed. De herkenning is onzeker en telt daarom nooit als uitspraakfout.'
                : 'Transcript ingevuld. Controleer de tekst en verbeter herkenningsfouten voordat je het antwoord gebruikt.';
            elements.transcriptNote.hidden = false;
            recorderRoot.dataset.state = 'transcribed';
            setStatus('Transcript klaar en hieronder ingevuld. Jij houdt de controle: pas het zo nodig aan.');
            recorderRoot.dispatchEvent(new CustomEvent('scenario:transcript-ready', {
                bubbles: true,
                detail: { confidenceStatus, transcript },
            }));
            elements.response.focus({ preventScroll: true });
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') return;
            recorderRoot.dataset.state = 'ready';
            setStatus(error instanceof Error ? `${error.message} Je opname blijft lokaal beschikbaar.` : 'Transcriptie mislukt. Probeer opnieuw of gebruik tekst.');
        } finally {
            uploadController = null;
            elements.retry.disabled = false;
            elements.transcribe.disabled = false;
        }
    };

    elements.start.addEventListener('click', startRecording);
    elements.stop.addEventListener('click', () => stopRecording(false));
    elements.retry.addEventListener('click', () => {
        resetRecorder();
        elements.start.focus({ preventScroll: true });
    });
    elements.transcribe.addEventListener('click', transcribeRecording);
    document.addEventListener('scenario:turn-changed', () => resetRecorder({ announce: false }));
    window.addEventListener('beforeunload', releaseRecording);

    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder || !supportedMimeType()) {
        elements.start.disabled = true;
        setStatus('WebM/Opus-opname is niet beschikbaar in deze browser. Gebruik de tekstinvoer hieronder.');
    }
}
