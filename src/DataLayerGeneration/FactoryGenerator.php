<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;

class FactoryGenerator extends ClassGeneratorTemplate
{
    public function getClassType(): ClassType
    {
        return ClassType::FACTORY;
    }

    public function getPath(): string
    {
        $dir = base_path('database/Factories');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir . DIRECTORY_SEPARATOR . $this->getClassName() . '.php';
    }

    protected function addImport(string $fqcn): void
    {
        $fqcn = trim($fqcn, '\\');
        if (!$this->imports->contains($fqcn)) $this->imports->add($fqcn);
    }

    protected function addModelImport(string $class): void
    {
        $this->addImport(ClassType::MODEL->namespace($this->table->name) . '\\' . $class);
    }

    protected function buildHeaderImports(): void
    {
        $parentImport = $this->getClassType()->extendsImport();
        if ($parentImport) $this->addImport($parentImport);
        $this->addImport('Illuminate\\Support\\Str');
        $this->addImport('Illuminate\\Support\\Facades\\DB');
    }

    protected function getModelClassName(): string
    {
        return ClassType::MODEL->basename($this->table->name);
    }

    protected function buildModelProperty(): void
    {
        $modelClass = $this->getModelClassName();
        $this->addModelImport($modelClass);
        $this->buildProperty('model', '', 'protected', false, $modelClass . '::class');
    }

    protected function sanitizeVariableName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        if (preg_match('/^[0-9]/', $name)) $name = '_' . $name;
        return $name;
    }

    protected function factoryVariableNameForRelation(RelationshipDTO $relation): string
    {
        $base = $relation->relationName ?? Str::snake(Str::singular($relation->relatedTable ?? 'related'));
        return lcfirst(Str::studly($this->sanitizeVariableName($base)));
    }

    protected function inferFakerExpression(object $col): string
    {
        $name = strtolower($col->name);
        $raw = strtolower($col->rawType ?? '');

        if (str_contains($name, 'email')) return '$this->faker->safeEmail()';
        if (str_contains($name, 'phone') || str_contains($name, 'tel')) return 'preg_replace("/[^0-9]/", "", $this->faker->phoneNumber())';
        if (str_contains($name, 'first_name')) return '$this->faker->firstName()';
        if (str_contains($name, 'last_name')) return '$this->faker->lastName()';
        if (str_contains($name, 'name')) return '$this->faker->name()';
        if (str_contains($name, 'password')) return "bcrypt('password')";
        if ($name === 'remember_token') return 'Str::random(10)';
        if (str_contains($raw, 'json')) return '[]';
        if (str_contains($name, 'description') || str_contains($name, 'body') || str_contains($raw, 'text')) return '$this->faker->text(500)';
        if (str_contains($raw, 'bool')) return '$this->faker->boolean()';
        if (str_contains($raw, 'decimal') || str_contains($raw, 'float') || str_contains($raw, 'double')) return '$this->faker->randomFloat(2, 0, 1000)';
        if (str_contains($raw, 'date') || str_contains($raw, 'time')) return '$this->faker->dateTime()';
        if (str_contains($raw, 'int') || str_ends_with($name, '_id')) return '$this->faker->numberBetween(1, 1000)';
        return '$this->faker->word()';
    }

    protected function buildFactoryPreambleAndMapping(): array
    {
        $preamble = [];
        $mapping = [];
        $declared = [];

        foreach ($this->table->relations as $relation) {
            if ($relation->type !== ModelRelationshipType::BELONGS_TO) continue;

            $relatedClass = $relation->relatedModel ?? ClassType::MODEL->basename($relation->relatedTable);
            $var = $this->factoryVariableNameForRelation($relation);

            if (!isset($declared[$var])) {
                $declared[$var] = $relatedClass;
                $preamble[] = '        $' . $var . ' = ' . $relatedClass . '::factory();';
            }

            $mapping[$relation->foreignKey] = '$' . $var;
            $this->addModelImport($relatedClass);
        }

        foreach ($this->table->columns as $col) {
            if (in_array($col->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;
            if (isset($mapping[$col->name])) continue;
            $mapping[$col->name] = $this->inferFakerExpression($col);
        }

        return [$preamble, $mapping, $declared];
    }

    protected function buildDefinitionMethod(): void
    {
        [$preamble, $mapping, $declared] = $this->buildFactoryPreambleAndMapping();

        $lines = [];
        foreach ($mapping as $key => $value) $lines[] = "            '{$key}' => {$value},";

        $body = '';
        if (!empty($preamble)) $body .= implode("\n", $preamble) . "\n\n";
        $body .= "        return [\n" . implode("\n", $lines) . "\n        ];";

        $this->buildMethod('definition', $body, 'array');
    }

    public function generate(): void
    {
        $this->buildHeaderImports();
        $this->buildModelProperty();
        $this->buildDefinitionMethod();
        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }
}
