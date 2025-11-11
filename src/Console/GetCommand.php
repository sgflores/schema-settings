<?php

namespace SgFlores\SchemaSetting\Console;

use Illuminate\Console\Command;
use SgFlores\SchemaSetting\Facades\Settings;

class GetCommand extends Command
{
    protected $signature = 'schema-settings:get 
                            {key : The setting key to retrieve}
                            {--scope=global : The scope (e.g., global)}
                            {--json : Output as JSON}';

    protected $description = 'Get a setting value';

    public function handle(): int
    {
        $key = $this->argument('key');
        $scope = $this->option('scope');

        try {
            // For global scope
            if ($scope === 'global') {
                $value = Settings::get($key);
            } else {
                $this->error('Model-scoped retrieval not supported in CLI. Use global scope or retrieve programmatically.');

                return self::FAILURE;
            }

            if ($this->option('json')) {
                $this->line(json_encode(['key' => $key, 'value' => $value], JSON_PRETTY_PRINT));
            } else {
                $this->info("Key: {$key}");
                $this->line('Value: '.$this->formatValue($value));
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        return (string) $value;
    }
}
