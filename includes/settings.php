<?php
class PepperSettings
{
	private $pepper_settings_options;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'pepper_settings_add_plugin_page'));
		add_action('admin_init', array($this, 'pepper_settings_page_init'));
	}

	public function pepper_settings_add_plugin_page()
	{
		add_menu_page(
			'Pepper Settings', // page_title
			'Pepper Settings', // menu_title
			'manage_options', // capability
			'pepper-connect', // menu_slug
			array($this, 'pepper_settings_create_admin_page'), // function
			'dashicons-admin-generic', // icon_url
			2 // position
		);
	}

	public function pepper_settings_create_admin_page()
	{
		$this->pepper_settings_options = get_option('pepper_settings_option_name'); ?>

		<div class="wrap">
			<?php if (!isset($_GET[sanitize_key('key')])) { ?>
				<h2>Pepper Settings</h2>
				<p></p>
				<?php settings_errors(); ?>

				<form method="post" action="options.php">
					<?php
					settings_fields('pepper_settings_option_group');
					do_settings_sections('pepper-settings-admin');
					submit_button();
					?>
				</form>
			<?php } else {
				$key = $_GET[sanitize_key('key')];
				$company_id = $_GET[sanitize_key('company_id')];
				$webhook_key = $_GET[sanitize_key('webhook_key')];
				$keys = array();
				$prevkeys = get_option('pepper_settings_option_name_key');
				foreach ($prevkeys as $pk) {
					if (!in_array($pk, $keys)) {
						$keys[] = $pk;
					}
				}
				$keys[] = wp_hash_password($key);
				update_option('pepper_settings_option_name_key', $keys);

				$wehbook_keys = array();
				$prev_webhook_keys = get_option('pepper_settings_option_name_webhook_key');
				foreach ($prev_webhook_keys as $pwk => $pwv) {
						$webhook_keys[$pwk] = $pwv;
				}
				$webhook_keys[$company_id] = $webhook_key;
				update_option('pepper_settings_option_name_webhook_key', $webhook_keys);

				$redirect_url = $_GET[sanitize_key('redirect_url')];
				$site_url = get_site_url();
			?>
				<script>
					window.location.replace('<?php echo esc_url($redirect_url); ?>?access_token=' + '<?php echo esc_html($key); ?>&source=<?php echo esc_html($site_url); ?>&webhook_token=<?php echo esc_html($webhook_key); ?>');
				</script>
			<?php
			}
			?>
		</div>
	<?php }

	public function pepper_settings_page_init()
	{
		register_setting(
			'pepper_settings_option_group', // option_group
			'pepper_settings_option_name', // option_name
			array($this, 'pepper_settings_sanitize') // sanitize_callback
		);

		add_settings_section(
			'pepper_settings_setting_section', // id
			'Settings', // title
			array($this, 'pepper_settings_section_info'), // callback
			'pepper-settings-admin' // page
		);



		add_settings_field(
			'status_1', // id
			'Status', // title
			array($this, 'status_1_callback'), // callback
			'pepper-settings-admin', // page
			'pepper_settings_setting_section' // section
		);
	}

	public function pepper_settings_sanitize($input)
	{
		$sanitary_values = array();
		if (isset($input['pepper_connector_key_0'])) {
			$sanitary_values['pepper_connector_key_0'] = sanitize_text_field($input['pepper_connector_key_0']);
		}

		if (isset($input['status_1'])) {
			$sanitary_values['status_1'] = $input['status_1'];
		}

		return $sanitary_values;
	}

	public function pepper_settings_section_info()
	{
	}

	public function pepper_connector_key_0_callback()
	{
	}

	public function status_1_callback()
	{
	?>
		<select name="pepper_settings_option_name[status_1]" id="status_1">
			<?php $selected = (isset($this->pepper_settings_options['status_1']) && $this->pepper_settings_options['status_1'] == '1') ? 'selected' : ''; ?>
			<option value="1" <?php echo esc_attr($selected); ?>> Enable</option>
			<?php $selected = (isset($this->pepper_settings_options['status_1']) && $this->pepper_settings_options['status_1'] == '0') ? 'selected' : ''; ?>
			<option value="0" <?php echo esc_attr($selected); ?>> Disable</option>
		</select> <?php
				}
			}
			if (is_admin())
				$pepper_settings = new PepperSettings();

/*
 * Retrieve this value with:
 * $pepper_settings_options = get_option( 'pepper_settings_option_name' ); // Array of All Options
 * $pepper_connector_key_0 = $pepper_settings_options['pepper_connector_key_0']; // Pepper Connector Key
 * $status_1 = $pepper_settings_options['status_1']; // Status
 */
