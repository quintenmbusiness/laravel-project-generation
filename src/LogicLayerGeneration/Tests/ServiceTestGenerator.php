<?php

namespace quintenmbusiness\LaravelProjectGeneration\LogicLayerGeneration\Tests;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class ServiceTestGenerator extends ClassGeneratorTemplate
{
    public function getPath(): string
    {
        $dir = base_path('tests/Unit/Services');
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

    protected function addServiceImport(string $class): void
    {
        $this->addImport(ClassType::SERVICE->namespace($this->table->name) . '\\' . $class);
    }

    protected function buildHeaderImports(string $modelClass, string $repositoryClass, string $serviceClass): void
    {
        $this->addImport('Tests\\TestCase');
        $this->addModelImport($modelClass);
        $this->addRepositoryImport($repositoryClass);
        $this->addServiceImport($serviceClass);
    }

    protected function buildTestMethods(string $modelClass, string $repositoryClass, string $serviceClass): void
    {
        $this->buildMethod(
            'test_service_resolves',
            '$service = app(' . $serviceClass . '::class);
$this->assertNotNull($service);',
            'void'
        );

        $this->buildMethod(
            'test_service_create_and_get',
            '$service = app(' . $serviceClass . '::class);
$model = ' . $modelClass . '::factory()->create();
$result = $service->getOrFail($model->id);
$this->assertNotNull($result);',
            'void'
        );

        $this->buildMethod(
            'test_service_update_and_delete',
            '$service = app(' . $serviceClass . '::class);
$model = ' . $modelClass . '::factory()->create();
$updated = $service->update($model->id, []);
$this->assertNotNull($updated);
$deleted = $service->delete($model->id);
$this->assertTrue($deleted);',
            'void'
        );
    }

    public function generate(): void
    {
        $base = Str::studly(Str::singular($this->table->name));
        $modelClass = $base;
        $repositoryClass = $base;
        $serviceClass = $base . 'Service';

        $this->buildHeaderImports($modelClass, $repositoryClass, $serviceClass);

        $this->buildTestMethods($modelClass, $repositoryClass, $serviceClass);

        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }

    public function getClassType(): ClassType
    {
        return ClassType::SERVICE_TEST;
    }
}
