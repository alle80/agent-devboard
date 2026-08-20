// alle80/griglia — speech to text. Two modes, chosen by the server (window.GRIGLIA_SPEECH.mode):
//  - 'server'  : record with MediaRecorder, upload to GRIGLIA_SPEECH.url, the AI SDK transcribes (best quality);
//  - 'browser' : the browser's Web Speech API (free; phones restart the session at every pause, handled).
// Alpine data used by <x-griglia::mic>: window.grigliaMic(getTarget) → { supported, on, busy, toggle(), stop() }
// Recognised text is appended to the target input/textarea and an `input` event is dispatched (wire:model).
const SR = typeof window !== 'undefined' ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;
const cfg = () => window.GRIGLIA_SPEECH || {};
const canRecord = () => typeof window !== 'undefined' && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);

function append(el, text) {
  const said = (text || '').trim();
  if (!said) return;
  const base = el.value;
  const sep = base && !/\s$/.test(base) ? ' ' : '';
  el.value = base + sep + said;
  el.dispatchEvent(new Event('input', { bubbles: true }));
  if (el.tagName === 'TEXTAREA') el.scrollTop = el.scrollHeight;
}

function pickMime() {
  const c = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/wav'];
  return c.find((m) => window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(m)) || '';
}

window.grigliaMic = function (getTarget, lang) {
  const serverMode = cfg().mode === 'server' && canRecord();
  return {
    supported: serverMode || !!SR,
    mode: serverMode ? 'server' : 'browser',
    on: false,
    busy: false,
    error: '',
    rec: null,      // SpeechRecognition (browser mode)
    mr: null,       // MediaRecorder (server mode)
    chunks: [],
    base: '',
    toggle() { if (this.busy) return; this.on ? this.stop() : this.start(); },
    target() { return typeof getTarget === 'function' ? getTarget() : getTarget; },
    init() { document.addEventListener('visibilitychange', () => { if (document.hidden && this.on) this.stop(); }); },

    // ----- start / stop -----
    start() { this.error = ''; this.mode === 'server' ? this.startRecording() : this.startRecognition(); },
    stop() { this.mode === 'server' ? this.stopRecording() : this.stopRecognition(); },

    // ----- server mode: record → upload → append -----
    async startRecording() {
      const el = this.target();
      if (!el) return;
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const mime = pickMime();
        const mr = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
        this.chunks = [];
        mr.ondataavailable = (e) => { if (e.data && e.data.size) this.chunks.push(e.data); };
        mr.onstop = async () => {
          stream.getTracks().forEach((t) => t.stop());
          const blob = new Blob(this.chunks, { type: mr.mimeType || mime || 'audio/webm' });
          this.chunks = [];
          if (!blob.size) return;
          this.busy = true;
          try {
            const ext = (blob.type.includes('mp4') ? 'mp4' : blob.type.includes('ogg') ? 'ogg' : blob.type.includes('wav') ? 'wav' : 'webm');
            const fd = new FormData();
            fd.append('audio', blob, 'speech.' + ext);
            fd.append('lang', lang || cfg().lang || '');
            const r = await fetch(cfg().url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': cfg().csrf, Accept: 'application/json' } });
            const j = await r.json().catch(() => ({}));
            if (!r.ok || !j.ok) throw new Error(j.error || ('HTTP ' + r.status));
            append(el, j.text);
          } catch (e) {
            console.error('griglia speech:', e);
            this.error = cfg().error || 'error';
            setTimeout(() => { this.error = ''; }, 4000);
          } finally {
            this.busy = false;
            el.focus();
          }
        };
        mr.start(250);
        this.mr = mr;
        this.on = true;
      } catch (e) {
        console.error('griglia speech:', e);
        this.on = false;
        this.error = cfg().error || 'error';
        setTimeout(() => { this.error = ''; }, 4000);
      }
    },
    stopRecording() {
      this.on = false;
      if (this.mr && this.mr.state !== 'inactive') { try { this.mr.stop(); } catch (e) {} }
      this.mr = null;
    },

    // ----- browser mode: Web Speech API -----
    startRecognition() {
      if (!SR) return;
      const el = this.target();
      if (!el) return;
      const rec = new SR();
      rec.lang = lang || document.documentElement.lang || navigator.language || 'it-IT';
      if (rec.lang.length === 2) rec.lang = { it: 'it-IT', en: 'en-US', fr: 'fr-FR', de: 'de-DE', es: 'es-ES' }[rec.lang] || rec.lang;
      rec.continuous = true;
      rec.interimResults = true;
      this.base = el.value;
      rec.onresult = (ev) => {
        let finalText = '', interim = '';
        for (let i = 0; i < ev.results.length; i++) {
          const t = ev.results[i][0].transcript;
          if (ev.results[i].isFinal) finalText += t; else interim += t;
        }
        const said = (finalText + interim).trim();
        if (!said) return;
        const sep = this.base && !/\s$/.test(this.base) ? ' ' : '';
        el.value = this.base + sep + said;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        if (el.tagName === 'TEXTAREA') el.scrollTop = el.scrollHeight;
      };
      rec.onerror = (ev) => { if (ev && (ev.error === 'not-allowed' || ev.error === 'service-not-allowed' || ev.error === 'audio-capture')) this.stopRecognition(); };
      // Phones end a continuous session after every pause: keep what was dictated (new base) and restart
      rec.onend = () => {
        if (!this.on) return;
        this.base = el.value;
        try { rec.start(); } catch (e) { setTimeout(() => { if (this.on) { try { rec.start(); } catch (e2) { this.on = false; } } }, 250); }
      };
      try { rec.start(); this.rec = rec; this.on = true; el.focus(); } catch (e) { this.on = false; }
    },
    stopRecognition() {
      this.on = false;
      if (this.rec) { try { this.rec.stop(); } catch (e) {} this.rec = null; }
    },
  };
};
