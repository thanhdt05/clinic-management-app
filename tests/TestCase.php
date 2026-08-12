<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        putenv('DB_HOST=db_test');
        putenv('DB_DATABASE=clinic_app_test');
        $_ENV['DB_HOST'] = 'db_test';
        $_ENV['DB_DATABASE'] = 'clinic_app_test';
        $_SERVER['DB_HOST'] = 'db_test';
        $_SERVER['DB_DATABASE'] = 'clinic_app_test';

        $app = parent::createApplication();

        $app['config']->set('database.connections.pgsql.host', 'db_test');
        $app['config']->set('database.connections.pgsql.database', 'clinic_app_test');

        return $app;
    }
}
