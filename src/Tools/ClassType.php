<?php

namespace quintenmbusiness\LaravelProjectGeneration\Tools;

use Illuminate\Support\Str;
use Tests\TestCase;

enum ClassType: string
{
    case MODEL = 'model';
    case FACTORY = 'factory';
    case REPOSITORY = 'repository';
    case SERVICE = 'service';
    case CONTROLLER = 'controller';
    case SEEDER = 'seeder';
    case MIGRATION = 'migration';
    case REQUEST = 'request';
    case RESOURCE = 'resource';
    case POLICY = 'policy';
    case JOB = 'job';

    CASE MODEL_TEST = 'ModelTest';
    case REPOSITORY_TEST = 'RepositoryTest';
    case SERVICE_TEST = 'ServiceTest';

    /**
     * Return the default class basename for a given table name
     */
    public function basename(string $table): string
    {
        $base = Str::studly(Str::singular($table));

        return match ($this) {
            self::MODEL => $base,
            self::FACTORY => $base . 'Factory',
            self::REPOSITORY => $base . 'Repository',
            self::SERVICE => $base . 'Service',
            self::CONTROLLER => $base . 'Controller',
            self::SEEDER => $base . 'Seeder',
            self::MIGRATION => 'Create' . Str::plural($base) . 'Table',
            self::REQUEST => $base . 'Request',
            self::RESOURCE => $base . 'Resource',
            self::POLICY => $base . 'Policy',
            self::JOB => $base . 'Job',
            self::REPOSITORY_TEST => $base . 'RepositoryTest',
            self::SERVICE_TEST => $base . 'ServiceTest',
            self::MODEL_TEST => $base . 'Test',
        };
    }

    /**
     * Return the fully qualified class import path
     */
    public function import(string $table): string
    {
        $base = $this->basename($table);

        return match ($this) {
            self::MODEL => "App\\Models\\$base",
            self::FACTORY => "Database\\Factories\\$base",
            self::REPOSITORY => "App\\Repositories\\$base",
            self::SERVICE => "App\\Services\\$base",
            self::CONTROLLER => "App\\Http\\Controllers\\$base",
            self::SEEDER => "Database\\Seeders\\$base",
            self::MIGRATION => "Database\\Migrations\\$base",
            self::REQUEST => "App\\Http\\Requests\\$base",
            self::RESOURCE => "App\\Http\\Resources\\$base",
            self::POLICY => "App\\Policies\\$base",
            self::JOB => "App\\Jobs\\$base",

            //repository + model + service tests should be added here
            self::MODEL_TEST => 'Tests\TestCase',
        };
    }

    /**
     * Return the default class namespace
     */
    public function namespace(string $table): string
    {
        return match ($this) {
            self::MODEL => 'App\Models',
            self::FACTORY => 'Database\Factories',
            self::REPOSITORY => 'App\Repositories',
            self::SERVICE => 'App\Services',
            self::CONTROLLER => 'App\Http\Controllers',
            self::SEEDER => 'Database\Seeders',
            self::MIGRATION => 'Database\Migrations',
            self::REQUEST => 'App\Http\Requests',
            self::RESOURCE => 'App\Http\Resources',
            self::POLICY => 'App\Policies',
            self::JOB => 'App\Jobs',

            self::REPOSITORY_TEST => 'Tests\Unit\Repositories',
            self::SERVICE_TEST => 'Tests\Unit\Services',
            self::MODEL_TEST => 'Tests\Unit\Models',
            //repository + model + service tests should be added here
        };
    }

    public function uses(): array
    {
        return match ($this) {
            self::MODEL_TEST => ['Illuminate\\Foundation\\Testing\\RefreshDatabase'],
            self::REPOSITORY_TEST => ['Illuminate\\Foundation\\Testing\\RefreshDatabase'],
            self::SERVICE_TEST => ['Illuminate\\Foundation\\Testing\\RefreshDatabase'],
            default => []
        };
    }

    /**
     * Return the parent class basename if applicable
     */
    public function extendsBasename(): ?string
    {
        return match ($this) {
            self::MODEL => 'Model',
            self::FACTORY => 'Factory',
            self::REPOSITORY => null,
            self::SERVICE => null,
            self::CONTROLLER => 'Controller',
            self::SEEDER => 'Seeder',
            self::MIGRATION => null,
            self::REQUEST => null,
            self::RESOURCE => null,
            self::POLICY => null,
            self::JOB => null,

            //add the testCase here
            self::MODEL_TEST => 'TestCase',
            self::REPOSITORY_TEST => 'TestCase',
            self::SERVICE_TEST => 'TestCase',
        };
    }

    /**
     * Return the fully qualified class import for the parent class if any
     */
    public function extendsImport(): ?string
    {
        return match ($this) {
            self::MODEL => 'Illuminate\Database\Eloquent\Model',
            self::FACTORY => 'Illuminate\Database\Eloquent\Factories\Factory',
            self::CONTROLLER => 'App\Http\Controllers\Controller',
            self::SEEDER => 'Illuminate\Database\Seeder',

            self::MODEL_TEST => 'Tests\TestCast',
            self::REPOSITORY_TEST => 'Tests\TestCast',
            self::SERVICE_TEST => 'Tests\TestCast',
            default => null,
        };
    }
}
