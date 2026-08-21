// alle80/griglia — speech to text. Two modes, chosen by the server (window.GRIGLIA_SPEECH.mode):
//  - 'server'  : record with MediaRecorder, upload to GRIGLIA_SPEECH.url, the AI SDK transcribes (best quality);
//  - 'browser' : the browser's Web Speech API (free; phones restart the session at every pause, handled).
//
// The dictation lives HERE, in the module, and not inside the Alpine component (task 431): the mic sits in
// a Livewire view, and any re-render (a broadcast from another device, the agent updating a task) morphs the
// DOM and used to destroy the component with the recorder still running — five minutes of dictation lost
// without a single error message. Alpine is only the view now: it re-adopts the running session and keeps
// the resolver of the target field fresh. Nothing is dropped in silence either: an upload that fails keeps
// its audio for a retry, a transcript with no field to write into waits for the field to come back, and a
// microphone that hears nothing says so instead of letting you talk to a dead mic.
//
// Alpine data used by <x-griglia::mic>: window.grigliaMic(getTarget, lang) → { supported, on, busy, hint, toggle() }
const SR = typeof window !== 'undefined' ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;
const cfg = () => window.GRIGLIA_SPEECH || {};
const canRecord = () => typeof window !== 'undefined' && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
const msg = (key, fallback) => cfg()[key] || fallback;
const toast = (text, type) => { if (text) window.dispatchEvent(new CustomEvent('toast', { detail: { message: text, type: type || 'error', duration: 6000 } })); };

const SILENCE_MS = 12000;   // no sound for this long → warn, instead of recording twelve minutes of nothing
const MAX_RETRIES = 3;      // automatic retries of a failed upload (the audio is kept in memory)

/** The one dictation session: only one field at a time, and it survives every re-render of the page. */
const S = {
  mode: 'browser',
  on: false,          // recording (server) / listening (browser)
  busy: false,        // uploading + transcribing
  error: '',          // shown on the button until the next start: no self-erasing errors
  retry: false,       // the audio of a failed upload is kept: another tap on the mic retries it
  tries: 0,
  key: '',            // identifies the dictated field across re-renders
  getEl: null,        // resolver of the field, refreshed by the live Alpine instance
  lang: '',
  startedAt: 0,
  lastSoundAt: 0,
  silent: false,
  silentSaid: false,
  blob: null,         // audio waiting to be (re)transcribed
  text: '',           // transcript that found no field: delivered when the field comes back
  stream: null, mr: null, chunks: [], rec: null, base: '',
  audio: null, meter: null, ticker: null,
  starting: false,   // getUserMedia is async: a second tap must not open a second recorder
};

/** Stable name of a field across Livewire re-renders (the wire:model wins: it is what identifies it). */
function fieldKey(el) {
  if (!el) return '';
  const wire = Array.from(el.attributes || []).find((a) => a.name.indexOf('wire:model') === 0);
  return (wire ? wire.name + '=' + wire.value : '') || el.name || el.id || el.tagName + ':' + (el.placeholder || '');
}

/** The field as it is right now: after a morph the old node is detached and writing into it loses the text. */
function liveEl() {
  const el = typeof S.getEl === 'function' ? S.getEl() : null;
  return el && el.isConnected ? el : null;
}

function append(el, text) {
  const said = (text || '').trim();
  if (!said) return;
  const base = el.value;
  const sep = base && !/\s$/.test(base) ? ' ' : '';
  el.value = base + sep + said;
  el.dispatchEvent(new Event('input', { bubbles: true }));
  if (el.tagName === 'TEXTAREA') el.scrollTop = el.scrollHeight;
}

function fail(text, type) {
  S.error = text;
  toast(text, type || 'error');
}

/** Write the transcript into the field, or keep it until the field is back on the page. */
function deliver(text) {
  const said = (text || '').trim();
  if (!said) { fail(msg('empty', 'Nothing was recorded'), 'info'); return; }
  const el = liveEl();
  if (!el) { S.text = S.text ? S.text + ' ' + said : said; toast(msg('kept', 'Dictated text kept: reopen the field to insert it'), 'info'); return; }
  append(el, said);
  el.focus();
}

/** The field came back (modal reopened, re-render finished): insert what was waiting for it. */
function flush(el) {
  if (!S.text || !el) return;
  append(el, S.text);
  S.text = '';
  el.focus();
  toast(msg('recovered', 'Dictated text recovered'), 'success');
}

function pickMime() {
  const c = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/wav'];
  return c.find((m) => window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(m)) || '';
}

// ----- is the microphone actually hearing something? -----
function watchLevel(stream) {
  try {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return;
    const ctx = new Ctx();
    const analyser = ctx.createAnalyser();
    analyser.fftSize = 512;
    ctx.createMediaStreamSource(stream).connect(analyser);
    const buf = new Uint8Array(analyser.fftSize);
    S.audio = ctx;
    S.meter = setInterval(() => {
      analyser.getByteTimeDomainData(buf);
      let peak = 0;
      for (let i = 0; i < buf.length; i++) { const v = Math.abs(buf[i] - 128); if (v > peak) peak = v; }
      if (peak / 128 > 0.02) S.lastSoundAt = Date.now();
    }, 300);
  } catch (e) {
    // no Web Audio: we simply cannot tell silence from a dead microphone
  }
}

function stopLevel() {
  if (S.meter) { clearInterval(S.meter); S.meter = null; }
  if (S.audio) { try { S.audio.close(); } catch (e) {} S.audio = null; }
}

function stopStream() {
  if (S.stream) { S.stream.getTracks().forEach((t) => t.stop()); S.stream = null; }
}

/** Heartbeat of a running session: silence warning and duration limit. */
function startTicking() {
  if (S.ticker) return;
  S.ticker = setInterval(() => {
    if (!S.on) { clearInterval(S.ticker); S.ticker = null; S.silent = false; S.silentSaid = false; return; }
    const now = Date.now();
    S.silent = S.lastSoundAt > 0 && now - S.lastSoundAt > SILENCE_MS;
    if (S.silent && !S.silentSaid) { S.silentSaid = true; toast(msg('silent', 'The microphone is not picking up any sound'), 'info'); }
    if (!S.silent) S.silentSaid = false;
    const max = Number(cfg().max_seconds || 0);
    if (max > 0 && (now - S.startedAt) / 1000 >= max) { toast(msg('limit', 'Time limit reached: transcribing what you said'), 'info'); stop(); }
  }, 500);
}

// ----- server mode: record → upload → append -----
async function startRecording() {
  S.error = ''; S.retry = false; S.tries = 0; S.blob = null; S.starting = true;
  let stream;
  try {
    stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  } catch (e) {
    console.error('griglia speech:', e);
    S.on = false; S.starting = false;
    fail(msg('denied', 'Microphone not available'));
    return;
  }
  const mime = pickMime();
  let mr;
  try {
    mr = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
  } catch (e) {
    console.error('griglia speech:', e);
    stream.getTracks().forEach((t) => t.stop());
    S.on = false; S.starting = false;
    fail(msg('denied', 'Microphone not available'));
    return;
  }
  S.chunks = [];
  mr.ondataavailable = (e) => { if (e.data && e.data.size) S.chunks.push(e.data); };
  mr.onerror = (e) => { console.error('griglia speech:', e); };
  mr.onstop = () => {
    stopStream();
    stopLevel();
    const blob = new Blob(S.chunks, { type: mr.mimeType || mime || 'audio/webm' });
    S.chunks = [];
    S.on = false;
    if (!blob.size) { fail(msg('empty', 'Nothing was recorded')); return; }   // used to be a silent `return`
    S.blob = blob;
    S.tries = 0;
    upload();
  };
  mr.start(1000);
  S.starting = false;
  S.mode = 'server';
  S.mr = mr;
  S.stream = stream;
  S.on = true;
  S.startedAt = Date.now();
  S.lastSoundAt = Date.now();
  watchLevel(stream);
  startTicking();
  const track = stream.getAudioTracks()[0];
  if (track) {
    track.onended = () => { if (S.on) { toast(msg('lost', 'The microphone was taken by another app'), 'error'); stop(); } };
  }
  const el = liveEl();
  if (el) el.focus();
}

function stopRecording() {
  S.on = false;
  if (S.mr && S.mr.state !== 'inactive') { try { S.mr.stop(); } catch (e) {} }
  else { stopStream(); stopLevel(); }
  S.mr = null;
}

async function upload() {
  const blob = S.blob;
  if (!blob || S.busy) return;
  S.busy = true;
  S.error = '';
  S.retry = false;
  S.tries += 1;
  try {
    const type = blob.type || '';
    const ext = type.includes('mp4') ? 'mp4' : type.includes('ogg') ? 'ogg' : type.includes('wav') ? 'wav' : 'webm';
    const fd = new FormData();
    fd.append('audio', blob, 'speech.' + ext);
    fd.append('lang', S.lang || cfg().lang || '');
    const r = await fetch(cfg().url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': cfg().csrf, Accept: 'application/json' } });
    if (r.status === 419 || r.status === 401 || r.status === 403) {
      const expired = new Error('session expired');
      expired.expired = true;
      throw expired;
    }
    const j = await r.json().catch(() => ({}));
    if (!r.ok || !j.ok) throw new Error(j.error || ('HTTP ' + r.status));
    S.blob = null;
    S.tries = 0;
    deliver(j.text);
  } catch (e) {
    console.error('griglia speech:', e);
    S.retry = true;   // the audio stays in S.blob: tapping the mic (or coming back to the tab) tries again
    fail(e && e.expired
      ? msg('expired', 'Session expired: reload the page, the recording is still here')
      : msg('retry', 'Transcription failed: tap the microphone to try again'));
  } finally {
    S.busy = false;
  }
}

// ----- browser mode: Web Speech API -----
function startRecognition() {
  if (!SR) return;
  const el = liveEl();
  if (!el) return;
  S.error = '';
  const rec = new SR();
  rec.lang = S.lang || cfg().lang || document.documentElement.lang || navigator.language || 'en-US';
  if (rec.lang.length === 2) rec.lang = { it: 'it-IT', en: 'en-US', fr: 'fr-FR', de: 'de-DE', es: 'es-ES', pt: 'pt-PT', nl: 'nl-NL' }[rec.lang] || rec.lang;
  rec.continuous = true;
  rec.interimResults = true;
  S.base = el.value;
  rec.onresult = (ev) => {
    let finalText = '', interim = '';
    for (let i = 0; i < ev.results.length; i++) {
      const t = ev.results[i][0].transcript;
      if (ev.results[i].isFinal) finalText += t; else interim += t;
    }
    const said = (finalText + interim).trim();
    if (!said) return;
    S.lastSoundAt = Date.now();
    const target = liveEl();
    if (!target) {   // the field went away mid-sentence: keep the text instead of writing into a dead node
      S.text = said;
      stopRecognition();
      toast(msg('kept', 'Dictated text kept: reopen the field to insert it'), 'info');
      return;
    }
    const sep = S.base && !/\s$/.test(S.base) ? ' ' : '';
    target.value = S.base + sep + said;
    target.dispatchEvent(new Event('input', { bubbles: true }));
    if (target.tagName === 'TEXTAREA') target.scrollTop = target.scrollHeight;
  };
  rec.onerror = (ev) => {
    const err = ev && ev.error;
    if (err === 'not-allowed' || err === 'service-not-allowed') { stopRecognition(); fail(msg('denied', 'Microphone not available')); }
    else if (err === 'audio-capture') { stopRecognition(); fail(msg('lost', 'The microphone was taken by another app')); }
    // 'no-speech', 'network', 'aborted': transient, onend restarts the session
  };
  // Phones end a continuous session after every pause: keep what was dictated (new base) and restart
  rec.onend = () => {
    if (!S.on) return;
    const target = liveEl();
    if (!target) { S.on = false; return; }
    S.base = target.value;
    try {
      rec.start();
    } catch (e) {
      setTimeout(() => {
        if (!S.on) return;
        try { rec.start(); } catch (e2) { S.on = false; fail(msg('retry', 'Dictation stopped')); }
      }, 250);
    }
  };
  try {
    rec.start();
    S.mode = 'browser';
    S.rec = rec;
    S.on = true;
    S.startedAt = Date.now();
    S.lastSoundAt = Date.now();
    startTicking();
    el.focus();
  } catch (e) {
    console.error('griglia speech:', e);
    S.on = false;
    fail(msg('denied', 'Microphone not available'));
  }
}

function stopRecognition() {
  S.on = false;
  if (S.rec) { try { S.rec.stop(); } catch (e) {} S.rec = null; }
}

function stop() {
  S.mode === 'server' ? stopRecording() : stopRecognition();
}

// ----- page-level guards (registered once, not once per mic button) -----
if (typeof document !== 'undefined' && !window.__grigliaDictationGuards) {
  window.__grigliaDictationGuards = true;
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { if (S.on) stop(); return; }
    // an upload that failed while the tab was in the background: try again now that we are back
    if (S.blob && S.retry && !S.busy && S.tries < MAX_RETRIES) upload();
  });
  window.addEventListener('beforeunload', (e) => {
    if (!S.on && !S.busy && !S.blob && !S.text) return;
    e.preventDefault();
    e.returnValue = '';   // browsers show their own wording: «changes you made may not be saved»
  });
}

window.grigliaMic = function (getTarget, lang) {
  const serverMode = cfg().mode === 'server' && canRecord();
  return {
    supported: serverMode || !!SR,
    mode: serverMode ? 'server' : 'browser',
    on: false,
    busy: false,
    error: '',
    silent: false,
    seconds: 0,
    poll: null,

    target() { return typeof getTarget === 'function' ? getTarget() : getTarget; },
    key() { return fieldKey(this.target()); },
    mine() { return S.key !== '' && S.key === this.key(); },

    get clock() { return Math.floor(this.seconds / 60) + ':' + String(this.seconds % 60).padStart(2, '0'); },
    get hint() {
      if (this.error) return this.error;
      if (this.busy) return msg('busy', 'Transcribing…');
      if (this.on) return (this.silent ? msg('silent', '') : msg('stop', 'Stop dictation')) + ' — ' + this.clock;
      return msg('start', 'Dictate');
    },

    // The component is rebuilt at every Livewire morph: it re-reads the shared session instead of owning it.
    init() {
      this.sync();
      this.poll = setInterval(() => {
        if (!this.$el.isConnected) { clearInterval(this.poll); return; }
        this.sync();
      }, 400);
    },
    destroy() { clearInterval(this.poll); },

    sync() {
      const mine = this.mine();
      const el = this.target();
      if (mine) S.getEl = () => this.target();   // keep the resolver on the field of the CURRENT render
      if (mine && S.text && !S.on && !S.busy && el && el.isConnected) flush(el);
      this.on = mine && S.on;
      this.busy = mine && S.busy;
      this.error = mine ? S.error : '';
      this.silent = mine && S.silent;
      this.seconds = this.on ? Math.floor((Date.now() - S.startedAt) / 1000) : 0;
    },

    toggle() {
      if (S.busy || S.starting) return;
      const el = this.target();
      if (!el) return;
      if (this.mine() && S.blob && S.retry) { upload(); this.sync(); return; }   // retry the failed transcription
      if (this.on) { stop(); this.sync(); return; }
      if (S.on) stop();                                                          // another field was being dictated
      S.key = this.key();
      S.getEl = () => this.target();
      S.lang = lang || '';
      S.mode = this.mode;
      this.mode === 'server' ? startRecording() : startRecognition();
      this.sync();
    },

    stop() { if (this.mine()) stop(); this.sync(); },
  };
};
