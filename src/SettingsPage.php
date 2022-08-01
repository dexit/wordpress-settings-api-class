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
abstract class SettingsPage extends MenuPage
{
	/**
	 * Istanza della classe che si interfaccia con le
	 * Settings API di WordPress; verrà impostato nel
	 * constructor automaticamente
	 */
	public $api;

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
		parent::__construct();
		$this->api = new SettingsApi;
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
		// Se non siamo sulla pagina specifica della sezione,
		// non serve calcolare tutti i campi
		if ( ( $_GET[ 'page' ] ?? '' ) === $this->slug ) {
			$this->api->set_fields( $this->getFields() );
		}
		$this->api->admin_init();
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

	/**
	 * Ritorna il valore di un'opzione a partire dalla sua sezione
	 * e dal suo nome
	 *
	 * @param string  $option  settings field name
	 * @param string  $section the section name this field belongs to
	 * @param string  $default default text if it's not found
	 * @return string
	 */
	public static function get_option( $option, $section, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[$option] ) ) {
			return $options[$option];
		}

		return $default;
	}
}
