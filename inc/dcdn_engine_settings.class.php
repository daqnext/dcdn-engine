<?php

/**
 * DCDN_Engine_Settings
 *
 * @since 0.0.1
 */

class DCDN_Engine_Settings
{


    /**
     * register settings
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function register_settings()
    {
        register_setting(
            'dcdn_engine',
            'dcdn_engine',
            [
                __CLASS__,
                'validate_settings',
            ]
        );
    }


    /**
     * validation of settings
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @param   array  $data  array with form data
     * @return  array         array with validated values
     */

    public static function validate_settings($data)
    {
        if (!isset($data['relative'])) {
            $data['relative'] = 0;
        }
        if (!isset($data['https'])) {
            $data['https'] = 0;
        }

        return [
            'url'             => esc_url($data['url']),
            'dirs'            => esc_attr($data['dirs']),
            'excludes'        => esc_attr($data['excludes']),
            'relative'        => (int)($data['relative']),
            'https'           => (int)($data['https']),
        ];
    }


    /**
     * add settings page
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function add_settings_page()
    {
        $page = add_options_page(
            'DCDN Engine',
            'DCDN Engine',
            'manage_options',
            'dcdn_engine',
            [
                __CLASS__,
                'settings_page',
            ]
        );
    }


    /**
     * settings page
     *
     * @since   0.0.1
     * @change  1.0.6
     *
     * @return  void
     */

    public static function settings_page()
    {
        $options = DCDN_Engine::get_options()


      ?>
        <div class="wrap">
           <h2>
               <?php _e("DCDN Engine Settings", "dcdn-engine"); ?>
           </h2>

           <?php
                {
                    printf(__('
           <div class="notice notice-info">
               <p>Combine DCDN Engine with <b><a href="%s">%s</a></b> for even faster WordPress performance.</p>
           </div>'), 'https://meson.network', 'Meson Network');
                }
            ?>

           <form method="post" action="options.php">
               <?php settings_fields('dcdn_engine') ?>

               <table class="form-table">

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("DCDN URL", "dcdn-engine"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="dcdn_engine_url">
                                   <input type="text" name="dcdn_engine[url]" id="dcdn_engine_url" value="<?php echo $options['url']; ?>" size="64" class="regular-text code" />
                               </label>

                               <p class="description">
                                   <?php _e("Enter the DCDN URL without trailing", "dcdn-engine"); ?> <code>/</code>
                               </p>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Included Directories", "dcdn-engine"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="dcdn_engine_dirs">
                                   <input type="text" name="dcdn_engine[dirs]" id="dcdn_engine_dirs" value="<?php echo $options['dirs']; ?>" size="64" class="regular-text code" />
                                   <?php _e("Default: <code>wp-content,wp-includes</code>", "dcdn-engine"); ?>
                               </label>

                               <p class="description">
                                   <?php _e("Assets in these directories will be pointed to the DCDN URL. Enter the directories separated by", "dcdn-engine"); ?> <code>,</code>
                               </p>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Exclusions", "dcdn-engine"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="dcdn_engine_excludes">
                                   <input type="text" name="dcdn_engine[excludes]" id="dcdn_engine_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
                                   <?php _e("Default: <code>.php</code>", "dcdn-engine"); ?>
                               </label>

                               <p class="description">
                                   <?php _e("Enter the exclusions (directories or extensions) separated by", "dcdn-engine"); ?> <code>,</code>
                               </p>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Relative Path", "dcdn-engine"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="dcdn_engine_relative">
                                   <input type="checkbox" name="dcdn_engine[relative]" id="dcdn_engine_relative" value="1" <?php checked(1, $options['relative']) ?> />
                                   <?php _e("Enable DCDN for relative paths (default: enabled).", "dcdn-engine"); ?>
                               </label>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("DCDN HTTPS", "dcdn-engine"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="dcdn_engine_https">
                                   <input type="checkbox" name="dcdn_engine[https]" id="dcdn_engine_https" value="1" <?php checked(1, $options['https']) ?> />
                                   <?php _e("Enable DCDN for HTTPS connections (default: disabled).", "dcdn-engine"); ?>
                               </label>
                           </fieldset>
                       </td>
                   </tr>

               </table>

               <?php submit_button() ?>
           </form>
        </div><?php
    }
}
