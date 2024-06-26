<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace Idearia;

/**
 * Crea una pagina generica di menu in WordPress e mostrala come
 * voce di menu a se stante oppure come sottovoce di un menu
 * esistente.
 *
 * Per creare una pagina estendi questa classe e sovrascrivi
 * i seguenti CAMPI OBBLIGATORI:
 *
 * - $label => titolo della pagina e nome della voce di menu
 * - $slug => slug unico della voce di menu
 * - view() => funzione che stampa l'HTML
 *
 * Per maggiore controllo sovrascrivi i seguenti CAMPI OPZIONALI:
 * - $position => posizione relativa della voce di menu
 * - $parentSlug => se vuoi che la pagina sia una sottovoce di un
 *   menu esistente, specifica qui lo slug del menu; altrimenti
 *   verrà creato un menu madre ad hoc con etichetta $label
 * - $menuLabel => per specificare un'etichetta del menu
 *   diversa da $label
 * - $capability => per specificare quale capability deve 
 *   possedere l'utente per visualizzare la voce del menu
 * - $icon => icona del menu, di default l'ingranaggio; i valori
 *   accettati sono gli stessi di $icon_url
 *
 * Ricordati di caricare la classe nel bootstrap del tuo plugin.
 */
class MenuPage
{
	/**
	 * Nome della pagina, apparirà sia come titolo della pagina che
	 * come etichetta della voce di menu
	 */
	protected $label; // obbligatorio

	/**
	 * Slug della pagina
	 */
	protected $slug; // obbligatorio

	/**
	 * Funzione usata per renderizzare l'HTML; deve fare
	 * echo di qualcosa.
	 */
	protected $viewCallback;

	/**
	 * Posizione della voce di menu nella sidebar
	 */
	protected $position = null;

	/**
	 * Menu madre a cui far appartenere questa pagina (ad es.
	 * options-general.php per farlo apparire nei 'Settings' di
	 * WordPress).
	 *
	 * Se lasci vuoto, verrà creato appositamente un nuovo menu
	 * madre con slug $slug e con etichetta $label.
	 */
	protected $parentSlug = '';

	/**
	 * Etichetta del menu madre. Lascia vuoto se vuoi che coincida
	 * con $label, oppure se il menu non ha altre pagine oltre
	 * questa.
	 *
	 * Ignorata se viene specificato un valore per $parentSlug,
	 * perché in quel caso la label è quella del parent.
	 */
	protected $menuLabel = '';

	/**
	 * Permessi per visualizzare la voce del menu
	 */
	protected $capability = 'manage_options';

	/**
	 * Priorità della voce di menu nel filtro WordPress admin_menu.
	 *
	 * IMPORTANTE: Per i sottomenu, va impostato un valore superiore a
	 * quello del menu parent, altrimenti il sottomenu darà un 404.
	 */
	protected $filterPriority = 10;

	/**
	 * Icona da usare per la voce di menu; va usato solo per i menu parent,
	 * di default è l'icona dell'ingranaggio.
	 *
	 * Puoi passare una URL oppure uno di questi tre:
	 * - Pass a base64-encoded SVG using a data URI, which will be colored to
	 *   match the color scheme. This should begin with 'data:image/svg+xml;base64,'.
	 * - Pass the name of a Dashicons helper class to use a font icon,
	 *   e.g. 'dashicons-chart-pie'.
	 * - Pass 'none' to leave div.wp-menu-image empty so an icon can be
	 *   added via CSS.
	 */
	protected $icon = '';

	/**
	 * Specifica qui i plugin che devono essere presenti affinché
	 * la voce di menu venga mostrata; usa lo stesso formato
	 * richiesto da is_plugin_active()
	 */
	protected $requiredPlugins = [];

	/**
	 * Registra gli hooks
	 */
	public function __construct( array $attributes = [] )
	{
		// Parse arguments
		$this->label = $attributes['label'] ?? $this->label;
		$this->slug = $attributes['slug'] ?? $this->slug;
		$this->viewCallback = $attributes['view'] ?? function() {
			echo '<h1>Hello world!</h1>';
			echo '<p>';
			echo 'Questa pagina può essere vista solo da chi ha una certa capability';
			echo '</p>';	
		};
		$this->position = $attributes['position'] ?? $this->position;
		$this->parentSlug = $attributes['parentSlug'] ?? $this->parentSlug;
		$this->menuLabel = $attributes['menuLabel'] ?? $this->menuLabel;
		$this->capability = $attributes['capability'] ?? $this->capability;
		$this->icon = $attributes['icon'] ?? $this->icon;
		$this->filterPriority = $attributes['filterPriority'] ?? $this->filterPriority;
		$this->requiredPlugins = $attributes['requiredPlugins'] ?? $this->requiredPlugins;

		// Validate
		if ( ! $this->label ) {
			throw new \Exception( 'Label non definita!' );
		}
		if ( ! $this->slug ) {
			throw new \Exception( 'Slug non definita!' );
		}

		// Crea pagina di menu
		add_action( 'admin_menu', array( $this, 'admin_menu' ), $this->filterPriority );
	}

	/**
	 * Aggiungi alla sidebar la voce di menu
	 */
	public function admin_menu()
	{
		// Se non sono attivi i plugin richiesti, non mostrare nulla
		if ( ! $this->checkDependencies() ) {
			return;
		}

		// Se la pagina è una sottovoce di un menu madre esistente...
		if ( ! empty( $this->parentSlug ) ) {
			add_submenu_page( $this->parentSlug, $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ], $this->position );
		}
		// Se il menu madre va creato...
		else {
			// Caso in cui l'etichetta del menu madre è diversa da quella
			// della sottovoce di menu (https://wordpress.stackexchange.com/a/66499/86662)
			if ( ! empty( $this->menuLabel ) ) {
				add_menu_page( $this->menuLabel, $this->menuLabel, $this->capability, $this->slug, '__return_true', $this->icon, $this->position );
				add_submenu_page( $this->slug, $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ] );
			}
			// Caso in cui non ci interessa differenziare, ad es. perché non ci sono
			// altre pagine di menu nel menu madre
			else {
				add_menu_page( $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ], $this->icon, $this->position );
			}
		}
	}

	/**
	 * Funzione usata per renderizzare l'HTML
	 */
	public function view()
	{
		call_user_func_array( $this->viewCallback, [] );
	}

	/**
	 * Ritorna true se le dipendenze necessarie per mostrare la voce
	 * di menu sono a posto
	 */
	public function checkDependencies()
	{
		foreach( $this->requiredPlugins as $plugin )
		{
			if ( ! is_plugin_active( $plugin ) ) {
				return false;
			}
		}
		return true;
	}
}
