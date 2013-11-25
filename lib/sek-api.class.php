<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('SEK_API')) {
    class SEK_API
    {
        // Plugin's options
        private $options = null;
        
        // List of active Search engines
        private $se = array();
        
        // List of active patterns
        private $pattern = array();
        
        // Referer
        private $referer = '';
        
        // Constructor
        public function __construct()
        {
            $options = get_option(SEK_OPTNAME);
            if (isset($options['version'])) {
                $this->options = $options;
                foreach ($options['SE'] as $key => $value) {
                    if ($value['status']) {
                        $this->se[$key] = $value;
                    }
                }
                foreach ($options['pattern'] as $key => $value) {
                    if ($value['status']) {
                        $this->pattern[$key] = $value;
                    }
                }
            }
            if (isset($_SERVER['HTTP_REFERER'])) {
                $this->referer = $_SERVER['HTTP_REFERER'];
            }
        }
        
        
        /**
        * Capture words (delimited by blank space) before a given string
        *
        * @param $text, the string in which we search
        * @param $cue, the cue string
        * @param $word_count, number of words to capture
        * @param $length, maximum allowed word length
        * @param $args, 'w' and 'wo' fields from pattern data object (result of SEK_PATTERN::build_pattern_data($pattern))
        */
        private function getw_before($text, $cue, $word_count = 1, $length = -1, $args = null)
        {
            $result = $this->capture('left', $text, $cue, $word_count, $length, $args);
            return $result;
        }
        
        /**
        * Capture words (delimited by blank space) after a given string
        *
        * @param $text, the string in which we search
        * @param $cue, the cue string
        * @param $word_count, number of words to capture
        * @param $length, maximum allowed word length
        * @param $args, 'w' and 'wo' fields from pattern data object (result of SEK_PATTERN::build_pattern_data($pattern))
        */
        private function getw_after($text, $cue, $word_count = 1, $length = -1, $args = null)
        {
            $result = $this->capture('right', $text, $cue, $word_count, $length, $args);
            return $result;
        }
        
        /**
        * the Capture function used by getw_after and getw_before
        */
        private function capture($dir, $text, $cue, $word_count = 1, $length = -1, $args = null)
        {
            $result = false;
            $text = strtolower($text);
            $sub_string = '';
            if ('left' == $dir) {
                $sub_string = strstr($text, $cue, true);
            } else {
                $sub_string = substr(strstr($text, $cue), strlen($cue));
            }
            $sub_string = trim($sub_string);
            if (!empty($sub_string)) {
                $words = ('left' == $dir) ? array_reverse(explode(' ', trim($sub_string))) : explode(' ', trim($sub_string));
                
                if ($word_count <= count($words)) {
                    $word_count = (1 > $word_count) ? count($words) : $word_count;
                    $result = array_slice($words, 0, $word_count);
                }
                
                $longest = 0;
                foreach ($words as $word) {
                    $current = strlen($word);
                    $longest = ($current > $longest) ? $current : $longest;
                }
                if (false != $result && 0 < $length && $length < $longest) {
                    $result = false;
                }
                if (false != $result && isset($args['w']) && is_array($args['w'])) {
                    foreach ($args['w'] as $value) {
                        if (!in_array($value, $result)) {
                            $result = false;
                            break;
                        }
                    }
                }
                if (false != $result &&  isset($args['wo']) && is_array($args['wo'])) {
                    foreach ($args['wo'] as $value) {
                        if (in_array($value, $result)) {
                            $result = false;
                            break;
                        }
                    }
                }
            }
            if ($result) {
                $result = ('left' == $dir) ? array_reverse($result) : $result;
                if (!$this->options['multiple-words']) {
                    $result = implode(' ', $result);
                }
            }
            return $result;
        }
        
        /**
        * Explode a text element with logic expression.
        */
        private function explode_text_elem($elem)
        {
            $blocs = array();
            $subject = $elem;
            while (false !== strpos($subject, '(')) {
                $exp = explode('(', $subject, 2);
                if (0 != strlen($exp[0])) {
                    $blocs[] = $exp[0];
                }
                $exp2 = explode(')', $exp[1], 2);
                $subject = $exp2[1];
                $alt = explode('|', $exp2[0]);
                if (1 < count($alt)) {
                    $lensort = function($a, $b){
                        return (0 < strcmp($a, $b))? -1 : 1;
                    };
                    
                    // This prevent confusion when alternatives starting with same chars are present - such as (with these|with the)
                    usort($alt, $lensort);
                    $blocs[] = $alt;
                }
            }
            if (0 != strlen($subject)) {
                $blocs[] = trim($subject, ')');
            }
            return $blocs;
        }
        
        /**
        * Search a text element (with or w/o logic expression) within a subject text.
        *
        * @param string $subject, the subject of the search
        * @param string $elem, the element to search for
        */
        private function search_TI($subject, $elem) {
            $result = false;
            if (false === strpos($elem, '(')) {
                return (false !== strpos($subject, $elem))? $elem : false;
            } else {
                $blocs = $this->explode_text_elem($elem);
                $winner = '';
                $first = true;
                foreach ($blocs as $bloc) {
                    if (is_array($bloc)) {
                        $alt_found = false;
                        foreach ($bloc as $alt) {
                            if ($alt_found) {
                                // Next bloc
                                break;
                            }
                            if (empty($alt)) {
                                // Next bloc;
                                $alt_found = true;
                                continue;
                            }
                            $pos = strpos($subject, $alt);
                            if (0 === $pos || (($first) && false !== $pos)) {
                                $winner .= $alt;
                                $exp = explode($alt, $subject, 2);
                                $subject = $exp[1];
                                $alt_found = true;
                            }
                        }
                        if (!$alt_found) {
                            return false;
                        }
                    } else {
                        $pos = strpos($subject, $bloc);
                        if (0 === $pos || (($first) && false !== $pos)) {
                            $winner .= $bloc;
                            $exp = explode($bloc, $subject, 2);
                            $subject = $exp[1];
                            // Next bloc
                        } else {
                            return false;
                        }
                    }
                    $first = false;
                }
                $result = $winner;
            }
            return $result;
        }
                
        /**
        * Provide keywords variable
        */
        public function matches()
        {
            $result = false;
            $domain = null;
            $query_string = null;
            if (!empty($this->referer)) {
                $url = parse_url($this->referer);
                $host = explode('.', $url['host']);
                $domain = $host[count($host) - 2];
                $query_string = (isset($url['query'])) ? $url['query'] : '';
            }
            if (!empty($domain) && isset($this->se[$domain]) && '' != $query_string) {// It's one of the active SE
                parse_str($query_string, $query);
                $q = (isset($query[$this->se[$domain]['query']])) ? str_replace(array('   ', '  ', ' '), array(' ', ' ', ' '), $query[$this->se[$domain]['query']]) : null ;

                if (!empty($q)) {// There is a query
                    $tmp_result = array();
                    $sch = array();
                    $repl = array();
                    // Chars replacement
                    foreach ($this->options['chars-filter'] as $r) {
                        $sch[] = $r['search'];
                        $repl[] = $r['repl'];
                    }
                    
                    if (!empty($sch)) {
                        $q = str_replace($sch, $repl, $q);
                    }
                    foreach ($this->pattern as $pattern) {
                        $subject = strtolower($q);
                        
                        $TI_found = $this->search_TI($subject, $pattern['chunks'][$pattern['text_indexes'][0]]);
                        
                        if (false !== $TI_found) {// Found the first text element
                            $tmp_match = array();
                            $VI = 0;
                            $all_elems_found = true;
                            $TE_count = count($pattern['text_indexes']);
                            
                            $look_back = false;
                            
                            $empty_fistTI = true;
                            $firtTI_offset = 0;
                            
                            foreach ($pattern['text_indexes'] as $key => $TI) {
                                // Text element iteration
                                if (false != $empty_fistTI && ' ' == $pattern['chunks'][$TI]) {
                                    $firtTI_offset++;
                                    continue;
                                }
                                $empty_fistTI = false;
                                $cue = $this->search_TI($subject, $pattern['chunks'][$TI]);
                                
                                if (false !== $cue) {
                                    
                                    if (0 == $key - $firtTI_offset && 0 != $TI) {
                                        // There is one or more variable before the first ($key ==0) text element
                                        
                                        $opening_VI = $TI - 1 - $firtTI_offset;
                                        $opening_cue = $cue;
                                        $opening_subject = strstr($subject, $cue, true) . $cue;
                                        while (0 <= $opening_VI) {
                                            $wc = $pattern['vars'][$opening_VI]['word-count'];
                                            $mlc = $pattern['vars'][$opening_VI]['max-letter-count'];
                                            $w = $pattern['vars'][$opening_VI]['w'];
                                            $wo = $pattern['vars'][$opening_VI]['wo'];
                                            $capture = $this->getw_before($opening_subject, $opening_cue, $wc, $mlc, array('w' => $w, 'wo' => $wo));
                                            if (false == $capture) {
                                                $all_elems_found = false;
                                                break 2;
                                            }
                                            $tmp_match[$pattern['vars'][$opening_VI]['name']]['value'] = $capture;
                                            $tmp_match[$pattern['vars'][$opening_VI]['name']]['pattern'] = $pattern['pattern'];
                                            $s_capture = (is_array($capture)) ? implode(' ', $capture) : $capture;
                                            $opening_cue = ' ' . $s_capture . $opening_cue;
                                            $opening_VI--;
                                            $VI++;
                                        }
                                        $subject = strstr($subject, $cue);
                                    }
                                    
                                    if ($look_back) {
                                        // There is a full length capture before the current text element.
                                        $wc = $pattern['vars'][$VI - 1]['word-count'];
                                        $mlc = $pattern['vars'][$VI - 1]['max-letter-count'];
                                        $w = $pattern['vars'][$VI - 1]['w'];
                                        $wo = $pattern['vars'][$VI - 1]['wo'];
                                        
                                        $capture = $this->getw_before($subject, $cue, $wc, $mlc, array('w' => $w, 'wo' => $wo));
                                        if (false == $capture) {
                                            $all_elems_found = false;
                                            break;
                                        }
                                        $tmp_match[$pattern['vars'][$VI - 1]['name']]['value'] = $capture;
                                        $tmp_match[$pattern['vars'][$VI - 1]['name']]['pattern'] = $pattern['pattern'];
                                        $s_capture = (is_array($capture)) ? implode(' ', $capture) : $capture;
                                        
                                        $subject = substr(strstr($subject, $s_capture), strlen($s_capture));
                                        $look_back = false;
                                    }
                                    $i = 1;
                                    while (isset($pattern['chunks'][$TI + $i])) {
                                        if (false !== strpos($pattern['chunks'][$TI + $i], '{')) {
                                            // The next chunk contains a variable.
                                            
                                            $wc = $pattern['vars'][$VI]['word-count'];
                                            $mlc = $pattern['vars'][$VI]['max-letter-count'];
                                            $w = $pattern['vars'][$VI]['w'];
                                            $wo = $pattern['vars'][$VI]['wo'];
                                            
                                            if (0 > $wc) {
                                                // The next variable is a full length var
                                                
                                                if ($TE_count - 1 == $key) {
                                                    // now in the last text element, so capture the last var (a full-length var)
                                                    $capture = $this->getw_after($subject, $cue, $wc, $mlc, array('w' => $w, 'wo' => $wo));
                                                    if (false == $capture) {
                                                        $all_elems_found = false;
                                                        break 2;
                                                    }
                                                    $tmp_match[$pattern['vars'][$VI]['name']]['value'] = $capture;
                                                    $tmp_match[$pattern['vars'][$VI]['name']]['pattern'] = $pattern['pattern'];
                                                    $VI++;
                                                    $i++;
                                                    // End of parsing for the current pattern.
                                                } else {
                                                    /*
                                                    * Else capture the full length var on the next step
                                                    * The next step corresponds to the next TI
                                                    */
                                                    $subject = substr($subject, strlen($cue));
                                                    $VI++;
                                                    $i++;
                                                    $look_back = true;
                                                    continue 2;
                                                }
                                            } else {
                                                // The next variable is a finite length var
                                                $capture = $this->getw_after($subject, $cue, $wc, $mlc, array('w' => $w, 'wo' => $wo));
                                                if (false == $capture) {
                                                    $all_elems_found = false;
                                                    break 2;
                                                }
                                                $tmp_match[$pattern['vars'][$VI]['name']]['value'] = $capture;
                                                $tmp_match[$pattern['vars'][$VI]['name']]['pattern'] = $pattern['pattern'];
                                                $s_capture = (is_array($capture)) ? implode(' ', $capture) : $capture;
                                                
                                                $drop = $cue . $s_capture;
                                                $subject = substr(strstr($subject, $drop), strlen($drop));
                                                $i++;
                                                $VI++;
                                            }
                                        } else {
                                            // the last chunk is a text element
                                            break;
                                        }
                                    } // end while (isset($pattern['chunks'][$TI + $i]));
                                } else {
                                    $all_elems_found = false;
                                    break;
                                }
                            }// End foreach TI
                            
                            if ($all_elems_found) {
                                $tmp_result = $tmp_match;
                            }
                        }
                    }// End foreach pattern
                    if (array() != $tmp_result) {
                        // an array of the variables matched.
                        $result = $tmp_result;
                        
                        // + the additional field containing the query
                        $result['__QUERY'] = $q;
                        
                        // + the Search engine
                        $result['__SEARCH_ENGINE'] = $domain;
                        return $result;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
            return $result;
        }
    }
}
