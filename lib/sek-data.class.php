<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('SEK_DATA')) {
    class SEK_DATA
    {
        /**
        * Default data
        *
        * @since 1.0.0
        */
        private $default_data = array(
            'SE' => array(
                'google' => array(
                    'name' => 'Google',
                    'query' => 'q',
                    'status' => false,
                ),
                'yahoo' => array(
                    'name' => 'Yahoo',
                    'query' => 'p',
                    'status' => false,
                ),
                'bing' => array(
                    'name' => 'Bing',
                    'query' => 'q',
                    'status' => false,
                ),
                'ask' => array(
                    'name' => 'Ask',
                    'query' => 'q',
                    'status' => false,
                ),
            ),
            'pattern' => array(
                0 => array(
                    'pattern' => 'make word from {main_var}',
                    'vars' => array(
                        array(
                            'name' => 'main_var',
                            'word-count' => 1,
                            'max-letter-count' => -1,
                            'w' => false,
                            'wo' => false,
                        ),
                    ),
                    'vars_indexes' => array(1),
                    'text_indexes' => array(0),
                    'chunks' => array('make word from ', '{main_var}'),
                    'status' => false,
                ),
            ),
            'chars-filter' => array(
                0 => array('search' => ',', 'repl' => ' '),
            ),
            'multiple-words' => false,
            'version' => SEK_VERSION,
        );
        
        
        /**
        * The data
        */
        private $data = null;
        
        /**
        * Check data structure
        *
        * @since 1.0.0
        */
        private function check_data()
        {
            $options = get_option(SEK_OPTNAME);
            if (!isset($options['version'])) {
                $this->data = $this->default_data;
                $this->save_db();
            } else {
                if (SEK_VERSION != $options['version']) {
                    $this->update_data();
                } else {
                    $this->data = $options;
                }
            }
        }
        
        /**
        * Update data structure
        *
        * @since 1.0.0
        */
        private function update_data()
        {
            // UPDATE HERE!
            $this->load_db();
            if (!isset($this->data['chars-filter'])) {
                
                // version 1.0.0
                $this->data['chars-filter'] = $this->default_data['chars-filter'];
                $this->data['multiple-words'] = $this->default_data['multiple-words'];
                if (array() != $this->data['pattern']) {
                    $new_patterns = array();
                    foreach ($this->data['pattern'] as $key => $old_pattern) {
                        $new_vars = array();
                        foreach ($old_pattern['vars'] as $var) {
                            $new_vars[] = array(
                                'name' => $var,
                                'word-count' => 1,
                                'max-letter-count' => -1,
                                'w' => false,
                                'wo' => false,
                            );
                        }
                        $new_patterns[$key] = $old_pattern;
                        $new_patterns[$key]['vars'] = $new_vars;
                    }
                    $this->data['pattern'] = $new_patterns;
                }
            }
            $this->data['version'] = SEK_VERSION;
            $this->save_db();
        }
        
        /**
        * The constructor
        */
        public function __construct()
        {
            $this->check_data();
        }
        
        /**
        * Save data 
        */
        public function save_db()
        {
            update_option(SEK_OPTNAME, $this->data);
        }
        
        /**
        * Load data
        */
        public function load_db()
        {
            $this->data = get_option(SEK_OPTNAME);
        }
        
        /**
        * Get options
        */
        public function get_data()
        {
            return $this->data;
        }
        
        /**
        * Add a pattern
        * 
        * @param array $pattern, pattern data
        */
        public function add_pattern($pattern)
        {
            $pattern['status'] = true;
            $this->data['pattern'][] = $pattern;
            $this->save_db(); 
            /**
            ** Writing directly in wp_options table is not allowed while using the WP SettingAPI.
            ** When using the WP SAPI, the new data is the value returned by the validation function.
            ** $this->save_db() here is just for an eventual future use outside the context of WP SAPI.
            */
        }
        
        /**
        * Add a new Search engine
        *
        * @param string $domain
        * @param string $name
        * @param string $query
        */
        public function add_se($domain, $name, $query)
        {
            $this->data['SE'][$domain] = array(
                'name' => $name,
                'query' => $query,
                'status' => false,
            );
            $this->save_db(); // idem as for add_pattern()
        }
        
        /**
        * Add or edit char replacement
        * 
        * @param string $search, the search string
        * @param string $repl, the replacement string
        */
        public function update_repl($search, $repl)
        {
            $search_list = array();
            foreach ($this->data['chars-filter'] as $key => $value) {
                $search_list[$key] = $value['search']; 
            }
            $index = array_search($search, $search_list);
            if (false !== $index) {
                $this->data['chars-filter'][$index] = array('search' => $search, 'repl' => $repl);
            } else {
                $this->data['chars-filter'][] = array('search' => $search, 'repl' => $repl);
            }
            $this->save_db(); // Useless with WP SAPI
        }
        
        /**
        * Update multiple words settings
        *
        * @param bool $value, the new value for multiple words setting
        */
        public function update_multiple_words($value)
        {
            $this->data['multiple-words'] = $value;
            $this->save_db(); // Useless with WP SAPI
        }
        
        /**
        * Return the id of a given pattern
        */
        public function get_pattern_id($pattern)
        {
            if (!is_string($pattern)) {
                return false;
            }
            foreach ($this->data['pattern'] as $id => $value) {
                if ($pattern == $value['pattern']) {
                    return $id;
                }
                return false;
            }
        }
        
        /**
        * Pattern status Getter/Setter
        *
        * @param string $id, the pattern id.
        * @param bool $status, the new value if setter.
        */
        public function pattern_status($id, $status = null)
        {
            if (null === $status) {
                return $this->data['pattern'][$id]['status'];
            } else {
                if (!is_bool($status)) throw new Exception('Pattern status must be a boolean');
                $this->data['pattern'][$id]['status'] = $status;
                $this->save_db();  // idem as for add_pattern() and add_se()
            }
        }
        
        /**
        * Remove a pattern
        */
        public function delete_pattern($id)
        {
            unset($this->data['pattern'][$id]);
            $this->save_db(); // Useless with WP SAPI
        }
        
        /**
        * Search Engine status Getter/Setter
        *
        * @param string $id, the SE id.
        * @param bool $status, the new value if setter.
        */
        public function se_status($id, $status = null)
        {
            if (null === $status) {
                return $this->data['SE'][$id]['status'];
            } else {
                if (!is_bool($status)) throw new Exception('Search Engine status must be a boolean');
                $this->data['SE'][$id]['status'] = $status;
                $this->save_db();  // Useless with WP SAPI
            }
        }
        
        /**
        * Remove a SE
        */
        public function delete_se($id)
        {
            unset($this->data['SE'][$id]);
            $this->save_db(); // Useless with WP SAPI
        }
        
        /**
        * Remove character replacement
        */
        public function delete_repl($id)
        {
            unset($this->data['chars-filter'][$id]);
            $this->save_db(); // Useless with WP SAPI
        }
    }
}
