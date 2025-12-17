<?php

namespace SgFlores\SchemaSetting\Console;

use Illuminate\Console\Command;
use SgFlores\SchemaSetting\Facades\Settings;

class ListCommand extends Command
{
    protected $signature = 'schema-settings:list 
                            {scope? : The scope to list settings for (e.g., global, App\Models\User)}
                            {--groups : Group settings by their group}';

    protected $description = 'List all registered settings and their schemas';

    public function handle(): int
    {
        $scope = $this->argument('scope') ?? 'global';
        $schema = Settings::getSchema($scope);

        if (empty($schema)) {
            $this->error("No settings found for scope: {$scope}");

            return self::FAILURE;
        }

        $this->info("Settings for scope: {$scope}");
        $this->newLine();

        if ($this->option('groups')) {
            $this->listByGroups($schema);
        } else {
            $this->listFlat($schema);
        }

        return self::SUCCESS;
    }

    protected function listFlat(array $schema): void
    {
        $rows = [];

        foreach ($schema as $item) {
            $config = $item->toArray();
            $rows[] = [
                $config['key'],
                $config['type'],
                $this->formatDefault($config['default']),
                $config['group'] ?? '-',
                $config['label'] ?? $config['key'],
                $config['encrypted'] ? 'Yes' : 'No',
                $config['readonly'] ? 'Yes' : 'No',
            ];
        }

        $this->table(
            ['Key', 'Type', 'Default', 'Group', 'Label', 'Encrypted', 'Read-only'],
            $rows
        );
    }

    protected function listByGroups(array $schema): void
    {
        $grouped = [];

        foreach ($schema as $item) {
            $config = $item->toArray();
            $group = $config['group'] ?? 'Ungrouped';
            $grouped[$group][] = $config;
        }

        foreach ($grouped as $group => $items) {
            $this->info("Group: {$group}");

            $rows = [];
            foreach ($items as $config) {
                $rows[] = [
                    $config['key'],
                    $config['type'],
                    $this->formatDefault($config['default']),
                    $config['label'] ?? $config['key'],
                ];
            }

            $this->table(
                ['Key', 'Type', 'Default', 'Label'],
                $rows
            );

            $this->newLine();
        }
    }

    protected function formatDefault(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
