<?php

class Pets_list extends WP_List_Table {
	private $table_data;
	private $db_interface_pets;

	function get_columns()
    {
        $columns = array(
				'id'       => __('ID', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'owner_email'  => __('Owner', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'pet_name'     => __('Pet Name', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'birthday' => __('Birthday', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'species'   => __('Animal', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'breed'    => __('Breed', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'sex'      => __('Sex', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'active'   => __('Status', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
				'manage'   => __('manage', VET_PHARMACY_PRESCRIPTIONS_TEXTDOMAIN),
        );
        return $columns;
    }
	function prepare_items()
    {			

		$per_page = $this->get_items_per_page('edit_pets_per_page',10);
		$current_page = $this->get_pagenum();
		$offset = (($current_page - 1) * $per_page);
		$limit = $per_page;

		$db_interface_pets = new DB_Interface_Pets();		
		if ( isset($_POST['s']) ) {			
            $this->table_data = $db_interface_pets->get_all_pets($limit,$offset,$_POST['s']);
        } else {
            $this->table_data = $db_interface_pets->get_all_pets($limit,$offset,'');
        }
        $columns = $this->get_columns();
		$hidden = array();
		$hidden = ( is_array(get_user_meta( get_current_user_id(), 'managewoocommerce_page_customer-pets-listcolumnshidden', true)) ) ? get_user_meta( get_current_user_id(), 'managewoocommerce_page_customer-pets-listcolumnshidden', true) : array();

        $sortable = $this->get_sortable_columns();
		$primary = 'id';
		$this->_column_headers = array($columns, $hidden, $sortable,$primary);
		usort($this->table_data, array(&$this, 'usort_reorder'));
		
		if ( isset($_POST['s']) ) {			
            $total_items  = $db_interface_pets->get_total_number_of_pets($_POST['s']);
        } else {
            $total_items = $db_interface_pets->get_total_number_of_pets();
        }
		$this->set_pagination_args(array(
			'total_items' => $total_items, // total number of items
			'per_page'    => $per_page, // items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
		));

		$this->items = $this->table_data;  

    }
	function column_default($item, $column_name)
    {
          switch ($column_name) {
				case 'cb':
				case 'id':
                case 'owner_email':
                case 'pet_name':
                case 'birthday':
				case 'species':
				case 'breed':
				case 'sex':
				case 'active':
				case 'manage':
                default:
                    return $item[$column_name];
          }
    } 

	function column_cb($item)
    {
        return sprintf(
                '<input type="checkbox" name="element[]" value="%s" />',
                $item['id']
        );
    }

	function column_birthday($item) {		
		$data = !empty( $item['birthday'] ) ? date('d/m/Y',strtotime($item['birthday'])) : '';
		return $data;
	}
	function column_manage($item)
    {	
		$data = '';
		if ( $item['active'] === '1' ) {
			$data.= '<form method="POST" id="deleteAPet">';
			$data.= wp_nonce_field( 'delete-a-pet-nonce' . $item['id'] );
			$data.= '<input type="hidden" name="delete_a_pet" value="' . $item['id'] . '">';
			$data.= '<input type="submit" value="Delete"></form>';
		} else {
			$data.= '<form method="POST" id="restoreAPet">';
			$data.= wp_nonce_field( 'restore-a-pet-nonce' . $item['id'] );
			$data.= '<input type="hidden" name="restore_a_pet" value="' . $item['id'] . '">';
			$data.= '<input type="submit" value="Restore"></form>';
		}
		return $data;
    }
	function column_active($item)
    {	
		$data = '';
		if ( $item['active'] === '1' ) {
			$data .= 'Active';
		} else {
			$data .= 'Deleted';
		}
		return $data;
    }

	function column_owner_email($item)
    {	
		$data = '';
		$data .= '<a href="user-edit.php?user_id=' . $item['owner_id'] . '">' . $item['owner_email'] . '</a>';
		return $data;
    }

	protected function get_sortable_columns()
	{
		$sortable_columns = array(
				'id'  => array('id', true),
				'pet_name'  => array('pet_name', true),
				'sex'  => array('sex', true),
		);
		return $sortable_columns;
	}

	function usort_reorder($a, $b)
	{
		$orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
		$order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
		$result = strcmp($a[$orderby], $b[$orderby]);
		return ($order === 'asc') ? $result : -$result;
	}

	function pets_sample_screen_options() {

		global $customer_pets_page;
		global $table;	
		$screen = get_current_screen();

		if(!is_object($screen) || $screen->id != $customer_pets_page)
			return;
	
		$args = array(
			'default' => 2,
			'option' => 'edit_pets_per_page'
		);
		add_screen_option( 'per_page', $args );		
		$table = new Pets_list();
	}
	// Adding action links to column
	function column_id($item)
	{
		$actions = array(
				'edit'      => sprintf('<a href="?page=%s&action=%s&p_id=%s">' . __('Edit', 'supporthost-admin-table') . '</a>', $_REQUEST['page'], 'edit_pet', $item['id']),
		);

		return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions));
	}	  
}