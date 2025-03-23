# Claude Guidelines for rechko codebase

## Build & Test Commands
```bash
# Run all tests
php ./symfony test:all

# Run unit tests
php ./symfony test:unit

# Run a single unit test
php ./symfony test:unit test/unit/incorrectFormsTest.php

# Run functional tests
php ./symfony test:functional

# Clear cache
php ./symfony cc
```

## Code Style Guidelines
- **Naming**: camelCase for methods and variables, PascalCase for classes
- **Indentation**: Use tabs in PHP files
- **Classes**: One class per file, matching the filename (e.g., `Word.class.php` contains `Word` class)
- **Methods**: 
  - Keep methods short and focused
  - Use type hints where possible for PHP 8.1 compatibility
- **Error Handling**: Use exceptions with meaningful messages
- **Doctrine Models**: Follow Doctrine naming conventions for model classes and tables
- **Symfony**: Follow Symfony 1.x conventions for actions, templates, and forms

## PHP 8.1 Compatibility Notes
- Use string array syntax instead of curly braces for string access
- Avoid deprecated functions like `create_function()`
- Don't use `get_magic_quotes_gpc()`

## Database Structure and Word Form Management

The codebase implements a Bulgarian spelling dictionary ("Български правописен речник") with a sophisticated system for handling word forms:

### Main Database Tables
- `AbstractWord`: Base table with core word properties (name, name_stressed with accent marks, syllabified form)
- `Word`: Main dictionary entries inheriting from AbstractWord
- `DerivativeForm`: Stores grammatical variations of base words
- `IncorrectForm`: Stores common misspellings
- `WordType`: Defines grammatical categories and rules

### Word Form Generation System
- Rule-based generation for grammatical forms via `DerivativeFormsGenerator` class
- Forms are generated based on word type rules and patterns
- Each part of speech (nouns, verbs, adjectives, etc.) has specialized form generation logic

### Bulgarian Linguistic Features
- Stress marking using accent grave (`)
- Syllabification for proper hyphenation
- Rich support for Bulgarian's complex morphology:
  - Verb conjugations with 9 tenses and 3 moods
  - Noun declensions with gender, number, definiteness
  - Adjective forms with gender and number agreement

### Form Search Capabilities
- Users can search using any word form
- The system finds the base word and shows all related forms
- Searches work with or without stress marks

When a word is added to the database:
1. It's stored in the `Word` table
2. All grammatical forms are automatically generated
3. Common misspellings are calculated and stored
4. Word forms are accessible through relationships

## Word Validation API

The system provides an API for validating Bulgarian words in texts:

### Core Components
- `WordValidatorAPI`: Core class for validating if a word is a valid Bulgarian form
- `PDFWordValidator`: Tool for extracting and validating words from PDF files
- `ValidatePDFWordsTask`: Symfony task for command-line validation of PDFs

### Usage
```bash
# Validate words in a PDF file and generate an HTML report
php ./symfony validate:pdf-words path/to/file.pdf --output=report.html

# Generate a CSV report
php ./symfony validate:pdf-words --format=csv --output=report.csv path/to/file.pdf

# Ignore specific words (e.g., proper names, technical terms)
php ./symfony validate:pdf-words --ignore=ignored_words.txt path/to/file.pdf
```

### Features
- Validates words against base forms, derivative forms, and known incorrect forms
- Supports Latin-to-Cyrillic character conversion
- Generates reports in HTML, CSV, or JSON formats
- Shows context for invalid words to help with correction
- Identifies correct forms for known misspellings