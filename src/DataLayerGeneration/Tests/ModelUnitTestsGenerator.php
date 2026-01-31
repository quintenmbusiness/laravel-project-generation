<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\Tests;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationThroughDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class ModelUnitTestsGenerator extends ClassGeneratorTemplate
{
    public function getClassType(): ClassType
    {
        return ClassType::MODEL;
    }

    public function getPath(): string
    {
        $dir = base_path('tests/Unit/Models');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir . DIRECTORY_SEPARATOR . $this->getClassName() . 'Test.php';
    }

    protected function inferFillable(): array
    {
        $fillable = $this->table->columns
            ->pluck('name')
            ->reject(fn($n) => in_array($n, ['id', 'created_at', 'updated_at', 'deleted_at']))
            ->values()
            ->toArray();

        return $fillable;
    }

    protected function inferCasts(): array
    {
        $casts = [];

        foreach ($this->table->columns as $col) {
            $raw = strtolower($col->rawType ?? '');
            $name = $col->name;

            if (str_contains($raw, 'tinyint(1)')) {
                $casts[$name] = 'bool';
            } elseif (str_contains($raw, 'int')) {
                $casts[$name] = 'int';
            } elseif (str_contains($raw, 'decimal') || str_contains($raw, 'numeric') || str_contains($raw, 'float') || str_contains($raw, 'double')) {
                $casts[$name] = 'float';
            } elseif (str_contains($raw, 'json')) {
                $casts[$name] = 'array';
            }
        }

        return $casts;
    }

    protected function relationPhpUnitAssertionClass(ModelRelationshipType $type): ?string
    {
        return match ($type) {
            ModelRelationshipType::BELONGS_TO => '\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ModelRelationshipType::HAS_MANY => '\\Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ModelRelationshipType::HAS_ONE => '\\Illuminate\\Database\\Eloquent\\Relations\\HasOne',
            default => null
        };
    }

    protected function buildTestClassString(): string
    {
        $modelClass = $this->getClassType()->namespace($this->table->name) . '\\' . $this->getClassName();
        $testClassNamespace = 'Tests\\Unit\\Models';
        $className = $this->getClassName() . 'Test';
        $modelShort = $this->getClassName();

        $fillable = $this->inferFillable();
        $fillableExport = var_export(array_values($fillable), true);

        $relations = [];
        foreach ($this->table->relations as $relation) {
            $relations[] = [
                'name' => $relation->relationName,
                'type' => $relation->type,
            ];
        }


        $relationAssertions = '';
        foreach ($relations as $r) {
            $assertClass = $this->relationPhpUnitAssertionClass($r['type']);
            $relationAssertions .= <<<PHP
                    public function test_relation_{$r['name']}_exists_and_returns_relation()
                    {
                        \$model = new {$modelShort}();
                        \$this->assertTrue(method_exists(\$model, '{$r['name']}'));
                        \$relation = \$model->{$r['name']}();
                        \$this->assertInstanceOf({$assertClass}::class, \$relation);
                    }
PHP;
        }

        $factoryTest = <<<PHP

    public function test_factory_can_make_instance()
    {
        if (! method_exists({$modelShort}::class, 'factory')) {
            \$this->assertTrue(true);
            return;
        }

        \$instance = {$modelShort}::factory()->make();
        \$this->assertInstanceOf({$modelShort}::class, \$instance);
    }
PHP;

        $casts = $this->inferCasts();
        $castsAssertions = '';
        if ($casts) {
            foreach ($casts as $k => $v) {
                $castsAssertions .= <<<PHP

                public function test_cast_for_{$k}_is_{$v}()
                {
                    \$model = new {$modelShort}();
                    \$this->assertArrayHasKey('{$k}', \$model->getCasts());
                    \$this->assertSame('{$v}', \$model->getCasts()['{$k}']);
                }
PHP;
            }
        }

        $fillableTest = <<<PHP
            public function test_fillable_contains_expected_attributes()
            {
                \$model = new {$modelShort}();
                \$expected = {$fillableExport};
                \$actual = \$model->getFillable();
                sort(\$expected);
                sort(\$actual);
                \$this->assertSame(\$expected, \$actual);
            }
PHP;

        $uses = "use Tests\\TestCase;\nuse Illuminate\\Foundation\\Testing\\RefreshDatabase;\nuse {$modelClass};";

        $full = <<<PHP
        <?php
            
            namespace {$testClassNamespace};
            
            {$uses}
            
            class {$className} extends TestCase
            {
                use RefreshDatabase;
                
                {$fillableTest}
                {$relationAssertions}
                {$castsAssertions}
                {$factoryTest}
            }
        PHP;

        return $full;
    }

    public function generate(): void
    {
        $content = $this->buildTestClassString();
        $this->fileGenerator->createFile($this->getPath(), $content);
    }
}
