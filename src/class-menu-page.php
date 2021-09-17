<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace Idearia;

defined( 'ABSPATH' ) || exit;

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
 *
 * Ricordati di caricare la classe nel bootstrap del tuo plugin.
 */
abstract class MenuPage
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
	 * Posizione della voce di menu nella sidebar
	 */
	protected $position = null;

	/**
	 * Menu madre a cui far appartenere questa pagina (ad es.
	 * options-general.php).
	 *
	 * Se lasci vuoto, verrà creato appositamente un nuovo menu
	 * madre con slug $parentSlug e con etichetta $label.
	 */
	protected $parentSlug = '';

	/**
	 * Etichetta del menu madre. Lascia vuoto se vuoi che coincida
	 * con $label, oppure se il menu non ha altre pagine oltre
	 * questa.
	 *
	 * Ignorata se viene specificato un valore per $parentSlug.
	 */
	protected $menuLabel = '';

	/**
	 * Permessi per visualizzare la voce del menu
	 */
	protected $capability = 'manage_options';

	/**
	 * Priorità della voce di menu nel filtro WordPress admin_menu
	 */
	protected $filterPriority = 10;

	/**
	 * Registra gli hooks
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), $this->filterPriority );
	}

	/**
	 * Aggiungi alla sidebar la voce di menu
	 */
	public function admin_menu() {
		// Se la pagina è una sottovoce di un menu madre esistente...
		if ( ! empty( $this->parentSlug ) ) {
			add_submenu_page( $this->parentSlug, $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ], $this->position );
		}
		// Se il menu madre va creato...
		else {
			// Caso in cui l'etichetta del menu madre è diversa da quella
			// della sottovoce di menu (https://wordpress.stackexchange.com/a/66499/86662)
			if ( ! empty( $this->menuLabel ) ) {
				add_menu_page( $this->menuLabel, $this->menuLabel, $this->capability, $this->slug, '__return_true', '', $this->position );
				add_submenu_page( $this->slug, $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ] );
			}
			// Caso in cui non ci interessa differenziare, ad es. perché non ci sono
			// altre pagine di menu nel menu madre
			else {
				add_menu_page( $this->label, $this->label, $this->capability, $this->slug, [ $this, 'view' ], '', $this->position );
			}
		}
	}

	/**
	 * Funzione usata per renderizzare l'HTML
	 */
	public function view() {
		return 'Hello world!';
	}
}
