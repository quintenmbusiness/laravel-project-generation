<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\Tests;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class RepositoryTestGenerator extends ClassGeneratorTemplate
{
    public function getPath(): string
    {
        $dir = base_path('tests/Unit/Repositories');
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

    protected function addRepositoryImport(string $class): void
    {
        $this->addImport(ClassType::REPOSITORY->namespace($this->table->name) . '\\' . $class . 'Repository');
    }

    protected function buildHeaderImports(string $modelClass, string $repositoryClass): void
    {
        $this->addImport('Tests\\TestCase');
        $this->addModelImport($modelClass);
        $this->addRepositoryImport($repositoryClass);
    }

    protected function buildTestMethods(string $modelClass, string $repositoryClass): void
    {
        $this->buildMethod(
            'test_create_and_find',
            '$repo = app(' . $repositoryClass . 'Repository::class);
$model = ' . $modelClass . '::factory()->create();
$found = $repo->find($model->id);
$this->assertNotNull($found);',
            'void'
        );

        $this->buildMethod(
            'test_update_and_delete',
            '$repo = app(' . $repositoryClass . 'Repository::class);
$model = ' . $modelClass . '::factory()->create();
$updated = $repo->update($model->id, []);
$this->assertNotNull($updated);
$deleted = $repo->delete($model->id);
$this->assertTrue($deleted);',
            'void'
        );

        $this->buildMethod(
            'test_find_by_and_pagination',
            '$repo = app(' . $repositoryClass . 'Repository::class);
' . $modelClass . '::factory()->create();
$result = $repo->paginate();
$this->assertNotNull($result);',
            'void'
        );
    }

    public function generate(): void
    {
        $base = Str::studly(Str::singular($this->table->name));
        $modelClass = $base;
        $repositoryClass = $base;

        $this->buildHeaderImports($modelClass, $repositoryClass);

        $this->buildTestMethods($modelClass, $repositoryClass);

        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }

    public function getClassType(): ClassType
    {
        return ClassType::REPOSITORY_TEST;
    }
}
