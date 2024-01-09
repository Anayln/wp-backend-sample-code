<?php
class Pets_edit {

    private $screen_id;  
    private $db_interface_pets;
    public function __construct($db_pets) {
                
		$this->db_interface_pets = $db_pets;        
        $current_screen = get_current_screen();
        $this->screen_id = $current_screen->id;    

	}
    /**
     * Render order edit page.
     */
    public function display() {

        $action  = !empty( $_GET['action'] ) ?  $_GET['action'] : '';
        $p_id =  !empty($_GET['p_id']) ? $_GET['p_id'] : 0;
        $pet_data = array();
        if(!empty($p_id) && $action == 'edit_pet') {
            $pet_data = $this->db_interface_pets->get_single_pet(intval($_GET['p_id']));
        }
        if($action == 'edit_pet' && empty($pet_data)) { ?>
            <div class="wp-die-message"><?php _e('You attempted to edit an pet that does not exist. Perhaps it was deleted',VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN).'?'; ?></div>
            <?php
        } else {        
            $this->render_wrapper_start($pet_data);
            $this->render_meta_boxes($pet_data);
            $this->render_wrapper_end();
        }
    }
    private function render_wrapper_start($pet_data) {
        $add_new_url = add_query_arg('action','add_pet',menu_page_url('customer-pets-list',false));
        $menu_page_url = menu_page_url('customer-pets-list',false);
        $current_action  = !empty( $_REQUEST['action'] ) ?  $_REQUEST['action'] : '';    
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php
                echo 'add_pet' === $current_action ? 'Add New Pet' : 'Edit Pet';
                ?>
            </h1>
            <?php
            if ( 'edit_pet' === $current_action ) {
                echo ' <a href="' . esc_url( $add_new_url ) . '" class="page-title-action">Add Pet</a>';
            }
            ?>
            <hr class="wp-header-end">
            <form name="pet" action="" method="post" id="pet">
                <?php
                if($current_action === 'add_pet')  {
                    wp_nonce_field( 'new-pet-form-nonce');
                } else {?>
                    <input type="hidden" name='pet_id' value="<?php echo intval($pet_data['id']); ?>" >
                    <?php
                    wp_nonce_field( 'edit-pet-form-nonce');
                }?>
                <input type="hidden" name='redirect_url' value="<?php echo $menu_page_url; ?>" >
        <?php
    }

    private function render_meta_boxes($pet_data) { 
        ?>
        <div id="postbox-container-1" class="postbox-container">
            <?php do_meta_boxes( $this->screen_id, 'side', $pet_data ); ?>
        </div>
        <div id="postbox-container-2" class="postbox-container">
            <?php
            do_meta_boxes( $this->screen_id, 'normal',  $pet_data );
            do_meta_boxes( $this->screen_id, 'advanced',  $pet_data );
            ?>
        </div>    
        <?php
    }

    private function render_wrapper_end() {
        ?>
        </form>
        </div>
        <?php
    }
}

