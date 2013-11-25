<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
global $SE_Keywords;
$options = $SE_Keywords->get_data();
?>
<div class="settings-section">
    <fieldset id="multiple-words-zone">
    <legend><?php _e('Multiple words results', SEK_TEXTDOMAIN); ?></legend>
        <label for="multiple-words"><?php _e('Return multiple words matches as an array instead of single string', SEK_TEXTDOMAIN); ?></label>
        <?php settings_fields('sek_options_group'); ?>
        <?php do_settings_fields('sek_options_page', 'sek_multiplewords_section'); ?>
        <input type="submit" id="save-multiplewords" class="button-primary" name="sek_options[save_multiplewords]" value="<?php echo $SE_Keywords->attr_texts['save']; ?>" />
    </fieldset>
</div><!-- .settings-section -->
<div class="settings-section">
    <h3><?php _e('Search and replace some characters in the query before parsing :', SEK_TEXTDOMAIN); ?></h3>
    <table class="widefat" id="repl-table">
        <thead>
            <tr>
                <th><?php _e('Searches', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Replacements', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Actions', SEK_TEXTDOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (array() == $options['chars-filter']) : ?>
                <tr><td colspan="5"><p class="nothing"><?php _e('There is no registered replacements.', SEK_TEXTDOMAIN); ?></p></td><tr>
            <?php else : ?>
                <?php foreach ($options['chars-filter'] as $key => $value) :?>
                    <tr>
                        <td>'<span class="big"><?php echo $value['search']; ?></span>'</td>
                        <td class="alt">'<span class="big"><?php echo $value['repl']; ?></span>'</td>
                        <td><input type="submit" data-target="<?php echo esc_attr($key); ?>" name="sek_options[delete_repl]" class="button-primary secondary-submit" value="<?php echo $SE_Keywords->attr_texts['rem']; ?>"  /></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <fieldset id="charsreplace-zone">
        <legend><?php _e('Add replacement', SEK_TEXTDOMAIN); ?></legend>
        <?php settings_fields('sek_options_group'); ?>
        <?php do_settings_fields('sek_options_page', 'sek_charsreplace_section'); ?>
        <input type="submit" id="add-repl" class="button-primary" name="sek_options[add_repl]" value="<?php echo $SE_Keywords->attr_texts['add_repl']; ?>" />
    </fieldset><!-- #charsreplace-zone -->
<div class="settings-section">
</div><!-- .settings-section -->
    <h3><?php _e('Search Engines :', SEK_TEXTDOMAIN); ?></h3>
    <table class="widefat" id="SE-table">
        <thead>
            <tr>
                <th><?php _e('Search Engines Name', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Domain Name', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Query variable', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Status', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Actions', SEK_TEXTDOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (array() == $options['SE']) : ?>
                <tr><td colspan="5"><p class="nothing"><?php _e('There is no registered Search Engine.', SEK_TEXTDOMAIN); ?></p></td><tr>
            <?php else : ?>
                <?php foreach ($options['SE'] as $key => $value) : ?>
                    <tr>
                        <td><?php echo $value['name']; ?></td>
                        <td class="alt"><code><?php echo $key; ?></code></td>
                        <td><code><?php echo $value['query']; ?></code></td>
                        <td class="alt"><?php echo $SE_Keywords->status($value['status'], __('Listen', SEK_TEXTDOMAIN), __('Down', SEK_TEXTDOMAIN)); ?></td>
                        <td>
                            <input data-target="<?php echo esc_attr($key); ?>" type="submit" name="sek_options[delete_se]" class="button-primary secondary-submit" value="<?php echo $SE_Keywords->attr_texts['rem']; ?>" />
                            <?php if ($value['status']) : ?>
                                <input data-target="<?php echo esc_attr($key); ?>" type="submit" name="sek_options[deactivate_se]" class="button secondary-submit" value="<?php echo $SE_Keywords->attr_texts['deact']; ?>" />
                            <?php else : ?>
                                <input data-target="<?php echo esc_attr($key); ?>" type="submit" name="sek_options[activate_se]" class="button secondary-submit" value="<?php echo $SE_Keywords->attr_texts['act']; ?>" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table><!-- .widefat -->
    <fieldset id="se-zone">
        <legend><?php _e('New Search Engine', SEK_TEXTDOMAIN); ?></legend>
        <?php settings_fields('sek_options_group'); ?>
        <?php do_settings_fields('sek_options_page', 'sek_se_section'); ?>
        <div class="clear-before">
            <input type="submit" id="add-se" class="button-primary" name="sek_options[add_se]" value="<?php echo $SE_Keywords->attr_texts['add_se']; ?>" />
        </div><!-- .clear-before -->
    </fieldset><!-- #se-zone -->
</div><!-- .settings-section -->
