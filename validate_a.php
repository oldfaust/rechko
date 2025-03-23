<?php

// Include the toolkit for conversions
require_once __DIR__ . '/lib/MyI18nToolkit.class.php';

/**
 * Simple Bulgarian Word Validator
 */
class SimpleBulgarianValidator
{
    /**
     * Validate a single word
     *
     * @param string $word The word to validate
     * @return array Result with validation status and details
     */
    public function validateWord($word)
    {
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
        
        // Skip words composed entirely of Latin letters (can't be Bulgarian words)
        if (preg_match('/^[a-zA-Z]+$/', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
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
        
        // Pure Cyrillic words (valid Bulgarian)
        if (preg_match('/^[а-яА-ЯЁё]+$/u', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'cyrillic_word'
            );
        }
        
        // Consider mixed Latin/Cyrillic as encoding issues (to be skipped/marked valid)
        if (preg_match('/[a-zA-Z].*[а-яА-ЯЁё]|[а-яА-ЯЁё].*[a-zA-Z]/u', $word)) {
            return array(
                'word' => $originalWord,
                'normalized' => $word,
                'isValid' => true,
                'reason' => 'mixed_alphabet'
            );
        }
        
        // Other characters, probably valid
        return array(
            'word' => $originalWord,
            'normalized' => $word,
            'isValid' => true,
            'reason' => 'other'
        );
    }
    
    /**
     * Validate multiple words
     *
     * @param array $words Array of words to validate
     * @return array Results for each word
     */
    public function validateWords(array $words)
    {
        $results = array();
        
        foreach ($words as $word) {
            $results[] = $this->validateWord($word);
        }
        
        return $results;
    }
    
    /**
     * Normalize a word for validation
     *
     * @param string $word The word to normalize
     * @return string Normalized word
     */
    protected function normalizeWord($word)
    {
        // Convert to lowercase
        $word = mb_strtolower(trim($word), 'UTF-8');
        
        // Remove punctuation and special characters
        $word = preg_replace('/[^\p{L}\d\s-]/u', '', $word);
        
        return trim($word);
    }
}

// Process text file
$filename = isset($argv[1]) ? $argv[1] : 'a.txt';

// Check if file exists
if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

// Read file in chunks to handle large files
$chunkSize = 1024 * 1024; // 1MB chunks
$handle = fopen($filename, 'r');
if (!$handle) {
    die("Cannot open file: $filename\n");
}

// Initialize statistics
$totalWords = 0;
$validWords = 0;
$invalidWords = 0;
$cyrillic = 0;
$latin = 0;
$mixedAlphabet = 0;
$withNumbers = 0;
$skipped = 0;
$other = 0;

// Create validator
$validator = new SimpleBulgarianValidator();

echo "Analyzing Bulgarian text in: $filename\n";

// Process file in chunks
while (!feof($handle)) {
    $chunk = fread($handle, $chunkSize);
    
    // Split chunk into words
    $words = preg_split('/[\s\p{P}]+/u', $chunk, -1, PREG_SPLIT_NO_EMPTY);
    
    // Validate words
    foreach ($words as $word) {
        $totalWords++;
        
        $result = $validator->validateWord($word);
        
        if ($result['isValid']) {
            $validWords++;
            
            // Count by category
            switch ($result['reason']) {
                case 'cyrillic_word':
                    $cyrillic++;
                    break;
                case 'non_cyrillic':
                    $latin++;
                    break;
                case 'mixed_alphabet':
                    $mixedAlphabet++;
                    break;
                case 'contains_numbers':
                    $withNumbers++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
                default:
                    $other++;
                    break;
            }
        } else {
            $invalidWords++;
        }
    }
}

fclose($handle);

// Print statistics
echo "Analysis complete.\n";
echo "--------------------\n";
echo "Total Words: $totalWords\n";
echo "Valid Words: $validWords\n";
echo "Invalid Words: $invalidWords\n";
echo "\nBreakdown:\n";
echo "- Pure Cyrillic Words: $cyrillic\n";
echo "- Latin-only Words: $latin\n";
echo "- Mixed Alphabet Words: $mixedAlphabet\n";
echo "- Words with Numbers: $withNumbers\n";
echo "- Skipped (short/numbers): $skipped\n";
echo "- Other: $other\n";

// Calculate percentage of valid Bulgarian words (Cyrillic + some of the mixed alphabet words)
$bulgarianWordPercentage = ($cyrillic / max(1, $totalWords - $skipped - $latin)) * 100;
echo "\nPercentage of valid Bulgarian words: " . number_format($bulgarianWordPercentage, 2) . "%\n";