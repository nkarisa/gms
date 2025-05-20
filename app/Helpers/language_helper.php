<?php 
if (!function_exists('process_get_phrase_files')){

    function process_get_phrase_files($directory = APPPATH, string $langCode, string $countryCode){
        helper('inflector');
        $lang = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                process_file_content($content, $lang);
            }
        }

        $targetDir = APPPATH . "Language/" . strtolower($langCode) . "/" . strtoupper($countryCode);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $output_file = $targetDir . '/App.php';
        processOutputLanguageFile($lang, $output_file);
    }

    /**
     * @param array $lang
     * @param string $output_file
     * @return void
     */
    function processOutputLanguageFile(array $lang, string $output_file): void
    {
        $php_content = "<?php\n\n\$lang = array(\n";
        foreach ($lang as $key => $value) {
            $php_content .= "    '" . str_replace("'", "\\'", $key) . "' => '" . humanize(str_replace("'", "\\'", $value)) . "',\n";
        }
        $php_content .= ");\n\nreturn \$lang;";

        if (file_put_contents($output_file, $php_content) === false) {
            echo "Error writing to $output_file\n";
        } else {
            echo "Successfully wrote to $output_file\n";
        }
    }
}
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
        processOutputLanguageFile($lang, $output_file);
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
