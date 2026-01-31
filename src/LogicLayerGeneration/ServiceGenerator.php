<?php

namespace quintenmbusiness\LaravelProjectGeneration\LogicLayerGeneration;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class ServiceGenerator extends ClassGeneratorTemplate
{
    public function getClassType(): ClassType
    {
        return ClassType::SERVICE;
    }

    public function getPath(): string
    {
        $dir = base_path('app/Services');
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

    protected function buildHeaderImports(): void
    {
        $this->addImport('Illuminate\\Database\\Eloquent\\Collection');
        $this->addImport('Illuminate\\Contracts\\Pagination\\LengthAwarePaginator');
    }

    protected function buildRepositoryAccessor(string $repositoryClass): void
    {
        $returnType = $repositoryClass . 'Repository';
        $body = "return app({$returnType}::class);";
        $this->buildMethod('repository', $body, $returnType);
    }

    protected function buildServiceMethods(string $modelClass, string $repositoryClass): void
    {
        $this->buildMethod(
            'getAll',
            'if ($perPage > 0) {
    return $this->repository()->paginate($perPage);
}
return $this->repository()->all();',
            'Collection|LengthAwarePaginator',
            'int $perPage = 0'
        );

        $this->buildMethod(
            'get',
            'return $this->repository()->find($id);',
            "?{$modelClass}",
            'int $id'
        );

        $this->buildMethod(
            'getOrFail',
            'return $this->repository()->findOrFail($id);',
            $modelClass,
            'int $id'
        );

        $this->buildMethod(
            'findBy',
            'return $this->repository()->findBy($criteria);',
            "?{$modelClass}",
            'array $criteria'
        );

        $this->buildMethod(
            'create',
            'return $this->repository()->create($data);',
            $modelClass,
            'array $data'
        );

        $this->buildMethod(
            'update',
            'return $this->repository()->update($id, $data);',
            "?{$modelClass}",
            'int $id, array $data'
        );

        $this->buildMethod(
            'updateWhere',
            'return $this->repository()->updateWhere($criteria, $data);',
            'int',
            'array $criteria, array $data'
        );

        $this->buildMethod(
            'delete',
            'return $this->repository()->delete($id);',
            'bool',
            'int $id'
        );

        $this->buildMethod(
            'deleteWhere',
            'return $this->repository()->deleteWhere($criteria);',
            'int',
            'array $criteria'
        );
    }

    public function generate(): void
    {
        $modelClass = Str::studly(Str::singular($this->table->name));
        $repositoryClass = $modelClass;

        $this->addModelImport($modelClass);
        $this->addRepositoryImport($repositoryClass);

        $this->buildHeaderImports();
        $this->buildRepositoryAccessor($repositoryClass);
        $this->buildServiceMethods($modelClass, $repositoryClass);

        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }
}
