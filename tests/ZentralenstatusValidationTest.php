<?php

declare(strict_types=1);

namespace tests;

use TestCaseSymconValidation;

include_once __DIR__ . '/stubs/Validator.php';

class ZentralenstatusValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule_Servicemeldungen(): void
    {
        $this->validateModule(__DIR__ . '/../Servicemeldungen');
    }

    public function testValidateModule_Zentralenstatus(): void
    {
        $this->validateModule(__DIR__ . '/../Zentralenstatus');
    }
}