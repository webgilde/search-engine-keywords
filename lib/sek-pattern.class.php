<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('SEK_PATTERN')) {
    class SEK_PATTERN{
        // The data object
        private $data_obj = null;
        
        // Regex patterns
        
        // Captures only variables names (even an invalid variable name)
        private $novars_regex = "#[^{}]*\{([^{}\s]+)(?:\([0-9]*\))?(?:\[[0-9]*\])?:?(?:[^}]*)\}#";
        
        /**
        * Captures :
        * 0- The Whole pattern
        * 1- Variables names
        * 2- Word Count (if present)
        * 3- Max letter Count (if present)
        * 4- The logic expression (if present)
        */
        private $vars_regex = "#[^{}]*\{(([a-zA-z][0-9a-zA-Z_]*)(\([0-9]*\))?(\[[0-9]*\])?:?([^}]*))\}#";
        
        /**
        * Constructor
        */
        public function __construct(&$data)
        {
            $this->data_obj = $data;
        }
        
        /**
        * Check whether a new pattern string is valid or not
        *
        * @since 1.0.0
        * @param string $pattern, the pattern string
        * @return array $result, array with basic new pattern data
        */
        public function validate_new_pattern($pattern)
        {
            $data = $this->data_obj->get_data();
            $result = array();
            $valid = true;
            $valid_varname_count = 0;
            $valid_varname = array();
            $vars = array();
            $open = substr_count($pattern, '{');
            $close = substr_count($pattern, '}');
            if (0 == $open || $open != $close) {
            
                // variable delimiter not closed or not opened correctly
                $valid = false;
            }
            if ($valid && 1 == $open) {
            
                // avoid pattern with one variable and no text, nonsense - meaning capturing the whole query
                if (0 == strpos($pattern, '{') && (strlen($pattern) - 1) == strpos($pattern, '}')) {
                    $valid = false;
                }
            }
            
            if ($valid && 0 != preg_match_all($this->novars_regex, $pattern, $variable)) {
            
                // capture submited variable, even with invalid name in order to display an admin notice.
                $result['submited_var'] = $variable[1];
            } else {
                $valid = false;
            }
            if ($valid) {
                if ($valid_varname_count = preg_match_all($this->vars_regex, $pattern, $match)) {
                    $valid_varname = $match[2];
                    $vars = $this->explode_vars($match);
                    if (false != $vars) {
                        $var_names = array();
                        foreach ($vars as $variable) {
                            $var_names[] = $variable['name'];
                            $w_count = (false != $variable['w']) ? count($variable['w']) : 0;
                            $longest_wcnt = 0;
                            $longest_w = 0;
                            if (false != $variable['w']) {
                                foreach ($variable['w'] as $value) {
                                    $a = strlen($value);
                                    if ($longest_wcnt < $a) {
                                        $longest_wcnt = $a;
                                        $longest_w = $value;
                                    }
                                }
                            }
                            if ($w_count > $variable['word-count'] && 0 < $variable['word-count']) {
                                $valid = false;
                                $result['word-count'] = array(
                                    'var' => $variable['name'],
                                    'word-count' => $variable['word-count'],
                                );
                                break;
                            }
                            if ($longest_wcnt > $variable['max-letter-count'] && 0 < $variable['max-letter-count']) {
                                $valid = false;
                                $result['max-letter-count'] = array(
                                    'var' => $variable['name'],
                                );
                                break;
                            }
                        }
                        if ($valid) {
                            $var_count = array_count_values($var_names);
                            if (count($var_count) != count($var_names)) {
                                $valid = false;
                                $result['varname_collision'] = true;
                            }
                        }
                    } else {
                        $valid = false;
                    }
                }
                if ($open != count($result['submited_var'])) {
                
                    // Avoid nested curly braces
                    $valid = false;
                }
                if ($valid && count($result['submited_var']) != $valid_varname_count) {
                
                    // Some variables names are invalid
                    $result['invalid_varname'] = array_diff($result['submited_var'], $valid_varname);
                    $valid = false;
                } else {
                    $result['valid_varname'] = $valid_varname;
                }
            }
            $result['valid'] = $valid;
            return $result;
        }
        
        /**
        * Explode vars string into an array
        *
        * @since 1.1
        * @param string $match, the result of a preg_match_all (with the $flags param set to PREG_PATTERN_ORDER, the default) through $this->vars_regex on a pattern string (even with invalid variables names).
        * @return array $vars | false when used with an invalid pattern
        */
        public function explode_vars($match = null)
        {
            $vars = false;
            if (!empty($match)) {
                foreach ($match[2] as $key => $value) {
                    $vars[$key]['name'] = $value;
                    
                    // Word count
                    $vars[$key]['word-count'] = (!empty($match[3][$key])) ? trim($match[3][$key], ')(') : false;
                    if ('' === $vars[$key]['word-count']) {
                        // No word count limit
                        $vars[$key]['word-count'] = -1;
                    }
                    
                    // Default value : 1
                    if (false === $vars[$key]['word-count']) {
                        $vars[$key]['word-count'] = 1;
                    }
                    
                    if (is_string($vars[$key]['word-count'])) {
                        $vars[$key]['word-count'] = intval($vars[$key]['word-count']);
                    }
                    
                    if (0 === $vars[$key]['word-count']) {
                        $vars[$key]['word-count'] = -1;
                    }
                    
                    // Max-letter-count
                    $vars[$key]['max-letter-count'] = (!empty($match[4][$key])) ? trim($match[4][$key], '][') : false;
                    
                    //Default value : -1. no letter-count limit.
                    if (empty($vars[$key]['max-letter-count'])) {
                        // will match false, 0 and ''.
                        $vars[$key]['max-letter-count'] = -1;
                    }
                    
                    if (is_string($vars[$key]['max-letter-count'])) {
                        $vars[$key]['max-letter-count'] = intval($vars[$key]['max-letter-count']);
                    }
                    
                    // w & w/o
                    $logic = (!empty($match[5][$key])) ? $match[5][$key] : false;
                    if (false !== $logic) {
                        $words = explode(' ', $logic);
                        foreach ($words as $word) {
                            if ('' != $word) {
                                if (0 === strpos($word, '!')) {
                                    if ('!' == $word) {
                                        $vars[$key]['w'][] = '!';
                                    } else {
                                        // the strtolower is due to case-insensitive parsing
                                        $vars[$key]['wo'][] = strtolower(substr($word, 1));
                                    }
                                } else {
                                    $vars[$key]['w'][] = strtolower($word);
                                }
                            }
                        }
                    } else {
                        $vars[$key]['w'] = false;
                        $vars[$key]['wo'] = false;
                    }
                    if (!isset($vars[$key]['w'])) {
                        $vars[$key]['w'] = false;
                    }
                    if (!isset($vars[$key]['wo'])) {
                        $vars[$key]['wo'] = false;
                    }
                }
            }
            return $vars;
        }
        
        /**
        * build single pattern data. Ensure that the pattern argement is a valid (passed through the validate_new_pattern function)
        * 
        * @since 1.0.0
        * @param string $pattern, a valid pattern string
        * @return array $result
        */
        public function build_pattern_data($pattern)
        {
            $index = 0;
            $var_count = preg_match_all($this->vars_regex, $pattern, $match);
            
            if (1 > $var_count) throw new Exception('invalid pattern');
            
            $var_expr = $match[1];
            
            $vars = $this->explode_vars($match);
            
            $result = array(
                'pattern' => $pattern,
                'vars' => $vars,
                'var_indexes' => array(),
                'text_indexes' => array(),
                'chunks' => array(),
            );
            
            foreach ($var_expr as $key => $var) {
                $exploded = explode('{' . $var . '}', $pattern);
                
                if (!empty($exploded[0])) {
                    $result['chunks'][] = $exploded[0];
                    $result['text_indexes'][] = $index;
                    $index++;
                }
                
                $result['chunks'][] = '{' . $var . '}';
                $result['var_indexes'][] = $index;
                $index++;
                
                if (false !== strpos($exploded[1], '{')) {
                    $pattern = $exploded[1];
                } else {
                    if (!empty($exploded[1])) {
                        $result['chunks'][] = $exploded[1];
                        $result['text_indexes'][] = $index;
                    }
                }
            }
            return $result;
        }
        
        /**
        * Check if two pattern are equivalents
        *
        * @since 1.0.0
        * @param array $pat1, pattern data
        * @param array $pat2, pattern data
        * @return bool
        */
        public function equivalent_patterns($pat1, $pat2)
        {
            $eq = true;
            if (count($pat1['vars']) == count($pat2['vars']) && count($pat1['chunks']) == count($pat2['chunks'])) {
                for ($i = 0; $i < count($pat1['chunks']); $i++) {
                    if (false !== strpos($pat1['chunks'][$i], '{') && false !== strpos($pat2['chunks'][$i], '{')) {
                        continue;
                    }
                    if (0 != strcasecmp($pat1['chunks'][$i], $pat2['chunks'][$i])) {
                        $eq = false;
                        break;
                    }
                }
            } else {
                $eq = false;
            }
            if ($eq) {
            
                $equiv_wlist = function($list1, $list2) {
                    $short = $list1;
                    $long = $list2;
                    $equiv_list = true;
                    if (count($list1) > count($list2)) {
                        $short = $list2;
                        $long = $list1;
                    }
                    foreach ($long as $w) {
                        if (!in_array($w, $short)) {
                            $equiv_list = false;
                            break;
                        }
                    }
                    return $equiv_list;
                };
                
                foreach ($pat1['vars'] as $key => $value) {
                    if ($value['word-count'] != $pat2['vars'][$key]['word-count']) {
                        $eq = false;
                        break;
                    }
                    if ($value['max-letter-count'] != $pat2['vars'][$key]['max-letter-count']) {
                        $eq = false;
                        break;
                    }
                    if (false != $value['w'] xor false != $pat2['vars'][$key]['w']) {
                        $eq = false;
                        break;
                    } else {
                        if (false != $value['w'] && false != $pat2['vars'][$key]['w']) {
                            if (count($value['w']) != count($pat2['vars'][$key]['w'])) {
                                $eq = false;
                                break;
                            } else {
                                $equiv = $equiv_wlist($value['w'], $pat2['vars'][$key]['w']);
                                if (!$equiv) {
                                    $eq = false;
                                    break;
                                }
                            }
                        }
                    }
                    if (false != $value['wo'] xor false != $pat2['vars'][$key]['wo']) {
                        $eq = false;
                        break;
                    } else {
                        if (false != $value['wo'] && false != $pat2['vars'][$key]['wo']) {
                            if (count($value['wo']) != count($pat2['vars'][$key]['wo'])) {
                                $eq = false;
                                break;
                            } else {
                                $equiv = $equiv_wlist($value['wo'], $pat2['vars'][$key]['wo']);
                                if (!$equiv) {
                                    $eq = false;
                                    break;
                                }
                            }
                        }
                    }
                }
                
            }
            return $eq;
        }
    }
}
