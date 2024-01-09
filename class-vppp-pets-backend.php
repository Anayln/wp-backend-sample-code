<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Pets_Backend {
    
    private $db_interface_pets;
	private $plugin_name;
	private $version;
	private $pet_list;

    public function __construct( $plugin_name, $version, $db_pets ) {
		
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->db_interface_pets = $db_pets;		
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vppp-pets-backend.css', array(), $this->version, 'all' );

	}

	public function enqueue_scripts() {

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vppp-pets-backend.js', array( 'jquery' ), $this->version, false );

	}

    public function add_pets_menu_entry() {
		global $customer_pets_page;         
       $customer_pets_page = add_submenu_page(	
            'prescriptions-list',
            __( 'Pets List' ),
            __( 'Pets List' ),
            'manage_woocommerce', // Required user capability
            'customer-pets-list',
            array($this, 'generate_pets_page'),
        );
		$this->pet_list = new Pets_list();
		add_action("load-$customer_pets_page", array($this->pet_list,"pets_sample_screen_options"));
        
    }

    public function generate_pets_page() {

		if(!empty( $_GET['action']) && ( $_GET['action'] == 'add_pet' || $_GET['action'] == 'edit_pet') )  {
			$pet_edit_form = new Pets_edit($this->db_interface_pets);
			$pet_edit_form->display();
		} else {
			$table = $this->pet_list;
			$add_new_url = add_query_arg('action','add_pet',menu_page_url('customer-pets-list',false));
			echo '<div class="wrap">
				<h1 class="wp-heading-inline">Customer Pets List</h1>
				<a href="'.$add_new_url.'" class="page-title-action">Add Pet</a>';
			echo '<form method="post">';
			// Prepare table
			$table->prepare_items();
			// Search form
			$table->search_box('search', 'search_id');
			// Display table
			$table->display();
			echo '</div></form>';
		}        
    }

    public function pets_action_handler() {
		
		if ( isset( $_POST['delete_a_pet'] ) ) {
			self::delete_a_pet_handler();
			return;
		}

        if ( isset( $_POST['restore_a_pet'] ) ) {
			self::restore_a_pet_handler();
			return;
		}

		return;
    }


    private function delete_a_pet_handler() {
		
		$nonce = $_POST['_wpnonce'];
		$pet_id = $_POST['delete_a_pet'];

		if( wp_verify_nonce($nonce, 'delete-a-pet-nonce' . $pet_id) ) {
			$this->db_interface_pets->pet_db_delete($pet_id);
			$pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
			wp_safe_redirect("admin.php?page=customer-pets-list&pagenum=" . $pagenum );
			exit();
		}
	}

    private function restore_a_pet_handler() {
		
		$nonce = $_POST['_wpnonce'];
		$pet_id = $_POST['restore_a_pet'];

		if( wp_verify_nonce($nonce, 'restore-a-pet-nonce' . $pet_id) ) {
			$this->db_interface_pets->pet_db_restore($pet_id);
            $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
			wp_safe_redirect("admin.php?page=customer-pets-list&pagenum=" . $pagenum );
			exit();
		}
	}

	public static function logit( $obj ) {
		$logn   = array( 'source' => 'vppp-admin' );
		$logger = wc_get_logger();
		$logger->info( var_export( $obj, true ), $logn );
		return;
	}	
	
	function pets_set_screen_option($status, $option, $value) {
		if ( 'edit_pets_per_page' == $option ) return $value;
	}

	function load_prescriptions_page_customer_pets_list() {

		$current_screen = get_current_screen();
		$current_screen->is_block_editor( false );
		$screen_id = $current_screen->id;
		$this->add_pets_specific_meta_box($screen_id);
	}

	private function add_pets_specific_meta_box($screen_id) {
		add_meta_box(
			'customer-pet-details',
			__( 'Customer Pets', 'woocommerce' ),
			array( $this, 'render_custom_meta_box' ),
			$screen_id,
			'normal',
			'high'
		);

		add_meta_box(
			'customer-pet-actions',
			__( 'Pet actions', 'woocommerce' ),
			array( $this, 'render_custom_pet_action_meta_box' ),
			$screen_id,
			'side',
			'high' 
		);		
	}

	function render_custom_meta_box() {
		$action  = !empty( $_GET['action'] ) ?  $_GET['action'] : '';
		$pet_data = array();
		if(!empty($action) && $action == 'edit_pet' && !empty($_GET['p_id'])) {
			$pet_data = $this->db_interface_pets->get_single_pet(intval($_GET['p_id']));
		}
		?>
		<h2 class="woocommerce-order-data__heading"><?php _e('Pet Details',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></h2>
		<div id="pets-add-form-wrapper">
			<p class="form-field form-field-wide">
				<label for="pet_name"><?php _e('Name',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="pet_name" id="pet_name"  value="<?php echo !empty($pet_data['pet_name']) ? esc_html($pet_data['pet_name']) : ''; ?>" required>
			</p>
			<p class="form-field form-field-wide">
				<label for="pet_species"><?php _e('Animal',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?>&nbsp;<span class="required">*</span></label>
				<select class="woocommerce-Input woocommerce-Input--text input-text" name="pet_species" id="pet_species" required>
					<option value="" <?php empty($pet_data['species']) ? 'selected=selected' : ''; ?> ><?php _e('--Please choose an option--',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="dog" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'dog' ? 'selected=selected' : ''; ?>><?php _e('Dog',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="cat" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'cat' ? 'selected=selected' : ''; ?>><?php _e('Cat',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="horse" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'horse' ? 'selected=selected' : ''; ?>><?php _e('Horse',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="rabbit" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'rabbit' ? 'selected=selected' : ''; ?>><?php _e('Rabbit',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="guinea" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'guinea' ? 'selected=selected' : ''; ?>><?php _e('Guinea Pig',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="rodent" <?php echo  !empty($pet_data['species']) && $pet_data['species'] == 'rodent' ? 'selected=selected' : ''; ?>><?php _e('Rodent',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="bird" <?php echo !empty($pet_data['species']) && $pet_data['species'] == 'bird' ? 'selected=selected' : ''; ?>><?php _e('Bird',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
				</select>        
			</p>
			<p class="form-field form-field-wide">
				<label for="pet_sex"><?php _e('sex',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?>&nbsp;<span class="required">*</span></label>
				<select  class="woocommerce-Input woocommerce-Input--text input-text" name="pet_sex" id="pet_sex" required >
					<option value="" <?php echo empty($pet_data['sex']) ? 'selected=selected' : ''; ?>><?php _e('--Please choose an option--',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="male" <?php echo !empty($pet_data['sex']) && $pet_data['sex'] == 'male' ? 'selected=selected' : ''; ?>><?php _e('Male',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
					<option value="female" <?php echo !empty($pet_data['sex']) && $pet_data['sex'] == 'female' ? 'selected=selected' : ''; ?>><?php _e('Female',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></option>
				</select>
			</p>
			<p class="form-field form-field-wide">
				<label for="pet_breed"><?php _e('Breed',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="pet_breed" id="pet_breed" value="<?php echo !empty($pet_data['breed']) ? esc_html($pet_data['breed']) : ''; ?>">
			</p>
			<p class="form-field form-field-wide">
				<label for="pet_birthday"><?php _e('Birthday',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN); ?>&nbsp;<span class="required">*</span></label>
				<input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="pet_birthday" min="1960-01-01" id="pet_birthday" value="<?php echo !empty($pet_data['birthday']) ? esc_html($pet_data['birthday']) : ''; ?>" required>
			</p>
		</div>
		<?php		
	}

	function render_custom_pet_action_meta_box() {			
		$action  = !empty( $_GET['action'] ) ?  $_GET['action'] : '';
		?>
		<div class="pet_actions submitbox">
			<button type="submit" class="button save_pet button-primary" name="save" value="<?php echo $action == 'edit_pet' ? 'update' :'add'; ?>"><?php $action == 'edit_pet' ? _e('Update',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN) : _e('Add',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN);  ?></button>
		</div>
		<?php
	}

	function save_pet_custom_metabox_data($post_id) {
		$action = !empty( $_REQUEST['action']) ? $_REQUEST['action'] : '';
		if(!empty($action) && ( $action == 'add_pet' || $action == 'edit_pet') ) {
			$nonce = !empty( $_POST['_wpnonce'] ) ? $_POST['_wpnonce']  : '';			
			if($action  == 'add_pet' ) {				
				if ( wp_verify_nonce( $nonce, 'new-pet-form-nonce' ) ) {
					$user    = wp_get_current_user();
					$email   = $user->user_email;
					$user_id = $user->ID;
					$pet = array(
						'owner_id'    => strval( $user_id ),
						'owner_email' => $email,
						'pet_name'    => !empty( $_POST['pet_name'] ) ? $_POST['pet_name'] : '',
						'birthday'    => !empty( $_POST['pet_birthday'] ) ? $_POST['pet_birthday'] : '',
						'species'     => !empty( $_POST['pet_species'] ) ? $_POST['pet_species'] : '',
						'breed'       => !empty( $_POST['pet_breed'] ) ? $_POST['pet_breed'] : '',
						'sex'         => !empty( $_POST['pet_sex'] ) ? $_POST['pet_sex'] : '',
					);					
					$this->db_interface_pets->pet_db_insert( $pet );
				}
			} elseif($action == 'edit_pet' && !empty($_REQUEST['pet_id'])) {				
				if ( wp_verify_nonce( $nonce, 'edit-pet-form-nonce' ) ) {
					$pet = array(
						'pet_name' => !empty( $_POST['pet_name']) ? $_POST['pet_name'] : '',
						'birthday' => !empty( $_POST['pet_birthday']) ? $_POST['pet_birthday'] : '',
						'species'  => !empty( $_POST['pet_species']) ? $_POST['pet_species'] : '',
						'breed'    => !empty( $_POST['pet_breed']) ? $_POST['pet_breed'] : '',
						'sex'      => !empty( $_POST['pet_sex']) ? $_POST['pet_sex'] : '',
						'id'       => !empty( $_POST['pet_id']) ? $_POST['pet_id'] : '',
					);
					$this->db_interface_pets->pet_db_update( $pet );					
				}				
			}
			if(!empty($_REQUEST['redirect_url'])) {
				$edit_page_url = esc_url($_REQUEST['redirect_url']);
				wp_redirect($edit_page_url);
				exit;
			}			
		}
	}

}