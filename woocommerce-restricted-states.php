<?php
/**
 * Plugin Name:         WooCommerce Restrict States
 * Plugin URI:          https://github.com/brandomeniconi/woocommerce-restrict-states
 * Description:         Allow to restrict WooCommerce billing and shipping to some specific states
 * Author:              Brando Meniconi
 * Author URI:          https://github.com/brandomeniconi/
 * Text Domain:         woocommerce-restrict-states
 * Domain Path:         /languages
 * Version:             0.1.0
 * GitHub Plugin URI:   brandomeniconi/woocommerce-restrict-states
 *
 * @package             Woocommerce_Restrict_States
 */

add_action( 'init', 'wrs_load_textdomain' );

/**
 * Load plugin textdomain.
 */
function wrs_load_textdomain() {
	load_plugin_textdomain( 'woocommerce-restrict-states', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_filter( 'woocommerce_countries_allowed_country_states', 'wrs_countries_allowed_country_states' );

/**
 * Filter: the allowed country states
 *
 * @param  array $countries_allowed_country_states The country/states to be filtered.
 * @return array
 */
function wrs_countries_allowed_country_states( $countries_allowed_country_states ) {
	if ( get_option( 'woocommerce_allowed_countries' ) !== 'specific' ) {
		return $countries_allowed_country_states;
	}

	$allowed_states = WC_Admin_Settings::get_option( 'woocommerce_specific_allowed_states', array() );

	$countries_allowed_country_states = array();

	foreach ( $allowed_states as $state_string ) {
		$state_info = wc_format_country_state_string( $state_string );
		if ( isset( WC()->countries->states[ $state_info['country'] ][ $state_info['state'] ] ) ) {
			$countries_allowed_country_states[ $state_info['country'] ][ $state_info['state'] ] = WC()->countries->states[ $state_info['country'] ][ $state_info['state'] ];
		}
	}

	return $countries_allowed_country_states;
}

add_filter( 'woocommerce_get_settings_general', 'wrs_general_settings' );

/**
 * Add setting field to WooCommerce options
 *
 * @param  array $settings The settings fields to be filtered.
 * @return array
 */
function wrs_general_settings( $settings ) {
	if ( get_option( 'woocommerce_allowed_countries' ) !== 'specific' ) {
		return $settings;
	}

	$insert_position = array_search( 'woocommerce_specific_allowed_countries', array_column( $settings, 'id' ), true );

	$allowed_states_setting = array(
		'title'   => __( 'Sell to specific states', 'woocommerce-restrict-states' ),
		'desc'    => __( 'Select the states where you want to sell', 'woocommerce-restrict-states' ),
		'id'      => 'woocommerce_specific_allowed_states',
		'default' => '',
		'type'    => 'multi_select_states',
	);

	array_splice( $settings, ++$insert_position, 0, array( $allowed_states_setting ) );

	return $settings;
}

add_filter( 'woocommerce_admin_field_multi_select_states', 'wrs_field_multi_select_states' );

/**
 * Render a custom 'multi_select_states' field
 *
 * @param  array $value The field incoming values.
 * @return void
 */
function wrs_field_multi_select_states( $value ) {
	$description  = '';
	$tooltip_html = '';

	$allowed_countries = WC()->countries->get_allowed_countries();

	$selections = (array) $value['value'];

	if ( true === $value['desc_tip'] ) {
		$tooltip_html = $value['desc'];
	} elseif ( ! empty( $value['desc_tip'] ) ) {
		$description  = $value['desc'];
		$tooltip_html = $value['desc_tip'];
	} elseif ( ! empty( $value['desc'] ) ) {
		$description = $value['desc'];
	}

	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
		</th>
		<td class="forminp">
			<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose state / regions&hellip;', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>" aria-label="<?php esc_attr_e( 'Country / Region', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>" class="wc-enhanced-select">
				<?php
				if ( ! empty( $allowed_countries ) ) {
					foreach ( $allowed_countries as $country_id => $country_name ) {
						echo '<optgroup label="' . esc_attr( $country_name ) . '" id="' . esc_attr( $value['id'] . '_' . $country_id ) . '" >'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped.

						$states = WC()->countries->get_states( $country_id );

						if ( ! empty( $states ) ) {
							foreach ( $states as $state_id => $state_name ) {
								$state_unique = $country_id . ':' . $state_id;
								echo '<option value="' . esc_attr( $state_unique ) . '"' . wc_selected( $state_unique, $selections ) . '>' . esc_html( $state_name ) . '</option>'; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch,WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}

						echo '</optgroup>';
					}
				}
				?>
			</select> 
			<br />
			<a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></a> 
			<a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></a>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><p>
			<?php endif; ?>			
		</td>
	</tr>
	<?php
}



add_filter( 'woocommerce_admin_settings_sanitize_option', 'wrs_admin_field_sanitize', 10, 3 );

/**
 * Sanitize custom admin fields on save
 *
 * @param  mixed $value     The incoming field value.
 * @param  array $option    The field options.
 * @param  array $raw_value The raw field value.
 * @return mixed
 */
function wrs_admin_field_sanitize( $value, $option, $raw_value ) {
	if ( 'multi_select_states' === $option['type'] ) {
		$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
	}

	return $value;
}
