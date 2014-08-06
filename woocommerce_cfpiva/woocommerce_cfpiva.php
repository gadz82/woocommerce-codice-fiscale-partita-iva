<?php
/*
Plugin Name: Woocommerce Codice Fiscale e Partita Iva
Plugin URI: http://webglow.it
Description: Aggiunge i campi per il codice fiscale e la partita iva alla pagina di checkout di Woocommerce (eseguendo i dovuti controlli di correttezza)
Version: 1.0
Author: Francesco Marchesini
Author URI: http://webglow.it
*/

if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	
	class overplace_woocommerce_ext{
		
		const paesi_cfpiva = "IT";
		
		/**
		 * Costruttore, imposta le actions di Wordpress
		 */
		function __construct(){
			add_action('woocommerce_after_order_notes', array($this,'add_cfpiva_field'));
			add_action('woocommerce_checkout_process', array($this,'cfpiva_checkout_field_process'));
			add_action('woocommerce_checkout_update_user_meta', array($this,'cfpiva_checkout_field_update_user_meta'));
			add_action('woocommerce_checkout_update_order_meta', array($this, 'cfpiva_checkout_field_update_order_meta'));
			add_filter('woocommerce_email_order_meta_keys', array($this,'cfpiva_checkout_field_order_meta_keys'));
		}
		

		/**
		 * Aggiunge il campo partita iva codice fiscale alla pagina per il checkout
		 * @param unknown $checkout
		 */
		function add_cfpiva_field($checkout){
		
			echo '<div id="piva_field"><h3>'.__('Codice Fiscale / Partita Iva').'</h3>';
			woocommerce_form_field( 'cfpiva', array(
			'type' 			=> 'text',
			'class' 		=> array('cfpiva form-row-wide'),
			'label' 		=> __('Partita Iva o Codice Fiscale'),
			'placeholder' 	=> __('Codice Fiscale o Partita Iva'),
			'required'      => true
			), $checkout->get_value( 'cfpiva' ));
		
			echo '</div>';
			?>
			<script type="text/javascript">
				jQuery('select#billing_country').live('change', function(){
					
					var country = jQuery('select#billing_country').val();
					
					var check_countries = new Array(<?php echo self::paesi_cfpiva; ?>);
		
					if (country && jQuery.inArray( country, check_countries ) >= 0) {
						jQuery('#piva_field').fadeIn();
					} else {
						jQuery('#piva_field').fadeOut();
						jQuery('#piva_field input').val('');
					}
					
				});
			</script>
			<?php
		}
				
		/**
		 * Rende obbligatorio il campo codice fiscale / partita iva
		 */
		public function cfpiva_checkout_field_process(){
			global $woocommerce;
			if($_POST['billing_country'] == 'IT'){
				if ($_POST['billing_company']){
					if (!$_POST['cfpiva']){
						$woocommerce->add_error( __('Inserisci il tuo Codice Fiscale / Partita Iva.') );			
					}
				}
				
				if(isset($_POST['cfpiva']) && empty($_POST['billing_company']) && !$this->controlla_codice_fiscale(esc_attr($_POST['cfpiva']))){
					$woocommerce->add_error( __('Codice fiscale Errato') );
				}
				
				if(isset($_POST['cfpiva']) && !empty($_POST['billing_company']) && !$this->controlla_piva(esc_attr($_POST['cfpiva']))){
					if(!$this->controlla_codice_fiscale(esc_attr($_POST['cfpiva'])))
						$woocommerce->add_error( __('Partita iva/codice fiscale non corretti') );
				}
				
			}
		}
		
		/**
		 * Controlla la validità di un codice fiscale
		 * @param string $cf
		 * @return boolean
		 */
		private function controlla_codice_fiscale($cf){
			if(empty($cf))
				return false;
			 
			if(strlen($cf) != 16) 
				return false;
			 
			$cf = strtoupper($cf); 
			if(!preg_match("/[A-Z0-9]+$/", $cf)) 
				return false; $s = 0; 
			
			for($i = 1; $i <= 13; $i += 2){ 	
				
				$c = $cf[$i]; 
	
				if('0' <= $c && $c<='9') {
					$s += ord($c) - ord('0');
				} else { 
					$s+=ord($c)-ord('A');
				} 
			} 
			
			for($i = 0; $i <= 14; $i += 2){
				 
				$c = $cf[$i];
				 
				switch($c){ 
					case '0': $s += 1; break; 
					case '1': $s += 0; break; 
					case '2': $s += 5; break; 
					case '3': $s += 7; break; 
					case '4': $s += 9; break; 
					case '5': $s += 13; break; 
					case '6': $s += 15; break; 
					case '7': $s += 17; break; 
					case '8': $s += 19; break; 
					case '9': $s += 21; break; 
					case 'A': $s += 1; break; 
					case 'B': $s += 0; break; 
					case 'C': $s += 5; break; 
					case 'D': $s += 7; break; 
					case 'E': $s += 9; break; 
					case 'F': $s += 13; break; 
					case 'G': $s += 15; break; 
					case 'H': $s += 17; break; 
					case 'I': $s += 19; break; 
					case 'J': $s += 21; break; 
					case 'K': $s += 2; break; 
					case 'L': $s += 4; break; 
					case 'M': $s += 18; break; 
					case 'N': $s += 20; break; 
					case 'O': $s += 11; break; 
					case 'P': $s += 3; break; 
					case 'Q': $s += 6; break; 
					case 'R': $s += 8; break; 
					case 'S': $s += 12; break; 
					case 'T': $s += 14; break; 
					case 'U': $s += 16; break; 
					case 'V': $s += 10; break; 
					case 'W': $s += 22; break; 
					case 'X': $s += 25; break; 
					case 'Y': $s += 24; break; 
					case 'Z': $s += 23; break; 
				} 
			} 
			
			if( chr($s%26+ord('A')) != $cf[15] ){
				return false;
			} 
				 
			return true;
		}
		
		private function controlla_piva($piva){
			if(empty($piva))
				return false;
		
			//la p.iva deve essere lunga 11 caratteri
			if(strlen($piva) != 11)
				return false;
		
			//la p.iva deve avere solo cifre
			if(!ereg("^[0-9]+$", $piva))
				
				return false;
		
			$f = 0;
			for($i = 0; $i <= 9; $i += 2)
				
				$f += ord($piva[$i])-ord('0');
		
			for($i=1; $i<=9; $i+=2 ){
				
				$s =2*( ord($piva[$i])-ord('0') );
		
				if($s > 9){
					
					$s = $s-9;
				}			
				
				$f += $s;
		
			}
			if( (10-$f%10)%10 != ord($piva[10])-ord('0') )
				return false;
		
			return true;
		
		}
		
		
		/**
		 * Mostra un messaggio di errore nel caso in cui l'utente non abbia messo la spunta su
		 * "Accetto" nel campo condizioni legali.
		 */
		public function condizioni_legali_checkout_field_process(){
			global $woocommerce;
			if ($_POST['billing_company']){
				if (!$_POST['condizioni_legali']){
					$woocommerce->add_error( __('E&apos; necessario accettare le condizioni legali per proseguire.') );
				}
			}
		}
	
		/**
		 * Aggiunge il codice fiscale partita iva alle properties dell'utente wordpress
		 * @param unknown $user_id
		 */
		public function cfpiva_checkout_field_update_user_meta($user_id){
			if ($user_id && $_POST['cfpiva']) update_user_meta($user_id, 'cfpiva', esc_attr($_POST['cfpiva']) );
		}
		
		/**
		 * Aggiunce cf/piva ad attributi dell'ordine Woocommerce
		 * @param int $order_id
		 */
		public function cfpiva_checkout_field_update_order_meta($order_id){
			if ($_POST['cfpiva']) update_post_meta( $order_id, 'cfpiva', esc_attr($_POST['cfpiva']));
		}
		
		/**
		 * Decommentare per aggiungere codice fiscale/partita_iva all'array delle keys visualizzate
		 * nelle email di resoconto ordine.
		 * @param array $keys
		 * @return array
		 */
		public function cfpiva_checkout_field_order_meta_keys($keys){
			//$keys[] = 'cfpiva';
			return $keys;
		}
		
	}
	
	$ovpw = new overplace_woocommerce_ext();
	
}

/*register_deactivation_hook( __FILE__, 'ovp_woo_ext_deactivate' );
register_uninstall_hook( __FILE__, 'ovp_woo_ext_deactivate' );*/

/**
 * Disinstallazione del modulo, rimuove l'user role
 */
function ovp_woo_ext_deactivate(){
	remove_role('ovp_shop_manager');
}