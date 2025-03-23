<?php

// Include the toolkit for conversions
require_once __DIR__ . '/lib/MyI18nToolkit.class.php';

/**
 * Simple Bulgarian Word Validator with HTML reporting
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

/**
 * HTML Report Generator
 */
class HTMLReportGenerator 
{
    /**
     * Generate HTML report
     */
    public function generateReport($filename, $stats, $samples)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulgarian Text Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .summary { margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
        .stats { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
        .stat-box { flex: 1; min-width: 200px; margin: 5px; padding: 15px; background-color: #f0f0f0; border-radius: 5px; }
        .stat-box h3 { margin-top: 0; color: #444; }
        .stat-value { font-size: 24px; font-weight: bold; color: #3366cc; }
        .percent { font-size: 18px; color: #009900; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .category { font-weight: bold; color: #3366cc; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <h1>Bulgarian Text Analysis Report</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <p><strong>File Analyzed:</strong> ' . htmlspecialchars(basename($filename)) . '</p>
        <p><strong>Total Words:</strong> ' . number_format($stats['total']) . '</p>
        <p><strong>Valid Bulgarian Words:</strong> ' . number_format($stats['cyrillic']) . ' (' . number_format($stats['bulgarianPercentage'], 2) . '%)</p>
    </div>
    
    <div class="stats">
        <div class="stat-box">
            <h3>Pure Cyrillic Words</h3>
            <div class="stat-value">' . number_format($stats['cyrillic']) . '</div>
            <div class="percent">' . number_format(($stats['cyrillic'] / $stats['total']) * 100, 2) . '%</div>
        </div>
        
        <div class="stat-box">
            <h3>Latin-only Words</h3>
            <div class="stat-value">' . number_format($stats['latin']) . '</div>
            <div class="percent">' . number_format(($stats['latin'] / $stats['total']) * 100, 2) . '%</div>
        </div>
        
        <div class="stat-box">
            <h3>Mixed Alphabet</h3>
            <div class="stat-value">' . number_format($stats['mixed']) . '</div>
            <div class="percent">' . number_format(($stats['mixed'] / $stats['total']) * 100, 2) . '%</div>
        </div>
        
        <div class="stat-box">
            <h3>With Numbers</h3>
            <div class="stat-value">' . number_format($stats['numbers']) . '</div>
            <div class="percent">' . number_format(($stats['numbers'] / $stats['total']) * 100, 2) . '%</div>
        </div>
        
        <div class="stat-box">
            <h3>Skipped</h3>
            <div class="stat-value">' . number_format($stats['skipped']) . '</div>
            <div class="percent">' . number_format(($stats['skipped'] / $stats['total']) * 100, 2) . '%</div>
        </div>
        
        <div class="stat-box">
            <h3>Other</h3>
            <div class="stat-value">' . number_format($stats['other']) . '</div>
            <div class="percent">' . number_format(($stats['other'] / $stats['total']) * 100, 2) . '%</div>
        </div>
    </div>
    
    <h2>Sample Words by Category</h2>';
        
        foreach ($samples as $category => $words) {
            if (empty($words)) continue;
            
            $html .= '
    <h3 class="category">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $category))) . '</h3>
    <table>
        <tr>
            <th>#</th>
            <th>Word</th>
        </tr>';
            
            foreach ($words as $index => $word) {
                $html .= '
        <tr>
            <td>' . ($index + 1) . '</td>
            <td>' . htmlspecialchars($word) . '</td>
        </tr>';
            }
            
            $html .= '
    </table>';
        }
        
        $html .= '
    <div class="footer">
        <p>Report generated on ' . date('Y-m-d H:i:s') . ' using Bulgarian Text Validator</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// Process text file
$filename = isset($argv[1]) ? $argv[1] : 'a.txt';
$outputFile = isset($argv[2]) ? $argv[2] : 'report_a_detailed.html';

// Check if file exists
if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

// Process in chunks to handle large files
$chunkSize = 1024 * 1024; // 1MB chunks
$handle = fopen($filename, 'r');
if (!$handle) {
    die("Cannot open file: $filename\n");
}

// Initialize statistics
$stats = array(
    'total' => 0,
    'valid' => 0,
    'invalid' => 0,
    'cyrillic' => 0,
    'latin' => 0,
    'mixed' => 0,
    'numbers' => 0,
    'skipped' => 0,
    'other' => 0
);

// Samples of each category
$samples = array(
    'cyrillic_word' => array(),
    'non_cyrillic' => array(),
    'mixed_alphabet' => array(),
    'contains_numbers' => array(),
    'skipped' => array(),
    'other' => array()
);

// Max samples per category
$maxSamples = 30;

// Create validator
$validator = new SimpleBulgarianValidator();

echo "Analyzing Bulgarian text in: $filename\n";
echo "Generating HTML report to: $outputFile\n";

// Process file in chunks
while (!feof($handle)) {
    $chunk = fread($handle, $chunkSize);
    
    // Split chunk into words
    $words = preg_split('/[\s\p{P}]+/u', $chunk, -1, PREG_SPLIT_NO_EMPTY);
    
    // Validate words
    foreach ($words as $word) {
        $stats['total']++;
        
        $result = $validator->validateWord($word);
        
        if ($result['isValid']) {
            $stats['valid']++;
            
            // Count by category
            switch ($result['reason']) {
                case 'cyrillic_word':
                    $stats['cyrillic']++;
                    if (count($samples['cyrillic_word']) < $maxSamples) {
                        $samples['cyrillic_word'][] = $result['word'];
                    }
                    break;
                case 'non_cyrillic':
                    $stats['latin']++;
                    if (count($samples['non_cyrillic']) < $maxSamples) {
                        $samples['non_cyrillic'][] = $result['word'];
                    }
                    break;
                case 'mixed_alphabet':
                    $stats['mixed']++;
                    if (count($samples['mixed_alphabet']) < $maxSamples) {
                        $samples['mixed_alphabet'][] = $result['word'];
                    }
                    break;
                case 'contains_numbers':
                    $stats['numbers']++;
                    if (count($samples['contains_numbers']) < $maxSamples) {
                        $samples['contains_numbers'][] = $result['word'];
                    }
                    break;
                case 'skipped':
                    $stats['skipped']++;
                    if (count($samples['skipped']) < $maxSamples) {
                        $samples['skipped'][] = $result['word'];
                    }
                    break;
                default:
                    $stats['other']++;
                    if (count($samples['other']) < $maxSamples) {
                        $samples['other'][] = $result['word'];
                    }
                    break;
            }
        } else {
            $stats['invalid']++;
        }
    }
}

fclose($handle);

// Calculate percentage of valid Bulgarian words
$stats['bulgarianPercentage'] = ($stats['cyrillic'] / max(1, $stats['total'] - $stats['skipped'] - $stats['latin'])) * 100;

// Generate HTML report
$reportGenerator = new HTMLReportGenerator();
$htmlReport = $reportGenerator->generateReport($filename, $stats, $samples);

// Save report to file
file_put_contents($outputFile, $htmlReport);

echo "Analysis complete. Report saved to: $outputFile\n";
echo "--------------------\n";
echo "Total Words: {$stats['total']}\n";
echo "Valid Words: {$stats['valid']}\n";
echo "Invalid Words: {$stats['invalid']}\n";
echo "\nBreakdown:\n";
echo "- Pure Cyrillic Words: {$stats['cyrillic']}\n";
echo "- Latin-only Words: {$stats['latin']}\n";
echo "- Mixed Alphabet Words: {$stats['mixed']}\n";
echo "- Words with Numbers: {$stats['numbers']}\n";
echo "- Skipped (short/numbers): {$stats['skipped']}\n";
echo "- Other: {$stats['other']}\n";
echo "\nPercentage of valid Bulgarian words: " . number_format($stats['bulgarianPercentage'], 2) . "%\n";