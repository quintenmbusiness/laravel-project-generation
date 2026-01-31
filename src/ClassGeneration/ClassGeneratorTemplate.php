<?php

namespace quintenmbusiness\LaravelProjectGeneration\ClassGeneration;

use Illuminate\Support\Collection;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;
use quintenmbusiness\LaravelProjectGeneration\Tools\ClassType;
use quintenmbusiness\LaravelProjectGeneration\Tools\FileGenerator;

abstract class ClassGeneratorTemplate
{
    public Collection $methods;
    public Collection $properties;
    public Collection $imports;
    public Collection $uses;
    public FileGenerator $fileGenerator;

    public function __construct(public TableDTO $table, public array $classesToGenerate) {
        $this->methods = new Collection();
        $this->properties = new Collection();
        $this->imports = new Collection();
        $this->uses = new Collection();
        $this->fileGenerator = new FileGenerator();
    }

    public abstract function getClassType(): ClassType;
    public abstract function getPath(): string;
    public function getNamespace(): string
    {
        return $this->getClassType()->namespace($this->table->name);
    }

    public function getClassName(): string
    {
        return $this->getClassType()->basename($this->table->name);
    }

    public function getClassExtends(): ?string
    {
        return $this->getClassType()->extendsBasename();
    }

    public function getClassExtendsImports(): ?string
    {
        return $this->getClassType()->extendsImport();
    }

    public function writeClass(): string
    {
        $this->addNeededUses();

        return implode("\n", array_filter([
            '<?php',
            '',
            'namespace ' . trim($this->getNamespace(), '\\') . ';',
            $this->writeImports(),
            $this->writeClassDeclaration(),
            $this->writeUses(),
            $this->writeProperties(),
            $this->writeMethods(),
            '}',
        ]));
    }

    public function addNeededUses(): void
    {
        foreach ($this->getClassType()->uses() as $use) {
            $this->addUse($use);
        }
    }

    public function addUse(string $class): void
    {
        $this->imports->add($class);
        $this->uses->add($class);
    }

    protected function writeImports(): string
    {
        $allImports = $this->imports->merge($this->uses)->unique();
        if ($allImports->isEmpty()) return '';
        return implode("\n", $allImports->map(fn($i) => 'use ' . $i . ';')->toArray());
    }

    protected function writeUses(): string
    {
        if ($this->uses->isEmpty()) return '';
        return 'use ' . implode(', ', $this->uses->map(fn($u) => basename($u))->toArray()) . ';';
    }

    protected function writeClassDeclaration(): string
    {
        $decl = 'class ' . $this->getClassName();

        if ($this->getClassExtends()) {
            $decl .= ' extends ' . $this->getClassExtends();
        }

        if ($this->getClassExtendsImports()){
            $this->imports->push($this->getClassExtendsImports());
        }

        return $decl . ' {';
    }

    protected function writeProperties(): string
    {
        if ($this->properties->isEmpty()) return '';
        return implode("\n", $this->properties->toArray());
    }

    protected function writeMethods(): string
    {
        if ($this->methods->isEmpty()) return '';
        return implode("\n\n", $this->methods->toArray());
    }

    protected function exportString(string $value): string
    {
        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    public function buildProperty(
        string $name,
        string $type,
        string $visibility = 'public',
        bool $nullable = false,
        ?string $defaultValue = null
    ): void {
        $typePrefix = $nullable ? '?' : '';
        $default = $defaultValue ?? 'null';
        $this->properties->add("$visibility {$typePrefix}{$type} \${$name} = {$default};");
    }

    public function buildMethod(
        string $name,
        string $body = '',
        ?string $returnType = 'void',
        string $arguments = '',
        bool $static = false
    ): void {
        if ($this->methods->contains(fn($m) => str_contains($m, " function $name"))) return;

        $returnTypeDecl = $returnType ? ": $returnType" : '';
        $staticPrefix = $static ? 'static ' : '';
        $methodText = "public {$staticPrefix}function $name($arguments)$returnTypeDecl {\n$body\n}";
        $this->methods->add($methodText);
    }

    public function buildArray(array $array, bool $endsStatement = true): string
    {
        $entries = array_map(fn($entry) => $this->buildArrayEntry($entry), $array);
        $multiLine = count($entries) > 2 ? "\n" . implode(",\n", $entries) . "\n" : implode(", ", $entries);
        return '[' . $multiLine . ']' . ($endsStatement ? ';' : '');
    }

    public function buildArrayEntry(array $entry): string
    {
        $value = $entry['value'] ?? throw new \Exception('No value passed for array entry');

        if (!is_numeric($value) && !is_bool($value) && !str_starts_with($value, '$') && !str_contains($value, '()') && !str_contains($value, 'json_encode')) {
            $value = $this->exportString($value);
        }

        return isset($entry['key']) ? $this->exportString($entry['key']) . ' => ' . $value : $value;
    }
}
