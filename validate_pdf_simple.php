<?php

// Simple script to validate words in a PDF file
// Doesn't require Symfony or Doctrine

require_once __DIR__ . '/lib/MyI18nToolkit.class.php';

// Create a simple word validator with hardcoded common Bulgarian words
class SimpleWordValidator {
    private $commonWords = array(
        'сме' => true,
        'са' => true,
        'е' => true,
        'бих' => true,
        'би' => true,
        'камо' => true,
        'няма' => true,
        'ние' => true,
        'те' => true,
        'той' => true,
        'тя' => true,
        'то' => true
    );
    
    public function validateWord($word) {
        // Original word before normalization (for reporting)
        $originalWord = $word;
        
        // Normalize the word
        $word = $this->normalizeWord($word);
        
        // Skip empty strings, numbers, and single characters
        if (empty($word) || is_numeric($word) || mb_strlen($word, 'UTF-8') < 2) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'skipped'
            );
        }
        
        // For testing purposes, mark Latin-only words as INVALID
        // (Unlike in production where we just exclude them)
        if (preg_match('/^[a-zA-Z]+$/', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => false,  // Changed to false for testing
                'reason' => 'non_cyrillic'
            );
        }
        
        // Skip words with numbers (like "3D", "4k", etc.)
        if (preg_match('/\d/', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'contains_numbers'
            );
        }
        
        // List of known valid Bulgarian words
        $validBulgarianWords = array(
            'куче', 'котка', 'къща', 'град', 'улица', 'човек', 'жена', 'мъж', 'дете', 'семейство', 
            'училище', 'университет', 'книга', 'стол', 'маса', 'компютър', 'телефон', 'автомобил', 
            'влак', 'самолет', 'слънце', 'луна', 'звезда', 'небе', 'земя', 'вода', 'огън', 'въздух', 
            'ябълка', 'портокал', 'банан', 'ягода', 'хляб', 'сирене', 'месо', 'зеленчук', 'риба', 
            'птица', 'крава', 'кон', 'в', 'на', 'за', 'с', 'от', 'до', 'към', 'под', 'над', 'пред', 
            'зад', 'между', 'около', 'тук', 'там', 'горе', 'долу', 'вътре', 'вън', 'днес', 'вчера', 
            'утре', 'сега', 'рано', 'късно', 'бързо', 'бавно', 'добре', 'зле',
            // Common verb forms
            'съм', 'си', 'е', 'сме', 'сте', 'са', 'бях', 'беше', 'бяхме', 'бяхте', 'бяха',
            'отивам', 'отиваш', 'отива', 'отиваме', 'отивате', 'отиват',
            'ходя', 'ходиш', 'ходи', 'ходим', 'ходите', 'ходят',
            // Common adjectives
            'голям', 'малък', 'висок', 'нисък', 'широк', 'тесен', 'дълъг', 'къс', 'красив',
            'грозен', 'добър', 'лош', 'стар', 'млад', 'нов', 'стар', 'чист', 'мръсен', 'силен',
            'слаб', 'бърз', 'бавен', 'умен', 'глупав', 'ранен', 'късен', 'тъмен', 'светъл',
            'топъл', 'студен',
            // Pronouns
            'аз', 'ти', 'той', 'тя', 'то', 'ние', 'вие', 'те', 'мой', 'моя', 'мое', 'мои',
            'твой', 'твоя', 'твое', 'твои', 'негов', 'негова', 'негово', 'негови',
            'нейн', 'нейна', 'нейно', 'нейни',
            // Numerals
            'един', 'една', 'едно', 'два', 'две', 'три', 'четири', 'пет', 'шест', 'седем',
            'осем', 'девет', 'десет',
            // Special cases
            'камо', 'няма', 'би', 'бих', 'бихме'
        );
        
        // Check against our known list (proper validation)
        if (in_array(mb_strtolower($word, 'UTF-8'), $validBulgarianWords)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'valid_word'
            );
        }
        
        // Check if it's a common Bulgarian word from our simplified list
        if (isset($this->commonWords[mb_strtolower($word, 'UTF-8')])) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'common_word'
            );
        }
        
        // For testing purposes, consider mixed Latin/Cyrillic as INVALID 
        // (unlike production where we'd consider them encoding issues)
        if (preg_match('/[a-zA-Z].*[а-яА-ЯЁё]|[а-яА-ЯЁё].*[a-zA-Z]/u', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => false,  // Changed to false for testing
                'reason' => 'encoding_issue'
            );
        }
        
        // For testing purposes, consider special characters as INVALID
        if (preg_match('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]/i', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => false,  // Changed to false for testing
                'reason' => 'special_chars'
            );
        }
        
        // Made-up or misspelled Bulgarian words - for testing, anything else with Cyrillic 
        // that's not in our valid list is considered invalid
        if (preg_match('/[а-яА-ЯЁё]{2,}/u', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => false,  // For strict testing
                'reason' => 'invalid_bulgarian'
            );
        }
        
        // Not a valid Bulgarian word
        return array(
            'word' => $originalWord,
            'normalized' => $word,
            'isValid' => false,
            'reason' => 'unknown_word'
        );
    }
    
    public function validateWords($words) {
        $results = array();
        foreach ($words as $word) {
            $results[] = $this->validateWord($word);
        }
        return $results;
    }
    
    protected function normalizeWord($word) {
        // Convert to lowercase
        $word = mb_strtolower(trim($word), 'UTF-8');
        
        // Remove punctuation and special characters
        $word = preg_replace('/[^\p{L}\d\s-]/u', '', $word);
        
        return trim($word);
    }
    
    public function extractContext($text, $word, $contextLength = 30) {
        // Use case-insensitive search
        $pos = mb_stripos($text, $word, 0, 'UTF-8');
        
        if ($pos === false) {
            return '';
        }
        
        // Calculate start and end positions for the context
        $start = max(0, $pos - $contextLength);
        $end = min(mb_strlen($text, 'UTF-8'), $pos + mb_strlen($word, 'UTF-8') + $contextLength);
        
        // Extract the context
        $context = mb_substr($text, $start, $end - $start, 'UTF-8');
        
        // Add ellipsis if we're not at the beginning or end
        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($end < mb_strlen($text, 'UTF-8')) {
            $context .= '...';
        }
        
        return $context;
    }
}

class SimplePDFProcessor {
    private $validator;
    
    public function __construct() {
        $this->validator = new SimpleWordValidator();
    }
    
    public function processPDF($pdfFile) {
        if (!file_exists($pdfFile)) {
            echo "File not found: $pdfFile\n";
            return false;
        }
        
        echo "Processing PDF: $pdfFile\n";
        
        try {
            // Extract text from PDF
            $text = $this->extractTextFromPDF($pdfFile);
            $words = $this->extractWordsFromText($text);
            
            echo "Found " . count($words) . " words in the PDF\n";
            
            // Validate each word
            $results = $this->validator->validateWords($words);
            
            // Calculate statistics
            $validCount = 0;
            $invalidCount = 0;
            $invalidWords = array();
            
            foreach ($results as $result) {
                if ($result['isValid']) {
                    $validCount++;
                } else {
                    $invalidCount++;
                    // Add context for invalid words
                    $result['context'] = $this->validator->extractContext($text, $result['word']);
                    $invalidWords[] = $result;
                }
            }
            
            echo "Valid words: $validCount\n";
            echo "Invalid words: $invalidCount\n";
            
            // Show some invalid words
            if (count($invalidWords) > 0) {
                echo "\nSample of invalid words:\n";
                $limit = min(10, count($invalidWords));
                for ($i = 0; $i < $limit; $i++) {
                    echo "- '{$invalidWords[$i]['word']}': {$invalidWords[$i]['reason']}\n";
                    echo "  Context: {$invalidWords[$i]['context']}\n";
                }
                
                echo "\nTotal invalid words: " . count($invalidWords) . "\n";
            }
            
            // Generate HTML report
            $this->generateHTMLReport($pdfFile, $validCount, $invalidCount, $invalidWords);
            
            return true;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function extractTextFromPDF($pdfFile) {
        // Use pdftotext to extract text
        $outputFile = tempnam(sys_get_temp_dir(), 'pdf_txt_');
        $command = sprintf('pdftotext -enc UTF-8 "%s" "%s" 2>/dev/null', $pdfFile, $outputFile);
        
        system($command, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to extract text from PDF. Make sure pdftotext is installed.");
        }
        
        // Read extracted text
        $text = file_get_contents($outputFile);
        unlink($outputFile);
        
        return $text;
    }
    
    private function extractWordsFromText($text) {
        // Process hyphenated words across line breaks
        $text = preg_replace('/(\p{L}+)­[-]\s*\n\s*(\p{L}+)/u', '$1$2', $text);
        
        // Replace other line breaks with spaces
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);
        
        // Remove common PDF artifacts
        $text = preg_replace('/\d+\s*\/\s*\d+/', ' ', $text); // Page numbers (e.g., "5/125")
        $text = preg_replace('/https?:\/\/\S+/', ' ', $text);  // URLs
        $text = preg_replace('/www\.\S+/', ' ', $text);        // Web addresses
        $text = preg_replace('/\S+@\S+\.\S+/', ' ', $text);    // Email addresses
        
        // Split by word boundaries
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out non-words
        $filteredWords = array();
        foreach ($words as $word) {
            $word = trim($word);
            
            // Skip empty strings, numbers, and very short words
            if (empty($word) || is_numeric($word) || mb_strlen($word, 'UTF-8') < 2) {
                continue;
            }
            
            // Skip common non-word tokens
            $skipTokens = array('jpg', 'png', 'pdf', 'doc', 'ISBN', 'ISSN', 'www');
            if (in_array($word, $skipTokens, true)) {
                continue;
            }
            
            $filteredWords[] = $word;
        }
        
        return $filteredWords;
    }
    
    private function generateHTMLReport($pdfFile, $validCount, $invalidCount, $invalidWords) {
        $reportFile = 'report_' . basename($pdfFile, '.pdf') . '.html';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDF Word Validation Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .highlight { background-color: #ffff99; font-weight: bold; }
        .summary { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="summary">
        <h1>Word Validation Report</h1>
        <p><strong>File:</strong> ' . htmlspecialchars(basename($pdfFile)) . '</p>
        <p><strong>Total Valid Words:</strong> ' . $validCount . '</p>
        <p><strong>Total Invalid Words:</strong> ' . $invalidCount . '</p>
        <p><strong>Validity Rate:</strong> ' . round(($validCount / ($validCount + $invalidCount)) * 100, 2) . '%</p>
    </div>';
        
        if (count($invalidWords) > 0) {
            $html .= '
    <h2>Invalid Words</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Word</th>
            <th>Reason</th>
            <th>Context</th>
        </tr>';
            
            foreach ($invalidWords as $i => $word) {
                $highlightedContext = preg_replace(
                    '/(' . preg_quote($word['word'], '/') . ')/ui', 
                    '<span class="highlight">$1</span>', 
                    htmlspecialchars($word['context'])
                );
                
                $html .= '
        <tr>
            <td>' . ($i + 1) . '</td>
            <td>' . htmlspecialchars($word['word']) . '</td>
            <td>' . htmlspecialchars($word['reason']) . '</td>
            <td>' . $highlightedContext . '</td>
        </tr>';
            }
            
            $html .= '
    </table>';
        } else {
            $html .= '
    <p>No invalid words found.</p>';
        }
        
        $html .= '
</body>
</html>';
        
        file_put_contents($reportFile, $html);
        echo "Report saved to: $reportFile\n";
    }
}

class TextProcessor {
    private $validator;
    
    public function __construct() {
        $this->validator = new SimpleWordValidator();
    }
    
    public function processText($text) {
        echo "Processing text input...\n";
        
        try {
            // Extract words from text
            $words = $this->extractWordsFromText($text);
            
            echo "Found " . count($words) . " words in the text\n";
            
            // Validate each word
            $results = $this->validator->validateWords($words);
            
            // Calculate statistics
            $validCount = 0;
            $invalidCount = 0;
            $invalidWords = array();
            $reasonCounts = array();
            
            foreach ($results as $result) {
                // Keep track of reason counts
                if (!isset($reasonCounts[$result['reason']])) {
                    $reasonCounts[$result['reason']] = 0;
                }
                $reasonCounts[$result['reason']]++;
                
                if ($result['isValid']) {
                    $validCount++;
                } else {
                    $invalidCount++;
                    $invalidWords[] = $result;
                }
            }
            
            echo "Valid words: $validCount\n";
            echo "Invalid words: $invalidCount\n";
            
            // Show reason breakdown
            echo "\nReason breakdown:\n";
            foreach ($reasonCounts as $reason => $count) {
                echo "- $reason: $count\n";
            }
            
            // Show some invalid words
            if (count($invalidWords) > 0) {
                echo "\nInvalid words:\n";
                foreach ($invalidWords as $i => $word) {
                    echo "- '{$word['word']}': {$word['reason']}\n";
                }
            }
            
            return true;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function extractWordsFromText($text) {
        // Process hyphenated words across line breaks
        $text = preg_replace('/(\p{L}+)­[-]\s*\n\s*(\p{L}+)/u', '$1$2', $text);
        
        // Replace line breaks with spaces
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);
        
        // Split by word boundaries
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out empty strings
        $filteredWords = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (!empty($word)) {
                $filteredWords[] = $word;
            }
        }
        
        return $filteredWords;
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage: php " . basename(__FILE__) . " [--text \"your text\"] [pdf_file]\n";
    echo "  --text \"your text\"   Process the given text instead of a PDF file\n";
    echo "  pdf_file             Process the specified PDF file\n";
    exit(1);
}

// Check if text input mode is specified
if ($argc >= 3 && $argv[1] === '--text') {
    $text = $argv[2];
    $processor = new TextProcessor();
    $processor->processText($text);
} else {
    $pdfFile = $argv[1];
    $processor = new SimplePDFProcessor();
    $processor->processPDF($pdfFile);
}