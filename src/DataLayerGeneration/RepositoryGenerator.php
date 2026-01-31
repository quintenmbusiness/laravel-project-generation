<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class RepositoryGenerator extends ClassGeneratorTemplate
{
    public function getClassType(): ClassType
    {
        return ClassType::REPOSITORY;
    }

    public function getPath(): string
    {
        $dir = base_path('app/Repositories');
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

    protected function buildHeaderImports(): void
    {
        $this->addImport('Illuminate\\Database\\Eloquent\\Collection');
        $this->addImport('Illuminate\\Contracts\\Pagination\\LengthAwarePaginator');
    }

    protected function buildRepositoryMethods(string $modelClass): void
    {
        $this->buildMethod(
            'all',
            'return '.$modelClass.'::get($columns);',
            'Collection',
            "array \$columns = ['*']"
        );

        $this->buildMethod(
            'paginate',
            'return '.$modelClass.'::paginate($perPage, $columns);',
            'LengthAwarePaginator',
            "int \$perPage = 15, array \$columns = ['*']"
        );

        $this->buildMethod(
            'find',
            'return '.$modelClass.'::find($id, $columns);',
            "?{$modelClass}",
            "int \$id, array \$columns = ['*']"
        );

        $this->buildMethod(
            'findOrFail',
            'return '.$modelClass.'::findOrFail($id, $columns);',
            $modelClass,
            "int \$id, array \$columns = ['*']"
        );

        $this->buildMethod(
            'findBy',
            'return '.$modelClass.'::where($criteria)->first($columns);',
            "?{$modelClass}",
            "array \$criteria, array \$columns = ['*']"
        );

        $this->buildMethod(
            'create',
            'return '.$modelClass.'::create($data);',
            $modelClass,
            'array $data'
        );

        $this->buildMethod(
            'update',
            ' $record = $this->find($id);
  if (!$record) return null;
  $record->fill($data);
  $record->save();
  return $record;',
            "?{$modelClass}",
            'int $id, array $data'
        );

        $this->buildMethod(
            'updateWhere',
            'return '.$modelClass.'::where($criteria)->update($data);',
            'int',
            'array $criteria, array $data'
        );

        $this->buildMethod(
            'delete',
            ' $record = $this->find($id);
  if (!$record) return false;
  return (bool) $record->delete();',
            'bool',
            'int $id'
        );

        $this->buildMethod(
            'deleteWhere',
            'return '.$modelClass.'::where($criteria)->delete();',
            'int',
            'array $criteria'
        );
    }

    public function generate(): void
    {
        $modelClass = Str::studly(Str::singular($this->table->name));

        $this->addModelImport($modelClass);

        $this->buildHeaderImports();
        $this->buildRepositoryMethods($modelClass);

        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }
}
