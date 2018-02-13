<div class="wrap">
    <h1>Facebook Page Postman</h1>
    <?php settings_errors(); ?>

    <?php 
        if (isset($_POST['fbpp_start']) && check_admin_referer('fbpp_button_start')) {
            // Start the cron event.
            if (!wp_next_scheduled('fbpp_refresh_event')) {
                wp_schedule_event(time(), 'fbpp_30min', 'fbpp_refresh_event');

                $html = '<div id="message" class="updated notice is-dismissible"><p>';
                $html .= __('Success. The process will now run once every 30min.', 'fbpp-textd');
                $html .= '</p></div>';
                echo $html;
            }
        }

        if (isset($_POST['fbpp_manual']) && check_admin_referer('fbpp_run_manual')) {
            // Run cron event once (manual run)
            wp_schedule_single_event(time() - 1, 'fbpp_refresh_event');
            spawn_cron();

            $html = '<div id="message" class="updated notice is-dismissible"><p>';
            $html .= __('Success', 'fbpp-textd');
            $html .= '</p></div>';
            echo $html;
        }
    ?>

    <?php 
        // Time when next refresh event is scheduled to occur.
        // If false, event is not scheduled/doesnt exist.
        $cronTime = wp_next_scheduled('fbpp_refresh_event');

        if (!$cronTime): 
    ?>
        <form method="post" action="">
            <?php wp_nonce_field('fbpp_button_start'); ?>
            <input type="hidden" value="true" name="fbpp_start">

            <p>
                <?php submit_button(__('Start process', 'fbpp-textd'), array('primary', 'large'), 'fbpp-run-start', false); ?>
                <label for="fbpp-run-start">&nbsp;&nbsp;&nbsp;
                    <?php _e('The process will execute and then repeat once every 30 minutes.', 'fbpp-textd'); ?>
                </label>
            </p>
        </form>
        <hr>
    <?php else: ?>
        <p><?php _e('Next refresh event in:', 'fbpp_textd'); ?> 
            <?php 
                $timeDiff = ($cronTime - time());
                if ($timeDiff < 0) {
                    _e('N/A', 'fbpp-textd');
                } else {
                    $timeSec = $timeDiff % 60;
                    $timeMin = floor($timeDiff / 60);
                    echo $timeMin . 'min ' . $timeSec . 's';
                }
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('fbpp_run_manual'); ?>
                <input type="hidden" value="true" name="fbpp_manual">
                <?php  submit_button(__('Run now.', 'fbpp-textd'), 'secondary', 'fbpp-run-now', false); ?>
            </form>
        </p>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
            settings_fields('fbpp_options_group');
            do_settings_sections('fbpp_plugin');
            submit_button();
        ?>
    </form>
</div>
