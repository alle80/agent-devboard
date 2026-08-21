# Notifiche

La board avvisa da sola il proprietario della lista quando l'agente **chiude un task** o **fa una domanda**
(gli eventi seguono le impostazioni `agent` `notify_on_done` / `notify_on_question`). Canali (Impostazioni →
App):

- **Campanella in-app** — pallino dei non letti, elenco, il clic apre il task (cambiando lista se serve),
  «segna tutto come letto»; dal vivo.
- **Web Push** — sui dispositivi dove l'hai abilitato (Impostazioni → Notifiche → *Abilita su questo
  dispositivo*; su iPhone: prima aggiungi il sito alla schermata Home). Servono le chiavi VAPID e
  `HasPushSubscriptions` sul modello utente. Il pannello **Diagnostica** mostra permesso, service worker,
  sottoscrizione e se una push è davvero arrivata al dispositivo.
- **Mail** — quando c'è un mailer configurato.

I link diretti `?list=ID&open=ID` aprono un task partendo da una notifica.

!!! tip "Due strati di notifiche"
    La board ti avvisa per conto suo (campanella, Web Push, mail). Il tuo agente può avvisarti *anche* dal suo
    canale quando chiude un task o chiede qualcosa — i due strati sono indipendenti, tieni quello che preferisci.

## Vedi anche

- [Installazione](../getting-started/installation.md#web-push-facoltativo) — chiavi VAPID e modello utente.
- [Se qualcosa non va](../operations/troubleshooting.md) — quando una push non arriva mai.
