<?php

namespace quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationThroughDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\ClassGeneratorTemplate;

class ModelGenerator extends ClassGeneratorTemplate
{
    public function getNamespace(): string
    {
        return 'App\Models';
    }

    public function getClassName(): string
    {
        return Str::studly(Str::singular($this->table->name));
    }

    public function getPath(): string
    {
        $dir = base_path('app/Models');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir . DIRECTORY_SEPARATOR . $this->getClassName() . '.php';
    }

    public function getClassExtends(): ?string
    {
        return 'Model';
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
        $this->addImport('App\\Models\\' . $class);
    }

    protected function buildHeaderImports(): void
    {
        $this->addImport('Illuminate\\Database\\Eloquent\\Model');
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

    protected function buildTableProperty(): void
    {
        $this->buildProperty('table', '', 'protected', false, $this->exportString($this->table->name));
    }

    protected function buildTimestampsProperty(): void
    {
        $hasCreated = $this->table->columns->contains(fn($c) => $c->name === 'created_at');
        $hasUpdated = $this->table->columns->contains(fn($c) => $c->name === 'updated_at');
        $this->buildProperty('timestamps', '', 'public', false, ($hasCreated && $hasUpdated) ? 'true' : 'false');
    }

    protected function buildFillableOrGuarded(): void
    {
        $fillable = $this->table->columns
            ->pluck('name')
            ->reject(fn($n) => in_array($n, ['id', 'created_at', 'updated_at', 'deleted_at']))
            ->values()
            ->toArray();

        if ($fillable) {
            $this->buildProperty(
                'fillable',
                '',
                'protected',
                false,
                $this->buildArray(array_map(fn($v) => ['value' => $v], $fillable), false)
            );
        } else {
            $this->buildProperty('guarded', '', 'protected', false, '[]');
        }
    }

    protected function buildCastsProperty(): void
    {
        $casts = $this->inferCasts();
        if (!$casts) return;

        $entries = [];
        foreach ($casts as $k => $v) {
            $entries[] = ['key' => $k, 'value' => $v];
        }

        $this->buildProperty('casts', '', 'protected', false, $this->buildArray($entries, false));
    }

    protected function buildRelationshipMethods(): void
    {
        foreach ($this->table->relations as $relation) {
            $this->buildRelationMethod($relation);
        }
    }

    protected function buildRelationMethod(RelationshipDTO $relation): void
    {
        $relatedClass = $relation->relatedModel
            ?? Str::studly(Str::singular($relation->relatedTable));

        $this->addModelImport($relatedClass);

        $method = $relation->relationName;
        $fk = $relation->foreignKey;
        $lk = $relation->localKey ?? 'id';

        match ($relation->type) {
            ModelRelationshipType::BELONGS_TO => $this->relationMethod(
                'BelongsTo',
                $method,
                "return \$this->belongsTo({$relatedClass}::class, '{$fk}', '{$lk}');"
            ),
            ModelRelationshipType::HAS_MANY => $this->relationMethod(
                'HasMany',
                $method,
                "return \$this->hasMany({$relatedClass}::class, '{$fk}', '{$lk}');"
            ),
            ModelRelationshipType::HAS_ONE => $this->relationMethod(
                'HasOne',
                $method,
                "return \$this->hasOne({$relatedClass}::class, '{$fk}', '{$lk}');"
            ),
            default => null
        };
    }

    protected function buildThroughRelationMethods(): void
    {

        foreach ($this->table->relationsThrough as $through) {
            $this->buildThroughRelationMethod($through);
        }
    }

    protected function buildThroughRelationMethod(RelationThroughDTO $through): void
    {
        $final = Str::studly(Str::singular($through->relatedTable));
        $throughClass = Str::studly(Str::singular($through->throughTable));

        $this->addModelImport($final);
        $this->addModelImport($throughClass);

        $body = match ($through->type) {
            ModelRelationshipType::HAS_MANY_THROUGH =>
            "return \$this->hasManyThrough({$final}::class, {$throughClass}::class, '{$through->firstKey}', '{$through->secondKey}', '{$through->localKey}', '{$through->secondLocalKey}');",
            ModelRelationshipType::HAS_ONE_THROUGH =>
            "return \$this->hasOneThrough({$final}::class, {$throughClass}::class, '{$through->firstKey}', '{$through->secondKey}', '{$through->localKey}', '{$through->secondLocalKey}');",
            default => null
        };

        if ($body) {
            $this->relationMethod(
                $through->type === ModelRelationshipType::HAS_MANY_THROUGH ? 'HasManyThrough' : 'HasOneThrough',
                $through->relationName,
                $body
            );
        }
    }

    protected function relationMethod(string $relationClass, string $name, string $body): void
    {
        $this->addImport("Illuminate\\Database\\Eloquent\\Relations\\{$relationClass}");
        $this->buildMethod($name, $body, $relationClass);
    }

    public function usesFactory(): void
    {
        if(in_array(FactoryGenerator::class,$this->classesToGenerate)) {
            $this->uses->add(HasFactory::class);
        }
    }

    public function generate(): void
    {
        $this->usesFactory();
        $this->buildHeaderImports();
        $this->buildTableProperty();
        $this->buildTimestampsProperty();
        $this->buildFillableOrGuarded();
        $this->buildCastsProperty();
        $this->buildRelationshipMethods();
        $this->buildThroughRelationMethods();
        $this->fileGenerator->createFile($this->getPath(), $this->writeClass());
    }
}
