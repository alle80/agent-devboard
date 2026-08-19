// alle80/agent-devboard — speech to text (dictation) via the browser's Web Speech API.
// Alpine data used by <x-devboard::mic>: window.devboardMic(getTarget) → { supported, on, toggle(), stop() }
// The recognised text is appended to the target input/textarea (at the end, with a separating space) and an
// `input` event is dispatched so Livewire (wire:model) and the md-editor auto-grow pick it up.
const SR = typeof window !== 'undefined' ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;

window.devboardMic = function (getTarget, lang) {
  return {
    supported: !!SR,
    on: false,
    rec: null,
    interim: '',
    base: '',
    toggle() { this.on ? this.stop() : this.start(); },
    start() {
      if (!SR) return;
      const el = typeof getTarget === 'function' ? getTarget() : getTarget;
      if (!el) return;
      const rec = new SR();
      rec.lang = lang || document.documentElement.lang || navigator.language || 'it-IT';
      if (rec.lang.length === 2) rec.lang = { it: 'it-IT', en: 'en-US', fr: 'fr-FR', de: 'de-DE', es: 'es-ES' }[rec.lang] || rec.lang;
      rec.continuous = true;
      rec.interimResults = true;
      this.base = el.value;
      this.interim = '';
      rec.onresult = (ev) => {
        let finalText = '', interim = '';
        for (let i = 0; i < ev.results.length; i++) {
          const t = ev.results[i][0].transcript;
          if (ev.results[i].isFinal) finalText += t; else interim += t;
        }
        const sep = this.base && !/\s$/.test(this.base) ? ' ' : '';
        el.value = this.base + sep + (finalText + interim).trim();
        el.dispatchEvent(new Event('input', { bubbles: true }));
        if (el.tagName === 'TEXTAREA') el.scrollTop = el.scrollHeight;
      };
      rec.onerror = () => this.stop();
      rec.onend = () => { if (this.on) { try { rec.start(); } catch (e) { this.on = false; } } };
      try { rec.start(); this.rec = rec; this.on = true; el.focus(); } catch (e) { this.on = false; }
    },
    stop() {
      this.on = false;
      if (this.rec) { try { this.rec.stop(); } catch (e) {} this.rec = null; }
    },
  };
};
