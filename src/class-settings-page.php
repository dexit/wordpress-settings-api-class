<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace Idearia;

defined( 'ABSPATH' ) || exit;

/**
 * Crea una pagina di opzioni di WordPress e mostrala come
 * voce di menu a se stante oppure come sottovoce di un menu
 * esistente.
 *
 * Per creare una pagina estendi questa classe e sovrascrivi
 * i seguenti CAMPI OBBLIGATORI:
 *
 * - $label => titolo della pagina e nome della voce di menu
 * - $slug => slug unico della voce di menu
 * - getSections() => funzione che ritorna le sezioni della pagina,
 *   solitamente renderizzate come tabs
 * - getFields() => funzione che ritorna le opzioni vere e proprie,
 *   raggruppate per sezione
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
 *   e modificarne i settings (default = manage_options)
 * - view() => funzione che stampa l'HTML; lascia vuoto se
 *   verrà usato un semplice layout dove ogni sezione è un tab.
 *
 * Ricordati di caricare la classe nel bootstrap del tuo plugin.
 */
abstract class SettingsPage {

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
	 * Istanza della classe che si interfaccia con le
	 * Settings API di WordPress; verrà impostato nel
	 * constructor automaticamente
	 */
	private $api;

	/**
	 * Metodo da sovrascrivere che ritorna l'array delle sezioni
	 * da passare a class-settings-api.php
	 */
	abstract protected function getSections();

	/**
	 * Metodo da sovrascrivere che ritorna l'array dei setting
	 * fields da passare a class-settings-api.php
	 */
	abstract protected function getFields();

	/**
	 * Registra gli hooks
	 */
	public function __construct() {
		$this->api = new SettingsApi;
		add_action( 'admin_menu', array( $this, 'admin_menu' ), $this->filterPriority );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		if ( $this->capability !== 'manage_options' ) {
			$this->set_write_capabilities();
		}
	}

	/**
	 * Permetti agli utenti con la capability richiesta
	 * di modificare i settings
	 */
	private function set_write_capabilities() {
		$sections = $this->getSections();
		$sections_ids = array_column( $sections, 'id' );
		foreach ( $sections_ids as $option_page ) {
			add_filter(
				'option_page_capability_' . $option_page,
				function( $cap ) {
					return $this->capability;
				}
			);
		}
	}

	/**
	 * Callback che registra tutti i nostri settings usando l'API
	 */
	public function admin_init() {
		$this->api->set_sections( $this->getSections() );
		$this->api->set_fields( $this->getFields() );
		$this->api->admin_init();
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
	 * Funzione usata per renderizzare l'HTML; di default usiamo
	 * il layout a tabs.
	 */
	public function view() {
		$this->view_template_tabs();
	}

	/**
	 * Template che mostra un tab per ogni sezione
	 */
	protected function view_template_tabs() {
		echo '<div class="wrap ' . 'admin-options-' . $this->slug . '">';
		$this->api->show_navigation();
		$this->api->show_forms();
		echo '</div>';
	}

}
