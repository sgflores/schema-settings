<?php

namespace SgFlores\SchemaSetting\Console;

use Illuminate\Console\Command;
use SgFlores\SchemaSetting\Facades\Settings;

class SetCommand extends Command
{
    protected $signature = 'schema-settings:set 
                            {key : The setting key}
                            {value : The value to set}
                            {--scope=global : The scope (e.g., global)}
                            {--json : Parse value as JSON}';

    protected $description = 'Set a setting value';

    public function handle(): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');
        $scope = $this->option('scope');

        try {
            // Parse value if JSON option is set
            if ($this->option('json')) {
                $value = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('Invalid JSON: '.json_last_error_msg());

                    return self::FAILURE;
                }
            } else {
                // Auto-detect boolean and null values
                $value = $this->parseValue($value);
            }

            // For global scope
            if ($scope === 'global') {
                Settings::set($key, $value);
            } else {
                $this->error('Model-scoped setting not supported in CLI. Use global scope or set programmatically.');

                return self::FAILURE;
            }

            $this->info("Setting '{$key}' has been updated successfully.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function parseValue(string $value): mixed
    {
        // Check for boolean values
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        // Check for null
        if (strtolower($value) === 'null') {
            return null;
        }

        // Check for numeric
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }
}
