# Come funziona

* Crea nuove pagine di opzioni in WordPress estendendo la classe `Idearia\SettingsPage`.
* Se vuoi creare un menu o sottomenu in WordPress senza opzioni, basta che estendi la classe `Idearia\MenuPage`.
* Accedi alle opzioni serializzate come fossi in Laravel estendend la classe `Idearia\Config`.

# Per avere più controllo

La classe `Idearia\SettingsPage` è un wrapper di `Idearia\SettingsApi` che ne semplifica l'utilizzo, che a sua volta è un wrapper della [Settings API di WordPress](https://codex.wordpress.org/Settings_API).

Per avere maggiore controllo sulle opzioni, puoi usare direttamente la classe `Idearia\SettingsApi`.

# Features

- Crea una pagina di opzioni usando solo array, niente codice richiesto.
- Usa il pratico template a tabs per raggruppare le opzioni.
- Controlla chi può vedere i menu.
- Controlla chi può modificare le opzioni.
- Controlla la posizione dei menu.
- Crea sia menu che sotto-menu.

# Dopo ogni modifica al repo...

Aggiorna il repository composer di Idearia come da istruzioni:

* https://trello.com/c/Q4wyOV9u/
