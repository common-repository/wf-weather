<?php

class WfStwSettingsPage
{

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('WF Weather Settings','wf-weather'),
            __('WF Weather','wf-weather'),
            'manage_options',
            'wf-weather',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = [
          'wf-weather-general' => get_option( 'wf-weather-general' )
        ];

        ?>
        <div class="wrap">
            <h2><?php _e('WF Weather','wf-weather') ?></h2>
            <h3><?php _e('Available Shortcodes','wf-weather') ?></h3>
            <p>
              [wf_weather_text]<br />
              [wf_weather_forecast]<br />
              [wf_weather_forecast district="1"]<br />
            </p>
            <hr>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wf-weather' );
                do_settings_sections( 'wf-weather' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
      register_setting(
          'wf-weather', // Option group
          'wf-weather-general', // Option name
          array( $this, 'sanitize' ) // Sanitize
      );

      add_settings_section(
          'wf-weather-general', // ID
          __('General Options','wf-weather'), // Title
          array( $this, 'print_cache_section_info' ), // Info Callback
          'wf-weather' // Page
      );

      add_settings_field(
          'apiUsername',
          __('API-Username','wf-weather'),
          array( $this, 'render_text_input' ),
          'wf-weather',
          'wf-weather-general',
          array(
              'option' => 'wf-weather-general',
              'field' => 'apiUsername'
          )
      );

      add_settings_field(
          'apiPassword',
          __('API-Password','wf-weather'),
          array( $this, 'render_password_input' ),
          'wf-weather',
          'wf-weather-general',
          array(
              'option' => 'wf-weather-general',
              'field' => 'apiPassword'
          )
      );

      add_settings_field(
          'cacheExpiration',
          __('Cache Expiration','wf-weather'),
          array( $this, 'render_text_input' ),
          'wf-weather',
          'wf-weather-general',
          array(
              'option' => 'wf-weather-general',
              'field' => 'cacheExpiration'
          )
      );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        foreach ($input as $key => $value) {
            $input[$key] = $value == '1' ? true : $value;
        }
        return $input;
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function render_checkbox($args)
    {
        printf(
            '<input type="checkbox" name="%s[%s]" %s value="1" />',
            $args['option'],
            $args['field'],
            isset( $this->options[$args['option']][$args['field']] ) ? 'checked' : ''
        );
    }

    /**
    * Get the settings option array and print one of its values
    * render_text_input is a input field
    */
    public function render_text_input($args)
    {
      printf(
          '<input type="text" name="%s[%s]" value="%s" />',
          $args['option'],
          $args['field'],
          isset($this->options[$args['option']][$args['field']]) ? $this->options[$args['option']][$args['field']] : ''
      );
    }

    /**
    * Get the settings option array and print one of its values
    * render_password_input is a password field
    */
    public function render_password_input($args)
    {
      printf(
          '<input type="password" name="%s[%s]" value="%s" />',
          $args['option'],
          $args['field'],
          isset($this->options[$args['option']][$args['field']]) ? $this->options[$args['option']][$args['field']] : ''
      );
    }

    /**
     * Print the Cache Section text
     */
    public function print_cache_section_info()
    {
      _e('Here you can change some stuff or leave the default options.','wf-weather');
    }

    public function encrypt($input_string, $key)
    {
      $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
      $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
      $h_key = hash('sha256', $key, TRUE);
      return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
    }

    public function decrypt($encrypted_input_string, $key)
    {
      $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
      $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
      $h_key = hash('sha256', $key, TRUE);
      return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
    }

}

if ( is_admin() ) {
    $my_settings_page = new WfStwSettingsPage();
}
