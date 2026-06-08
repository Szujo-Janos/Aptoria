<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // XAMPP PHP may not expose PHPUnit's APP_ENV through variables_order.
        $this->app->detectEnvironment(fn (): string => 'testing');
    }
}
