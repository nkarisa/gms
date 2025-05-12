<?php 

if(!function_exists('process_php_files')){
    function process_php_files($directory = APPPATH) {
        helper('inflector');
        $lang = array();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST);
    
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                process_file_content($content, $lang);
            }
        }
    
        // Write the collected phrases to eng.php
        $output_file = APPPATH.'Language/en/Global/App.php';
        $php_content = "<?php\n\n\$lang = array(\n";
        foreach ($lang as $key => $value) {
            $php_content .= "    '" . str_replace("'", "\\'", $key) . "' => '" . humanize(str_replace("'", "\\'", $value)) . "',\n";
        }
        $php_content .= ");\n\nreturn \$lang;"; // Add the return statement here
    
        if (file_put_contents($output_file, $php_content) === false) {
            echo "Error writing to $output_file\n";
        } else {
            echo "Successfully wrote to $output_file\n";
        }
    }
}

if(!function_exists('process_file_content')){
    function process_file_content($content, &$lang) {
        // Use preg_match_all to find all occurrences of get_phrase
        $pattern = '/get_phrase\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]+)[\'"]\s*)?\)/';
    
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = trim($match[1]);
                $value = isset($match[2]) ? trim($match[2]) : str_replace(' ', '_', $key); // Replace spaces with underscores
                $lang[$key] = $value;
            }
        }
    }    
}
