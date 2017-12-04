<div class="wrap">
    <h1>Facebook Page Postman</h1>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
            settings_fields('fbpp_options_group');
            do_settings_sections('fbpp_plugin');
            submit_button();
        ?>
    </form>
</div>
