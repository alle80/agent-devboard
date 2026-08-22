// alle80/griglia — «copiabile»: ogni blocco di codice nelle note e nei commenti dell'agente ha un pulsante
// che lo copia, e il codice inline si copia con un clic. Serve perché quei riquadri contengono comandi e
// prompt da incollare altrove (task 367). Qualunque elemento con data-copy (la targhetta «id:N» del task nella
// riga e nel modale, task 510) copia quel valore con un tocco e per un attimo dice «copiato».
const t = () => window.GRIGLIA_I18N || {};

async function copyText(text) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    }
  } catch (e) { /* fall through: older browsers, permission denied */ }

  // Fallback for non-secure contexts (plain http, local mode)
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.setAttribute('readonly', '');
  ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
  document.body.appendChild(ta);
  ta.select();
  let ok = false;
  try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
  ta.remove();
  return ok;
}

function flash(el, done) {
  const label = el.querySelector('.db-copy-label');
  if (label) label.textContent = done ? (t().copied || 'copied') : (t().copy_failed || 'error');
  el.classList.add(done ? 'is-copied' : 'is-failed');
  setTimeout(() => {
    el.classList.remove('is-copied', 'is-failed');
    if (label) label.textContent = t().copy || 'copy';
  }, 1600);
}

const ICON = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>';

/** Adds the button to every code block that does not have one yet. */
function decorate(root = document) {
  root.querySelectorAll('.db-prose pre').forEach((pre) => {
    if (pre.querySelector(':scope > .db-copy')) return;
    pre.classList.add('db-copy-host');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'db-copy';
    btn.title = t().copy_block || 'Copy';
    btn.setAttribute('aria-label', btn.title);
    btn.innerHTML = ICON + '<span class="db-copy-label">' + (t().copy || 'copy') + '</span>';
    pre.appendChild(btn);
  });

  // Links inside a note or a comment open outside the board.
  root.querySelectorAll('.db-prose a[href^="http"]').forEach((a) => {
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
  });
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.db-copy');
  if (btn) {
    e.preventDefault();
    e.stopPropagation();
    const pre = btn.closest('pre');
    const code = pre?.querySelector('code') || pre;
    const text = (code.cloneNode(true).textContent || '').replace(/\s*\n?$/, '');
    flash(btn, await copyText(text));
    return;
  }

  // Anything with data-copy (the task id chip, task 510): one tap copies the value; the label says «copied»
  // (or «not copied») for a moment, then goes back to what it was.
  const chip = e.target.closest('[data-copy]');
  if (chip) {
    e.preventDefault();
    e.stopPropagation();
    const ok = await copyText(chip.dataset.copy || '');
    const original = chip.dataset.copyLabel ?? (chip.dataset.copyLabel = chip.textContent);
    chip.textContent = ok ? (t().copied || 'copied') : (t().copy_failed || 'error');
    chip.classList.add(ok ? 'is-copied' : 'is-failed');
    setTimeout(() => { chip.textContent = original; chip.classList.remove('is-copied', 'is-failed'); }, 1200);
    return;
  }

  // Inline code: one click copies it, unless the user is selecting text.
  const inline = e.target.closest('.db-prose code');
  if (inline && ! inline.closest('pre') && (window.getSelection()?.isCollapsed ?? true)) {
    const ok = await copyText(inline.textContent || '');
    inline.classList.add(ok ? 'is-copied' : 'is-failed');
    setTimeout(() => inline.classList.remove('is-copied', 'is-failed'), 1200);
  }
});

const run = () => decorate();
document.addEventListener('DOMContentLoaded', run);
document.addEventListener('livewire:navigated', run);

// Livewire re-renders (modal, live updates) bring in new blocks: decorate them as they appear.
if (typeof MutationObserver !== 'undefined') {
  let queued = false;
  new MutationObserver(() => {
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => { queued = false; decorate(); });
  }).observe(document.documentElement, { childList: true, subtree: true });
}

run();
