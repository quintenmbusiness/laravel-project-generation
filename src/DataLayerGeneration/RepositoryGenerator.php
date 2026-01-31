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
        $this->addImport(Collection::class);
        $this->addImport(LengthAwarePaginator::class);
    }

    protected function buildRepositoryMethods(string $modelClass): void
    {
        $pk = $this->table->primaryKey->name ?? 'id';
        $pkType = 'string|int';

        $this->buildMethod(
            'all',
            body: 'return ' . $modelClass . '::get($columns);',
            returnType: 'Collection',
            arguments: "array \$columns = ['*']"
        );

        $this->buildMethod(
            'paginate',
            body: 'return ' . $modelClass . '::paginate($perPage, $columns);',
            returnType: 'LengthAwarePaginator',
            arguments: "int \$perPage = 15, array \$columns = ['*']"
        );

        $this->buildMethod(
            'find',
            body: "return {$modelClass}::find(\${$pk}, \$columns);",
            returnType: "?{$modelClass}",
            arguments: "$pkType \${$pk}, array \$columns = ['*']"
        );

        $this->buildMethod(
            'findOrFail',
            body: "return {$modelClass}::findOrFail(\${$pk}, \$columns);",
            returnType: $modelClass,
            arguments: "$pkType \${$pk}, array \$columns = ['*']"
        );

        $this->buildMethod(
            'findBy',
            body: "return {$modelClass}::where(\$criteria)->first(\$columns);",
            returnType: "?{$modelClass}",
            arguments: "array \$criteria, array \$columns = ['*']"
        );

        $this->buildMethod(
            'create',
            body: "return {$modelClass}::create(\$data);",
            returnType: $modelClass,
            arguments: 'array $data'
        );

        $this->buildMethod(
            'update',
            body: "\$record = \$this->find(\${$pk});
if (!\$record) return null;
\$record->fill(\$data);
\$record->save();
return \$record;",
            returnType: "?{$modelClass}",
            arguments: "$pkType \${$pk}, array \$data"
        );

        $this->buildMethod(
            'updateWhere',
            body: "return {$modelClass}::where(\$criteria)->update(\$data);",
            returnType: 'int',
            arguments: 'array $criteria, array $data'
        );

        $this->buildMethod(
            'delete',
            body: "\$record = \$this->find(\${$pk});
if (!\$record) return false;
return (bool) \$record->delete();",
            returnType: 'bool',
            arguments: "$pkType \${$pk}"
        );

        $this->buildMethod(
            'deleteWhere',
            body: "return {$modelClass}::where(\$criteria)->delete();",
            returnType: 'int',
            arguments: 'array $criteria'
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
