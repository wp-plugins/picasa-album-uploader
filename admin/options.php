<?php
/**
 * Class to manage options
 *
 * @package Picasa Album Uploader
 * @author Kenneth J. Brucker <ken@pumastudios.com>
 * @copyright 2010 Kenneth J. Brucker (email: ken@pumastudios.com)
 * 
 * This file is part of Picasa Album Uploader, a plugin for Wordpress.
 *
 * Picasa Album Uploader is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Picasa Album Uploader is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Picasa Album Uploader.  If not, see <http://www.gnu.org/licenses/>.
 **/

class picasa_album_uploader_options
{
	/**
	 * slug used to detect pages requiring plugin action
	 *
	 * @var string slug name
	 * @access public
	 **/
	public $slug;
	
	/**
	 * When errors are detected in the module, this variable will contain a text description
	 *
	 * @var string Error Message
	 * @access public
	 **/
	public $error;
	
	/**
	 * Class Constructor function
	 *
	 * Setup plugin defaults and register with WordPress for use in Admin screens
	 **/
	function picasa_album_uploader_options()
	{
		// Retrieve Plugin Options
		$options = get_option('pau_plugin_settings');
		
		// Init value for slug name - supply default if undefined
		$this->slug = $options['slug'] ? $options['slug'] : 'picasa_album_uploader';
		
		// Init value for error log
		$this->debug_log_enabled = $options['debug_log_enabled'] ? $options['debug_log_enabled'] : 0;
		$this->debug_log = $options['debug_log'] ? $options['debug_log'] : array();
		
		// When displaying admin screens ...
		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'pau_settings_admin_init' ) );

			// Add section for reporting configuration errors
			add_action('admin_footer', array( &$this, 'pau_admin_notice'));			
		}

		// If logging is enabled, setup save in the footers.
		if ($this->debug_log_enabled) {
			add_action('admin_footer', array( &$this, 'save_debug_log'));
			add_action('wp_footer', array( &$this, 'save_debug_log'));				
		}
	}
		
	/**
	 * Register the plugin settings options when running admin_screen
	 **/
	function pau_settings_admin_init ()
	{
		// Add settings section to the 'media' Settings page
		add_settings_section( 
				'pau_settings_section', 
				'Picasa Album Uploader Settings', 
				array( &$this, 'settings_section_html'), 
				'media' );
		
		// Add slug name field to the plugin admin settings section
		add_settings_field( 
				'pau_plugin_settings[slug]', 
				'Slug', 
				array( &$this, 'slug_html' ), 
				'media', 
				'pau_settings_section' );
		
		// Add Plugin Error Logging
		add_settings_field( 
				'pau_plugin_settings[debug_log_enabled]', 
				'Enable Debug Log', 
				array( &$this, 'debug_log_enabled_html'), 
				'media', 
				'pau_settings_section' );
		add_settings_field(
				'pau_plugin_settings[debug_log]',
				array( &$this, 'debug_log_html'),
				'media',
				'pau_settings_section' );
		
		// Register the slug name setting;
		register_setting( 'media', 'pau_plugin_settings', array (&$this, 'sanitize_settings') );
		
		// TODO Need an unregister_setting routine for de-install of plugin
	}
	
	/**
	 * Display Notice messages at head of admin screen
	 *
	 * @return void
	 **/
	function pau_admin_notice()
	{
		if ( get_option('permalink_structure') == '' ) {
			echo '<div class="error"><p>';
			printf(__('%1$s requires the use of %2$s', 'picasa-album-uploader'), '<a href="options-media.php">' . PAU_PLUGIN_NAME . '</a>', '<a href="options-permalink.php">Permalinks</a>');
			echo '</p></div>';
		}
		
		if ( $this->debug_log_enabled ) {
			echo '<div class="error"><p>';
			printf(__('%s logging is enabled.  If left enabled, this can affect database performance.', 'picasa-album-uploader'),'<a href="options-media.php">' . PAU_PLUGIN_NAME . '</a>');
			echo '</p></div>';
		}
	}
	
	/**
	 * Sanitize the Plugin Options received from the user
	 *
	 * @return hash Sanitized hash of plugin options
	 **/
	function sanitize_settings($options)
	{
		// Slug must be alpha-numeric, dash and underscore.
		$slug_pattern[0] = '/\s+/'; 						// Translate white space to a -
		$slug_replacement[0] = '-';
		$slug_pattern[1] = '/[^a-zA-Z0-9-_]/'; 	// Only allow alphanumeric, dash (-) and underscore (_)
		$slug_replacement[1] = '';
		$options['slug'] = preg_replace($slug_pattern, $slug_replacement, $options['slug']);
		
		// Cleanup error log if it's disabled
		if ( ! $options['debug_log_enabled'] ) {
			$options['debug_log'] = array();
		}

		return $options;
	}
	
	/**
	 * Emit HTML to create a settings section for the plugin in admin screen.
	 **/
	function settings_section_html()
	{	
		echo '<p>';
		_e('To use the Picasa Album Uploader, install the Button in Picasa Desktop using this automated install link:', 'picasa-album-uploader');
		echo '</p>';
		// Display button to download the Picasa Button Plugin
		echo do_shortcode( "[picasa_album_uploader_button]" );
	}
	
	/**
	 * Emit HTML to create form field for slug name
	 **/
	function slug_html()
	{ 
		echo '<input type="text" name="pau_plugin_settings[slug]" value="' . $this->slug . '" />';
		echo '<p>';
		_e('Set the slug used by the plugin.  Only alphanumeric, dash (-) and underscore (_) characters are allowed.  White space will be converted to dash, illegal characters will be removed.', 'picasa-album-uploader');
		echo '<br />';
		_e('When the slug name is changed, a new button must be installed in Picasa to match the new setting.', 'picasa-album-uploader');
		echo '</p>';
	}
	
	/**
	 * Emit HTML to create form field used to enable/disable Debug Logging
	 **/
	function debug_log_enabled_html()
	{ 
		$checked = $this->debug_log_enabled ? "checked" : "" ;
		echo '<input type="checkbox" name="pau_plugin_settings[debug_log_enabled]" value="1" ' . $checked . '>';
		_e('Enable Plugin Debug Logging. When enabled, log will display below.', 'picasa-album-uploader');
		if ( $this-> debug_log_enabled ) {
			echo "<div class=pau-error-log>";
			foreach ($this->debug_log as $line) {
				echo "$line<br/>\n";
			}
			echo "</div>";
		}
	}
	
	/**
	 * Log an error message for display
	 **/
	function debug_log($msg)
	{
		if ( $this->debug_log_enabled )
			array_push($this->debug_log, date("Y-m-d H:i:s") . " " . $msg);
	}
	
	/**
	 * Save the error log if it's enabled
	 **/
	function save_debug_log()
	{
		if ( $this->debug_log_enabled ) {
			$options = get_option('pau_plugin_settings');
			$options['debug_log'] = $this->debug_log;
			update_option('pau_plugin_settings', $options);
		}
	}
	
	/**
	 * Log errors to server log and debug log
	 *
	 * @return void
	 * @author Kenneth J. Brucker <ken@pumastudios.com>
	 **/
	function error_log($msg)
	{
		error_log(PAU_PLUGIN_NAME . ": " . $msg);
		$this->debug_log($msg);
	}
} // END class 
?>
