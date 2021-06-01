# Come funziona

* Crea nuovi menu o sottomenu in WordPress estendendo la classe `Idearia\SettingsPage`.
* Accedi alle opzioni serializzate come fossi in Laravel estendend la classe `Idearia\Config`.
# Per avere più controllo

La classe `Idearia\SettingsPage` è un wrapper di `Idearia\SettingsApi` che ne semplifica l'utilizzo, che a sua volta è un wrapper della [Settings API di WordPress](https://codex.wordpress.org/Settings_API).

Per avere maggiore controllo sulle opzioni, puoi usare direttamente la classe `Idearia\SettingsApi`.
# Dopo ogni modifica al repo...

Aggiorna il repository composer di Idearia come da istruzioni:

* https://trello.com/c/Q4wyOV9u/
