<?php
/**
* Nav Menu Field v5
*
* @package ACF Nav Menu Field
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF_Field_Nav_Menu_V5 Class
 *
 * This class contains all the custom workings for the Nav Menu Field for ACF v5
 */
class ACF_Field_Nav_Menu_V5 extends acf_field {

	/**
	 * Sets up some default values and delegats work to the parent constructor.
	 */
	public function __construct() {
		$this->name     = 'nav_menu';
		$this->label    = __( 'Nav Menu' );
		$this->category = 'relational';
		$this->defaults = array(
			'save_format' => 'id',
			'allow_null'  => 0,
			'container'   => 'div',
		);

		parent::__construct();
	}

	/**
	 * Renders the Nav Menu Field options seen when editing a Nav Menu Field.
	 *
	 * @param array $field The array representation of the current Nav Menu Field.
	 */
	public function render_field_settings( $field ) {
		// Register the Return Value format setting
		acf_render_field_setting( $field, array(
			'label'        => __( 'Return Value' ),
			'instructions' => __( 'Specify the returned value on front end' ),
			'type'         => 'radio',
			'name'         => 'save_format',
			'layout'       => 'horizontal',
			'choices'      => array(
				'object' => __( 'Nav Menu Object' ),
				'menu'   => __( 'Nav Menu HTML' ),
			),
		) );

		// Register the Menu Container setting
		acf_render_field_setting( $field, array(
			'label'        => __( 'Menu Container' ),
			'instructions' => __( "What to wrap the Menu's ul with (when returning HTML only)" ),
			'type'         => 'select',
			'name'         => 'container',
			'choices'      => $this->get_allowed_nav_container_tags(),
		) );

		// Register the Allow Null setting
		acf_render_field_setting( $field, array(
			'label'        => __( 'Allow Null?' ),
			'type'         => 'radio',
			'name'         => 'allow_null',
			'layout'       => 'horizontal',
			'choices'      => array(
				1 => __( 'Yes' ),
				0 => __( 'No' ),
			),
		) );
	}

	/**
	 * Get the allowed wrapper tags for use with wp_nav_menu().
	 *
	 * @return array An array of allowed wrapper tags.
	 */
	private function get_allowed_nav_container_tags() {
		$tags           = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );
		$formatted_tags = array(
			'0' => 'None',
		);

		foreach ( $tags as $tag ) {
			$formatted_tags[$tag] = ucfirst( $tag );
		}

		return $formatted_tags;
	}

	/**
	 * Renders the Nav Menu Field.
	 *
	 * @param array $field The array representation of the current Nav Menu Field.
	 */
	public function render_field( $field ) {
		$allow_null = $field['allow_null'];
		$nav_menus  = $this->get_nav_menus( $allow_null );

		if ( empty( $nav_menus ) ) {
			return;
		}
		?>
		<select id="<?php esc_attr( $field['id'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>">
		<?php foreach( $nav_menus as $nav_menu_id => $nav_menu_name ) : ?>
			<option value="<?php echo esc_attr( $nav_menu_id ); ?>" <?php selected( $field['value'], $nav_menu_id ); ?>>
				<?php echo esc_html( $nav_menu_name ); ?>
			</option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Gets a list of Nav Menus indexed by their Nav Menu IDs.
	 *
	 * @param bool $allow_null If true, prepends the null option.
	 *
	 * @return array An array of Nav Menus indexed by their Nav Menu IDs.
	 */
	private function get_nav_menus( $allow_null = false ) {
		$navs = get_terms( 'nav_menu', array( 'hide_empty' => false ) );

		$nav_menus = array();

		if ( $allow_null ) {
			$nav_menus[''] = ' - Select - ';
		}

		foreach ( $navs as $nav ) {
			$nav_menus[ $nav->term_id ] = $nav->name;
		}

		return $nav_menus;
	}

	/**
	 * Renders the Nav Menu Field.
	 *
	 * @param int   $value   The Nav Menu ID selected for this Nav Menu Field.
	 * @param int   $post_id The Post ID this $value is associated with.
	 * @param array $field   The array representation of the current Nav Menu Field.
	 *
	 * @return mixed The Nav Menu ID, or the Nav Menu HTML, or the Nav Menu Object, or false.
	 */
	public function format_value( $value, $post_id, $field ) {
		// bail early if no value
		if ( empty( $value ) ) {
			return false;
		}

		// check format
		if ( 'object' == $field['save_format'] ) {

            $args = array(
                'order'=>'ASC',
                'orderby'=> 'menu_order',
                'output' => object);

            $array_menu = wp_get_nav_menu_items($value);
               $menu = array();
               foreach ($array_menu as $m) {
                   if (empty($m->menu_item_parent)) {
                       $menu[] = array(
                           'ID' => $m->ID,
                           'title' => $m->title,
                           'url' => $m->url,
                           'childern' => array(),
						);
                   }
               }

               foreach ($array_menu as $sm) {
                   if ($sm->menu_item_parent != 0) {
                       foreach($menu as $index=>$value) {
                           if ($sm->menu_item_parent == $value['ID']) {
                               $submenu_array = array(
                                   'ID' => $sm->ID,
                                   'title' => $sm->title,
                                   'url' => $sm->url,
                             );
                             array_push($menu[$index]['childern'], $submenu_array);

                           }
                       }

                   }

               }

               return $menu;

			// return $menu_object;
		} elseif ( 'menu' == $field['save_format'] ) {
			ob_start();

			wp_nav_menu( array(
				'menu' => $value,
				'container' => $field['container']
			) );

			return ob_get_clean();
		}

		// Just return the Nav Menu ID
		return $value;
	}
}

new ACF_Field_Nav_Menu_V5();
