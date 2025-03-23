<?php

/**
 * WordValidatorAPI - API for validating Bulgarian word forms
 * 
 * Checks if a given word is a valid Bulgarian word form by checking:
 * 1. If it exists as a base word in the dictionary
 * 2. If it exists as a derivative form of a base word
 * 3. If it is a known incorrect form with a corresponding correct form
 *
 * @package    rechnik
 * @author     claude-code
 */
class WordValidatorAPI
{
    /**
     * Validate a single word
     *
     * @param string $word The word to validate
     * @return array Result with validation status and details
     */
    public function validateWord($word)
    {
        // Normalize the word (convert Latin to Cyrillic if needed)
        $word = $this->normalizeWord($word);
        
        // Skip empty strings, numbers, and single characters
        if (empty($word) || is_numeric($word) || mb_strlen($word, 'UTF-8') < 2) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'skipped',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Skip words composed entirely of Latin letters (can't be Bulgarian words)
        if (preg_match('/^[a-zA-Z]+$/', $word)) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'non_cyrillic',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Skip words with numbers (like "3D", "4k", etc.)
        if (preg_match('/\d/', $word)) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'contains_numbers',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Special case for common Bulgarian words that might be missing from the database
        // Including verb forms of "to be" (съм)
        $commonWords = array(
            'сме' => 'verb form of съм (we are)',
            'са' => 'verb form of съм (they are)',
            'е' => 'verb form of съм (is)',
            'бих' => 'verb form of съм (would)',
            'би' => 'verb form of съм (would)',
            'камо' => 'where to (archaic)',
            'няма' => 'there is not'
        );
        
        if (isset($commonWords[$word])) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'common_word',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Check if it's a base word
        $baseWords = Doctrine_Core::getTable('Word')->getByName($word);
        if (count($baseWords) > 0) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'base_word',
                'type' => isset($baseWords[0]['Type']) ? $baseWords[0]['Type']['name'] : null,
                'correctForm' => null
            );
        }
        
        // Check if it's a derivative form
        // DerivativeFormTable::getByName() filters out infinitive forms (where is_infinitive = 1)
        // For verb forms like "сме", "са" which should be in the derivative_form table
        // We need to update our query to check for all records
        $qb = Doctrine_Core::getTable('DerivativeForm')->createQuery('df')
            ->where('df.name = ?', $word);
        $derivativeForms = $qb->execute();
        
        if (count($derivativeForms) > 0) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'derivative_form',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Check if it's a known incorrect form
        $incorrectForms = Doctrine_Core::getTable('IncorrectForm')->getByNameWithCorrectWord($word);
        if (count($incorrectForms) > 0) {
            return array(
                'word' => $word,
                'isValid' => false,
                'reason' => 'incorrect_form',
                'type' => null,
                'correctForm' => $incorrectForms[0]['CorrectWord']['name']
            );
        }
        
        // Check for mixed encoding issues (common in PDFs)
        // Words with a mix of Latin and Cyrillic are likely encoding problems
        if (preg_match('/[a-zA-Z].*[а-яА-ЯЁё]|[а-яА-ЯЁё].*[a-zA-Z]/u', $word)) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'encoding_issue',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Special case for common encoding issues
        if (preg_match('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]/i', $word)) {
            return array(
                'word' => $word,
                'isValid' => true,
                'reason' => 'special_chars',
                'type' => null,
                'correctForm' => null
            );
        }
        
        // Not found in any form
        return array(
            'word' => $word,
            'isValid' => false,
            'reason' => 'unknown_word',
            'type' => null,
            'correctForm' => null
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
     * - Convert Latin characters to Cyrillic
     * - Trim whitespace
     * - Remove special characters
     *
     * @param string $word The word to normalize
     * @return string Normalized word
     */
    protected function normalizeWord($word)
    {
        // Convert to lowercase
        $word = mb_strtolower(trim($word), 'UTF-8');
        
        // Convert Latin to Cyrillic
        $word = MyI18nToolkit::latcyr($word);
        
        // Remove punctuation and special characters
        $word = preg_replace('/[^\p{L}\d\s-]/u', '', $word);
        
        return trim($word);
    }
    
    /**
     * Extract context from text for a given word
     *
     * @param string $text Full text to extract context from
     * @param string $word The word to find
     * @param int $contextLength Number of characters to include before and after the word
     * @return string Context snippet with the word
     */
    public function extractContext($text, $word, $contextLength = 30)
    {
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