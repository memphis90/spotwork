<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // NOTE: Override baseUrl so routes resolve correctly when APP_URL has a subdirectory path (e.g. http://localhost/jobmap)
    protected string $baseUrl = 'http://localhost';
}
