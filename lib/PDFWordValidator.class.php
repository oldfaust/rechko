<?php

/**
 * PDFWordValidator - Tool for validating words in PDF files
 * 
 * Extracts text from PDFs, parses into words, validates each one,
 * and generates reports of invalid Bulgarian words
 *
 * @package    rechnik
 * @author     claude-code
 */
class PDFWordValidator
{
    /**
     * @var WordValidatorAPI
     */
    protected $validator;
    
    /**
     * @var array Words to ignore in validation
     */
    protected $ignoredWords = array();
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->validator = new WordValidatorAPI();
    }
    
    /**
     * Add words to ignore during validation
     *
     * @param array $words Words to ignore
     * @return PDFWordValidator
     */
    public function addIgnoredWords(array $words)
    {
        foreach ($words as $word) {
            $this->ignoredWords[mb_strtolower(trim($word), 'UTF-8')] = true;
        }
        
        return $this;
    }
    
    /**
     * Extract text from a PDF file using pdftotext (must be installed)
     *
     * @param string $pdfFile Path to PDF file
     * @return string Extracted text
     * @throws Exception If pdftotext is not available or extraction fails
     */
    public function extractTextFromPDF($pdfFile)
    {
        if (!file_exists($pdfFile)) {
            throw new Exception("PDF file not found: $pdfFile");
        }
        
        // Create a temporary file for the output
        $outputFile = tempnam(sys_get_temp_dir(), 'pdf_extract_');
        
        // Use pdftotext command-line tool to extract text
        $command = sprintf('pdftotext -enc UTF-8 "%s" "%s"', $pdfFile, $outputFile);
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to extract text from PDF. Make sure pdftotext is installed.");
        }
        
        // Read the extracted text
        $text = file_get_contents($outputFile);
        
        // Clean up temp file
        unlink($outputFile);
        
        return $text;
    }
    
    /**
     * Split text into words
     *
     * @param string $text Text to split
     * @return array Words
     */
    public function extractWordsFromText($text)
    {
        // Replace line breaks with spaces
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);
        
        // Split by word boundaries
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out non-words
        $filteredWords = array();
        foreach ($words as $word) {
            $word = trim($word);
            
            // Skip empty strings, numbers, URLs, emails, and ignored words
            if (empty($word) ||
                is_numeric($word) ||
                preg_match('/^https?:\/\//i', $word) ||
                preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $word) ||
                isset($this->ignoredWords[mb_strtolower($word, 'UTF-8')])) {
                continue;
            }
            
            $filteredWords[] = $word;
        }
        
        return $filteredWords;
    }
    
    /**
     * Validate all words in a PDF file
     *
     * @param string $pdfFile Path to PDF file
     * @return array Validation results
     */
    public function validatePDFFile($pdfFile)
    {
        // Extract text from PDF
        $text = $this->extractTextFromPDF($pdfFile);
        
        // Extract words from text
        $words = $this->extractWordsFromText($text);
        
        // Validate words
        $results = $this->validator->validateWords($words);
        
        // Add context for invalid words
        foreach ($results as &$result) {
            if ($result['isValid'] === false) {
                $result['context'] = $this->validator->extractContext($text, $result['word']);
            }
        }
        
        return array(
            'filename' => basename($pdfFile),
            'total_words' => count($words),
            'results' => $results
        );
    }
    
    /**
     * Generate a report of invalid words
     *
     * @param array $validationResults Results from validatePDFFile
     * @param string $outputFormat Format of the report (html, csv, json)
     * @return string Report in the requested format
     */
    public function generateReport($validationResults, $outputFormat = 'html')
    {
        // Filter results to get only invalid words
        $invalidWords = array();
        foreach ($validationResults['results'] as $result) {
            if ($result['isValid'] === false) {
                $invalidWords[] = $result;
            }
        }
        
        switch ($outputFormat) {
            case 'json':
                return json_encode(array(
                    'filename' => $validationResults['filename'],
                    'total_words' => $validationResults['total_words'],
                    'invalid_words_count' => count($invalidWords),
                    'invalid_words' => $invalidWords
                ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                $csv = "Word,Reason,CorrectForm,Context\n";
                foreach ($invalidWords as $word) {
                    $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                        $word['word'],
                        $word['reason'],
                        $word['correctForm'] ?: '',
                        str_replace('"', '""', $word['context'])
                    );
                }
                return $csv;
                
            case 'html':
            default:
                $html = '<html><head><meta charset="UTF-8"><title>Word Validation Report</title>';
                $html .= '<style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .highlight { background-color: #ffff99; font-weight: bold; }
                    .summary { margin-bottom: 20px; }
                </style></head><body>';
                
                // Summary
                $html .= '<div class="summary">';
                $html .= '<h1>Word Validation Report</h1>';
                $html .= '<p><strong>File:</strong> ' . htmlspecialchars($validationResults['filename']) . '</p>';
                $html .= '<p><strong>Total Words:</strong> ' . $validationResults['total_words'] . '</p>';
                $html .= '<p><strong>Invalid Words:</strong> ' . count($invalidWords) . '</p>';
                $html .= '</div>';
                
                if (count($invalidWords) > 0) {
                    $html .= '<table>';
                    $html .= '<tr><th>#</th><th>Word</th><th>Reason</th><th>Correct Form</th><th>Context</th></tr>';
                    
                    foreach ($invalidWords as $i => $word) {
                        $highlightedContext = preg_replace(
                            '/(' . preg_quote($word['word'], '/') . ')/ui',
                            '<span class="highlight">$1</span>',
                            htmlspecialchars($word['context'])
                        );
                        
                        $html .= '<tr>';
                        $html .= '<td>' . ($i + 1) . '</td>';
                        $html .= '<td>' . htmlspecialchars($word['word']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($word['reason']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($word['correctForm'] ?: '') . '</td>';
                        $html .= '<td>' . $highlightedContext . '</td>';
                        $html .= '</tr>';
                    }
                    
                    $html .= '</table>';
                } else {
                    $html .= '<p>No invalid words found.</p>';
                }
                
                $html .= '</body></html>';
                return $html;
        }
    }
    
    /**
     * Validate PDF and save report to file
     *
     * @param string $pdfFile Path to PDF file
     * @param string $outputFile Path to output report file
     * @param string $outputFormat Format of the report (html, csv, json)
     * @return boolean Success
     */
    public function validateAndSaveReport($pdfFile, $outputFile, $outputFormat = 'html')
    {
        try {
            $results = $this->validatePDFFile($pdfFile);
            $report = $this->generateReport($results, $outputFormat);
            
            file_put_contents($outputFile, $report);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}