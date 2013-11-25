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
    <h3><?php _e('Patterns :', SEK_TEXTDOMAIN); ?></h3>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Patterns', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Status', SEK_TEXTDOMAIN); ?></th>
                <th><?php _e('Actions', SEK_TEXTDOMAIN); ?></th>
            </tr>
            </thead>
        <tbody>
            <?php if (array() == $options['pattern']) : ?>
                <tr><td colspan="3"><p class="nothing"><?php _e('There is no registered pattern yet.', SEK_TEXTDOMAIN); ?></p></td><tr>
            <?php else : ?>
                <?php foreach ($options['pattern'] as $key => $value) : ?>
                    <tr>
                        <td><code class="pattern"><?php echo $value['pattern']; ?></code></td>
                        <td class="alt"><?php echo $SE_Keywords->status($value['status'], __('Active', SEK_TEXTDOMAIN), __('Inactive', SEK_TEXTDOMAIN)); ?></td>
                        <td>
                            <input data-target="<?php esc_attr_e($key); ?>" type="submit" name="sek_options[delete_pattern]" class="button-primary secondary-submit" value="<?php echo $SE_Keywords->attr_texts['rem']; ?>" />
                            <?php if ($value['status']) : ?>
                                <input data-target="<?php esc_attr_e($key); ?>" type="submit" name="sek_options[deactivate_pattern]" class="button secondary-submit" value="<?php echo $SE_Keywords->attr_texts['deact']; ?>" />
                            <?php else : ?>
                                <input data-target="<?php esc_attr_e($key); ?>" type="submit" name="sek_options[activate_pattern]" class="button secondary-submit" value="<?php echo $SE_Keywords->attr_texts['act']; ?>" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table><!-- .widefat -->
    <fieldset id="pattern-zone">
        <legend><?php _e('New Pattern', SEK_TEXTDOMAIN); ?></legend>
        <?php settings_fields('sek_options_group'); ?>
        <?php do_settings_fields('sek_options_page', 'sek_pattern_section'); ?>
        <input type="submit" id="add-pattern" class="button-primary" name="sek_options[add_pattern]" value="<?php echo $SE_Keywords->attr_texts['add_pattern']; ?>" />
    </fieldset><!-- #pattern-zone -->
</div><!-- .setting-section -->
