<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\Tests;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class ModelUnitTestsGenerator extends ClassGeneratorTemplate
{
    public function getClassType(): ClassType
    {
        return ClassType::MODEL_TEST;
    }

    public function getPath(): string
    {
        $dir = base_path('tests/Unit/Models');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir . DIRECTORY_SEPARATOR . $this->getClassName() . '.php';
    }

    protected function addImport(string $fqcn): void
    {
        $fqcn = trim($fqcn, '\\');
        if (!$this->imports->contains($fqcn)) {
            $this->imports->add($fqcn);
        }
    }

    protected function addModelImport(string $class): void
    {
        $this->addImport(ClassType::MODEL->namespace($this->table->name) . '\\' . $class);
    }

    protected function buildHeaderImports(string $modelClass): void
    {
        $this->addImport('Tests\\TestCase');
        $this->addImport('Illuminate\\Foundation\\Testing\\RefreshDatabase');
        $this->addModelImport($modelClass);
    }

    protected function inferFillable(): array
    {
        return $this->table->columns
            ->pluck('name')
            ->reject(fn($n) => in_array($n, ['id', 'created_at', 'updated_at', 'deleted_at']))
            ->values()
            ->toArray();
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

    protected function relationAssertionClass(ModelRelationshipType $type): ?string
    {
        return match ($type) {
            ModelRelationshipType::BELONGS_TO => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ModelRelationshipType::HAS_MANY => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ModelRelationshipType::HAS_ONE => 'Illuminate\\Database\\Eloquent\\Relations\\HasOne',
            default => null
        };
    }

    protected function buildTestMethods(string $modelClass): void
    {
        $fillable = var_export($this->inferFillable(), true);

        $this->buildMethod(
            'test_fillable_contains_expected_attributes',
            '$model = new ' . $modelClass . '();
$expected = ' . $fillable . ';
$actual = $model->getFillable();
sort($expected);
sort($actual);
$this->assertSame($expected, $actual);',
            'void'
        );

        foreach ($this->table->relations as $relation) {
            $assertClass = $this->relationAssertionClass($relation->type);

            if (!$assertClass) {
                continue;
            }

            $this->addImport($assertClass);

            $this->buildMethod(
                'test_relation_' . $relation->relationName . '_exists_and_returns_relation',
                '$model = new ' . $modelClass . '();
$this->assertTrue(method_exists($model, \'' . $relation->relationName . '\'));
$relation = $model->' . $relation->relationName . '();
$this->assertInstanceOf(' . class_basename($assertClass) . '::class, $relation);',
                'void'
            );
        }

        foreach ($this->inferCasts() as $field => $cast) {
            $this->buildMethod(
                'test_cast_for_' . $field . '_is_' . $cast,
                '$model = new ' . $modelClass . '();
$this->assertArrayHasKey(\'' . $field . '\', $model->getCasts());
$this->assertSame(\'' . $cast . '\', $model->getCasts()[\'' . $field . '\']);',
                'void'
            );
        }

        $this->buildMethod(
            'test_factory_can_make_instance',
            'if (!method_exists(' . $modelClass . '::class, \'factory\')) {
    $this->assertTrue(true);
    return;
}
$instance = ' . $modelClass . '::factory()->make();
$this->assertInstanceOf(' . $modelClass . '::class, $instance);',
            'void'
        );
    }

    public function generate(): void
    {
        $modelClass = Str::studly(Str::singular($this->table->name));

        $this->buildHeaderImports($modelClass);

        $this->buildTestMethods($modelClass);

        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }
}
