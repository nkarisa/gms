<?php 

if(!function_exists('constructSeederData')){
    function constructSeederData($jsonData){
        $arrayData = json_decode($jsonData);
        $data = [];

        for($i = 0; $i < sizeof($arrayData); $i++){
            $data[$i] = $arrayData[$i];
        }

        return $data;
    }
}


if(!function_exists('csvToJson')){
    function csvToJsonLiteral(string $dataFile): ?string {
        $csvFilePath = APPPATH.'Database/Seeds/data_csv/'.$dataFile.'.csv';

        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            error_log("Error: CSV file not found or not readable at '$csvFilePath'.");
            return null;
        }
    
        $csvFile = fopen($csvFilePath, 'r');
        if ($csvFile === false) {
            error_log("Error: Could not open CSV file '$csvFilePath'.");
            return null;
        }
    
        // Read the first line (headers) and remove the BOM if present
        $firstLine = fgets($csvFile);
        if ($firstLine !== false) {
            $headers = str_getcsv(ltrim($firstLine, "\xEF\xBB\xBF"));
        } else {
            fclose($csvFile);
            error_log("Error: Could not read headers from CSV file '$csvFilePath'.");
            return null;
        }
    
        $jsonData = [];
        while ($row = fgetcsv($csvFile)) {
            if ($row !== false) {
                $rowData = [];
                $numHeaders = count($headers);
                $numValues = count($row);
                $count = min($numHeaders, $numValues);
    
                for ($i = 0; $i < $count; $i++) {
                    // Trim whitespace and newlines from the values
                    $rowData[$headers[$i]] = trim(str_replace(["\r\n", "\r", "\n"], ' ', $row[$i]));
                }
                $jsonData[] = $rowData;
            }
        }
    
        fclose($csvFile);
    
        $jsonOutput = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonOutput === false) {
            error_log("Error: Could not encode data to JSON. Error: " . json_last_error_msg());
            return null;
        }
    
        return $jsonOutput;
    }
}

if(!function_exists('isValidJSON')){
    function isValidJSON(string $string): bool {
        // Trim whitespace to avoid issues
        $trimmedString = trim($string);
    
        // Attempt to decode the string as JSON
        json_decode($trimmedString);
    
        // Check for decoding errors
        return (json_last_error() === JSON_ERROR_NONE);
    }
}