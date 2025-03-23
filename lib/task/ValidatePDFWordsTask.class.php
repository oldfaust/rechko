<?php

/**
 * ValidatePDFWordsTask - Command-line task for validating Bulgarian words in PDF files
 *
 * @package    rechnik
 * @author     claude-code
 */
class ValidatePDFWordsTask extends sfBaseTask
{
    /**
     * Configure the task
     */
    protected function configure()
    {
        $this->namespace        = 'validate';
        $this->name             = 'pdf-words';
        $this->briefDescription = 'Validates Bulgarian words in a PDF file';
        $this->detailedDescription = <<<EOF
The [validate:pdf-words|INFO] task validates all Bulgarian words in a PDF file
and generates a report of invalid words with context:

  [./symfony validate:pdf-words path/to/file.pdf|INFO]

You can specify the output format and file:

  [./symfony validate:pdf-words --format=html --output=report.html path/to/file.pdf|INFO]

Available formats: html (default), csv, json

You can provide a list of words to ignore during validation:

  [./symfony validate:pdf-words --ignore=words_to_ignore.txt path/to/file.pdf|INFO]
EOF;

        $this->addArgument('pdf_file', sfCommandArgument::REQUIRED, 'The PDF file to validate');
        $this->addOption('output', null, sfCommandOption::PARAMETER_REQUIRED, 'Output file for the report', 'validation-report.html');
        $this->addOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (html, csv, json)', 'html');
        $this->addOption('ignore', null, sfCommandOption::PARAMETER_REQUIRED, 'File with words to ignore (one per line)', null);
    }

    /**
     * Execute the task
     *
     * @param array $arguments  Arguments
     * @param array $options    Options
     */
    protected function execute($arguments = array(), $options = array())
    {
        // Initialize Doctrine - this is needed because we're running from CLI
        $databaseManager = new sfDatabaseManager($this->configuration);
        $databaseManager->loadConfiguration();
        
        $pdfFile = $arguments['pdf_file'];
        $outputFile = $options['output'];
        $outputFormat = $options['format'];
        
        // Create validator
        $validator = new PDFWordValidator();
        
        // Load ignored words if specified
        if ($options['ignore'] && file_exists($options['ignore'])) {
            $ignoredWords = file($options['ignore'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $validator->addIgnoredWords($ignoredWords);
            $this->logSection('info', 'Loaded ' . count($ignoredWords) . ' words to ignore');
        }
        
        $this->logSection('info', 'Validating words in ' . basename($pdfFile));
        
        try {
            // Extract and validate words
            $startTime = microtime(true);
            $results = $validator->validatePDFFile($pdfFile);
            $endTime = microtime(true);
            
            // Count invalid words
            $invalidWords = 0;
            foreach ($results['results'] as $result) {
                if ($result['isValid'] === false) {
                    $invalidWords++;
                }
            }
            
            // Generate and save report
            $report = $validator->generateReport($results, $outputFormat);
            file_put_contents($outputFile, $report);
            
            // Log results
            $this->logSection('success', 'Validation complete in ' . round($endTime - $startTime, 2) . ' seconds');
            $this->logSection('info', 'Total words processed: ' . $results['total_words']);
            $this->logSection('info', 'Invalid words found: ' . $invalidWords);
            $this->logSection('info', 'Report saved to: ' . $outputFile);
            
        } catch (Exception $e) {
            $this->logSection('error', $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}