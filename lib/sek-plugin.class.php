<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('SEK_PLUGIN')) {
    class SEK_PLUGIN
    {
        // The data object
        private $data_obj = null;
        
        // The pattern object
        private $pattern_obj = null;
        
        private $api = null;
        
        private $pattern_candidate = '';
        
        public $attr_texts = array();
        
        /**
        * The constructor
        */
        public function __construct()
        {
            // Admin zone
            if (is_admin()) {
                $this->data_obj = new SEK_DATA();
                $this->pattern_obj = new SEK_PATTERN($this->data_obj);
                if (isset($_SESSION['SEKeywords']['pattern_candidate'])) {
                    $this->pattern_candidate = $_SESSION['SEKeywords']['pattern_candidate'];
                    unset($_SESSION['SEKeywords']['pattern_candidate']);
                }
                
                $this->attr_texts['add_pattern'] = esc_attr(__('Add Pattern', SEK_TEXTDOMAIN));
                $this->attr_texts['add_se'] = esc_attr(__('Add Search Engine', SEK_TEXTDOMAIN));
                $this->attr_texts['add_repl'] = esc_attr(__('Add replacement', SEK_TEXTDOMAIN));
                $this->attr_texts['rem'] = esc_attr(__('Remove', SEK_TEXTDOMAIN));
                $this->attr_texts['act'] = esc_attr(__('Activate', SEK_TEXTDOMAIN));
                $this->attr_texts['deact'] = esc_attr(__('Deactivate', SEK_TEXTDOMAIN));
                $this->attr_texts['save'] = esc_attr(__('Save', SEK_TEXTDOMAIN));
                
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'admin_init'));
                add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            } else {
                $this->api = new SEK_API();
            }
        }
        
        /**
        * Get options
        */
        public function get_data()
        {
            return $this->data_obj->get_data();
        }
        
        /**
        * Get admin tabs data
        */
        public function admin_tabs()
        {
            $admin_tabs = array(
                'patterns' => array(
                    'title' => __('Patterns', SEK_TEXTDOMAIN),
                    'url' => admin_url('options-general.php?page=sek_options_page'),
                    'tpl' => 'patterns-page.php',
                ),
                'settings' => array(
                    'title' => __('Settings', SEK_TEXTDOMAIN),
                    'url' => admin_url('options-general.php?page=sek_options_page&tab=settings'),
                    'tpl' => 'settings-page.php',
                ),
            );
            return $admin_tabs;
        }
        
        /**
        * Admin menu
        */
        public function admin_menu()
        {
            global $sek_options_page;
            $sek_options_page = add_options_page(__('Options page - SEK Plugin', SEK_TEXTDOMAIN), __('SEK Options', SEK_TEXTDOMAIN), 'manage_options', 'sek_options_page', array($this, 'options_page_cb'));
        }
        
        /**
        * Enqueue admin scripts and styles.
        */
        public function admin_scripts($hook)
        {
            global $sek_options_page;
            if ($sek_options_page == $hook) {
                wp_enqueue_style('sek_admin_css', SEK_SCRIPTURL . 'admin.css');
                wp_enqueue_script('jquery');
                wp_enqueue_script('sek_admin_js', SEK_SCRIPTURL . 'admin.js', array('jquery'));
            }
        }
        
        /**
        * Admin init - registering settings for WP Setting API
        */
        public function admin_init()
        {
            register_setting(
                'sek_options_group',
                SEK_OPTNAME,
                array($this, 'validate_options_cb')
            );
            
            // Settings section
            add_settings_section(
                'sek_general_section',
                '',
                '__return_false',
                'sek_options_page'
            );
            add_settings_section(
                'sek_se_section',
                '',
                '__return_false',
                'sek_options_page'
            );
            add_settings_section(
                'sek_charsreplace_section',
                '',
                '__return_false',
                'sek_options_page'
            );
            add_settings_section(
                'sek_multiplewords_section',
                '',
                '__return_false',
                'sek_options_page'
            );
            add_settings_section(
                'sek_pattern_section',
                '',
                '__return_false',
                'sek_options_page'
            );
            
            // settings fields
            add_settings_field(
                'pattern',
                '<label for="add-pattern">' . __('Add a pattern :', SEK_TEXTDOMAIN) . '</label>',
                array($this, 'pattern_field_cb'),
                'sek_options_page',
                'sek_pattern_section'
            );
            add_settings_field(
                'charsreplace',
                '',
                array($this, 'charsreplace_field_cb'),
                'sek_options_page',
                'sek_charsreplace_section'
            );
            add_settings_field(
                'multiple_words',
                '',
                array($this, 'multiplewords_field_cb'),
                'sek_options_page',
                'sek_multiplewords_section'
            );
            add_settings_field(
                'se',
                '',
                array($this, 'se_field_cb'),
                'sek_options_page',
                'sek_se_section'
            );
        }
        
        /**
        * Options page callback
        */
        public function options_page_cb()
        {
            $tabs = $this->admin_tabs();
            $tab = (isset($_GET['tab']))? $_GET['tab'] : 'patterns';
            if (!array_key_exists($tab, $tabs)) $tab = 'patterns';
            ?>
        <div class="wrap">
            <div id="icon-generic" class="icon32"><br></div>
            <h2><?php _e('Search Engine Keywords settings', SEK_TEXTDOMAIN); ?></h2>
            <h3 class="nav-tab-wrapper">
            <?php foreach ($tabs as $key => $value) : ?>
                <a href="<?php echo esc_url($value['url']) ?>" class="nav-tab<?php if ($tab == $key) echo ' nav-tab-active'; ?>"><?php echo $value['title']; ?></a>
            <?php endforeach; ?>
            </h3>
            <form id="sek-options-form" method="post" action="options.php">
                <input type="hidden" id="secondary-submit-target" name="sek_options[target]" value="" />
                <?php require_once(SEK_TPLDIR . $tabs[$tab]['tpl']); ?>
            </form>
        </div><!-- .wrap -->
            <?php
        }
        
        /**
        * =========================
        * WP Settings API callbacks
        * =========================
        */
        
        
        /**
        * SAPI: Pattern field
        */
        public function pattern_field_cb()
        {
            ?>
            <input type="text" class="long-text code" id="new-pattern" name="sek_options[pattern]" value="<?php echo esc_attr($this->pattern_candidate); ?>" />
            <?php
        }

        /**
        * SAPI: Multiple words field
        */
        public function multiplewords_field_cb()
        {
            $options = $this->data_obj->get_data();
            ?>
            <input type="checkbox" id="multiple-words" name="sek_options[multiple_words]" <?php checked($options['multiple-words']); ?> value="1"/>
            <?php
        }
        
        /**
        * SAPI: SE field
        */
        public function se_field_cb()
        {
            ?>
            <div class="single-setting">
                <label for="se-name"><?php _e('Name: ', SEK_TEXTDOMAIN); ?></label>
                <input id="se-name" type="text" name="sek_options[se_name]" value="" />
                <span class="field-info"><?php _e('i.e. Google', SEK_TEXTDOMAIN); ?></span>
            </div><!-- .single-setting -->
            <div class="single-setting">
                <label for="se-domain"><?php _e('Domain: ', SEK_TEXTDOMAIN); ?></label>
                <input id="se-domain" class="code" type="text" name="sek_options[se_domain]" value="" />
                <span class="field-info"><?php _e('"google" for Google.<br />No tld(.com), no subdomain(www.) ', SEK_TEXTDOMAIN); ?></span>
            </div><!-- .single-setting -->
            <div class="single-setting">
                <label for="se-query"><?php _e('Query variable: ', SEK_TEXTDOMAIN); ?></label>
                <input id="se-query" class="small-text code" type="text" name="sek_options[se_query]" value="" />
                <span class="field-info"><?php _e('"q" for Google, "p" for Yahoo', SEK_TEXTDOMAIN); ?></span><br />
            </div><!-- .single-setting -->
            <?php
        }
        
        /**
        * SAPI: chars replace field
        */
        public function charsreplace_field_cb()
        {
            ?>
            <label for="chars-search"><?php _e('Search: ', SEK_TEXTDOMAIN); ?></label><br />
            <input type="text" id="chars-search" class="code" value="" name="sek_options[chars_search]" /><br />
            <label for="chars-repl"><?php _e('Replacement: ', SEK_TEXTDOMAIN); ?></label><br />
            <input type="text" id="chars-repl" class="code" value="" name="sek_options[chars_repl]" /><br />
            <?php
        }
        
        /**
        * SAPI: settings validation
        */
        public function validate_options_cb($input)
        {
            $data_obj = $this->data_obj;
            $data = $data_obj->get_data();
            $pattern_obj = $this->pattern_obj;
            
            //Add pattern
            if (isset($input['add_pattern'])) {
                $pattern = str_replace(array('   ', '  '), array(' ', ' '), trim($input['pattern']));
                $var_info = $pattern_obj->validate_new_pattern($pattern);
                $msg = __('Pattern added successfully.', SEK_TEXTDOMAIN);
                $type = 'updated';
                $code = 'valid-pattern';
                if ($var_info['valid']) {
                    $new_pattern_obj = $pattern_obj->build_pattern_data($pattern);
                    $equivalent = false;
                    foreach ($data['pattern'] as $key => $value) {
                        if ($pattern_obj->equivalent_patterns($value, $new_pattern_obj)) {
                            $equivalent = $key;
                            break;
                        }
                    }
                    if (false !== $equivalent) {
                        $type = 'error';
                        $code = 'equivalent-pattern';
                        $msg = sprintf(__('There is already an equivalent pattern to "%1$s" in the pattern stack. <em> ("%2$s")</em>',  SEK_TEXTDOMAIN)
                            , $pattern
                            , $data['pattern'][$equivalent]['pattern']
                        );
                    } elseif (false !== strpos($pattern, '}{')) {
                        // Avoid successive variables
                        $type = 'error';
                        $code = 'successive-variable-pattern';
                        $msg = sprintf(__('The pattern <em>"%s"</em> is invalid. Do not use successive variables. You must space them with at least one character.',  SEK_TEXTDOMAIN)
                            , $pattern
                        );
                    } else {
                        $valid = true;
                        $current_index;
                        $chunks_count = count($new_pattern_obj['chunks']);
                        $v_index = 0;
                        foreach ($new_pattern_obj['chunks'] as $key => $value) {
                            if (false === strpos($value, '{')) {
                                continue;
                            }
                            if (-1 == $new_pattern_obj['vars'][$v_index]['word-count']) {
                            
                                if (!(0 == $key || $chunks_count - 1 == $key)) {
                                    if (
                                            false !== strpos($new_pattern_obj['chunks'][$key-1],'{')    ||
                                            false !== strpos($new_pattern_obj['chunks'][$key+1], '{')   || 
                                            '' == trim($new_pattern_obj['chunks'][$key-1])              ||
                                            '' == trim($new_pattern_obj['chunks'][$key+1])
                                    ) {
                                        $valid = false;
                                        break;
                                    }
                                }
                            }
                            $v_index++;
                        }
                        if ($valid) {
                            $data_obj->add_pattern($new_pattern_obj);
                        } else {
                            $type = 'error';
                            $code = 'full-length-capture';
                            $msg = __('When using a full length variable, it must be either at the beginning or at the end of the pattern, or surrounded by two non-blank text element.', SEK_TEXTDOMAIN);
                        }
                    }
                } else {
                    if (isset($var_info['invalid_varname'])) {
                        $type = 'error';
                        $code = 'invalid-variable-name';
                        $msg = sprintf(__('There is one or more invalid variable name in the pattern "%1$s": <em>(%2$s)</em>', SEK_TEXTDOMAIN),
                            $pattern,
                            implode(', ', $var_info['invalid_varname'])
                        );
                    } elseif (isset($var_info['varname_collision'])) {
                        $type = 'error';
                        $code = 'varname-collision';
                        $msg = sprintf(__('You can not use a variable name more than once within a pattern. Pattern: <em>"%s"</em>', SEK_TEXTDOMAIN),
                            $pattern
                        );
                    } elseif (isset($var_info['word-count'])) {
                        $type = 'error';
                        $code = 'word-count';
                        $msg = sprintf(__('You require to much words in the variable "%1$s", while you are intending to capture only %2$s word. Pattern: <em>"%3$s"</em>', SEK_TEXTDOMAIN),
                            $var_info['word-count']['var'],
                            $var_info['word-count']['word-count'],
                            $pattern
                        );
                    } elseif (isset($var_info['max-letter-count'])) {
                        $type = 'error';
                        $code = 'max-letter-count';
                        $msg = sprintf(__('There is a required word in the variable "%1$s" that is longer than the maximum allowed word&#39;s length. Pattern: <em>"%2$s"</em>', SEK_TEXTDOMAIN),
                            $var_info['max-letter-count']['var'],
                            $pattern
                        );
                    } else {
                        $type = 'error';
                        $code = 'invalid-pattern';
                        $msg = sprintf(__('The pattern <em>"%s"</em> is invalid.', SEK_TEXTDOMAIN), $pattern);
                    }
                }
                if ('updated' != $type) $_SESSION['SEKeywords']['pattern_candidate'] = $pattern;
                add_settings_error(
                    'pattern',
                    $code,
                    $msg,
                    $type
                );
            }
            
            // Add search engine
            if (isset($input['add_se'])) {
                $msg = __('Search Engine added successfully.', SEK_TEXTDOMAIN);
                $type = 'updated';
                $code = 'valid-se';
                
                $domain = strtolower(trim($input['se_domain']));
                $name = trim($input['se_name']);
                $query = trim($input['se_query']);
                if (!isset($data['SE'][$domain])) {
                    if ('' == $domain || false !== strpos($domain, '.')) {
                        $msg = __('Please enter a valid domain for the new Search Engine (without tld nor subdomain).', SEK_TEXTDOMAIN);
                        $type = 'error';
                        $code = 'invalid-se-domain';
                    } elseif ('' == $query) {
                        $msg = __('Query variable field of the new search engine is missing.', SEK_TEXTDOMAIN);
                        $type = 'error';
                        $code = 'missing-se-query';
                    } else {
                        $data_obj->add_se($domain, $name, $query);
                    }
                } else {
                    $msg = __('This Search engine is already registred.', SEK_TEXTDOMAIN);
                    $type = 'error';
                    $code = 'existing-se-domain';
                }
                add_settings_error(
                    'se',
                    $code,
                    $msg,
                    $type
                );
            }
            
            // Add characters replacement
            if (isset($input['add_repl'])) {
                $data_obj->update_repl($input['chars_search'], $input['chars_repl']);
            }
            
            // Multiple words
            if (isset($input['save_multiplewords'])) {
                $check = (isset($input['multiple_words'])) ? true : false;
                $data_obj->update_multiple_words($check);
            }
            
            // Deactivate pattern
            if (isset($input['deactivate_pattern'])) {
                $id = intval($input['target']);
                if (isset($data['pattern'][$id])) {
                    $data_obj->pattern_status($id, false);
                }
            }
            
            // Activate pattern
            if (isset($input['activate_pattern'])) {
                $id = intval($input['target']);
                if (isset($data['pattern'][$id])) {
                    $data_obj->pattern_status($id, true);
                }
            }
            
            // Delete pattern
            if (isset($input['delete_pattern'])) {
                $id = intval($input['target']);
                if (isset($data['pattern'][$id])) {
                    $data_obj->delete_pattern($id);
                }
            }
            
            // Deactivate SE
            if (isset($input['deactivate_se'])) {
                $id = trim($input['target']);
                $data_obj->se_status($id, false);
            }
            
            // Activate SE
            if (isset($input['activate_se'])) {
                $id = trim($input['target']);
                $data_obj->se_status($id, true);
            }
            
            // Delete SE
            if (isset($input['delete_se'])) {
                $data_obj->delete_se(trim($input['target']));
            }
            
            // Delete chras replacement
            if (isset($input['delete_repl'])) {
                $target = intval($input['target']);
                if (isset($data['chars-filter'][$target])) {
                    $data_obj->delete_repl($target);
                }
            }
            
            return $data_obj->get_data();
        }
        
        /**
        * Helper function
        */
        public function status($subject, $true, $false)
        {
            if ($subject) {
                return '<p class="active">'. $true . '</p>';
            } else {
                return '<p class="inactive">'. $false . '</p>';
            }
        }
        
        /**
        * Get the api
        */
        public function get_api()
        {
            return $this->api;
        }
    }
}
