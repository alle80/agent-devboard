# Accessi, amministratori e modalità

## Modalità

`GRIGLIA_MODE` (oppure `config('griglia.mode')`, scavalcabile da Impostazioni → App):

| Modalità | Cosa vuol dire |
|---|---|
| `server` (default) | Serve il login. Ogni lista appartiene a un utente; la board mostra solo le tue. |
| `local` | Nessuna autenticazione, un unico insieme di liste globali, canale di broadcast pubblico. **Solo sulla tua macchina** — legalo a `127.0.0.1`. Una fascia te lo ricorda su ogni pagina. |

Per passare a `local` dall'interfaccia serve `APP_ENV=local` oppure `GRIGLIA_ALLOW_LOCAL_FROM_UI=true`.

## Chi può usare la board (modalità server)

Il package sostituisce il semplice middleware `auth` con un proprio gate. Puoi restringere l'accesso con:

- `canAccessGriglia(): bool` sul tuo modello utente, oppure
- `GRIGLIA_ACCESS_GATE=<ability>` (una ability del Gate della tua applicazione).

Il vecchio hook `canAccessDevboard()`, da prima della rinomina, è ancora onorato quando manca
`canAccessGriglia()`, ma le nuove installazioni devono usare il nome attuale.

## Chi la amministra

Impostazioni, contesto dell'agente e pacchetti di temi sono **solo per amministratori**:

- `canManageGriglia(): bool` sul tuo modello utente, oppure
- `GRIGLIA_ADMIN_GATE=<ability>`, oppure
- `GRIGLIA_ADMINS="1,alice@example.com"` (id o indirizzi e-mail).

Di default è amministratore solo il **primo utente registrato**. Come per l'accesso, il vecchio
`canManageDevboard()` è ancora onorato come ripiego.

## I pacchetti di temi sono codice

I pacchetti installabili sono trattati come contenuto non fidato: installazione riservata agli amministratori,
SVG rifiutati, CSS ripulito (niente `@import`, niente url esterni), tetti di dimensione (5 MB per file, 20 MB
per pacchetto, 200 file) e asset serviti da una rotta isolata. Vedi [Sicurezza](../operations/security.md).

## Vedi anche

- [Sicurezza](../operations/security.md) · [Configurazione e impostazioni](index.md)
