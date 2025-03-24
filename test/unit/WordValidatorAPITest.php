<?php
require_once dirname(__FILE__).'/../bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/WordValidatorAPI.class.php';

$t = new lime_test(5, new lime_output_color());

$t->diag('WordValidatorAPI::validateWord()');

$validator = new WordValidatorAPI();

// Test with a known Bulgarian word
$result = $validator->validateWord('абсурд');
$t->is($result['isValid'], true, 'Valid Bulgarian word is recognized');

// Test with a derivative form
$result = $validator->validateWord('абсурдно');
$t->is($result['isValid'], true, 'Derivative form is recognized as valid');

// Test with Latin to Cyrillic conversion
$result = $validator->validateWord('absurd');
$t->is($result['isValid'], true, 'Latin character input is converted to Cyrillic and validated');

// Test with a known incorrect form
$result = $validator->validateWord('апсурт');
$t->is($result['isValid'], false, 'Incorrect form is recognized as invalid');

// Test with a completely made-up word
$result = $validator->validateWord('зюмбюлакалия');
$t->is($result['isValid'], false, 'Made-up word is recognized as invalid');