<?php

namespace SgFlores\SchemaSetting\Console;

use Illuminate\Console\Command;
use SgFlores\SchemaSetting\Facades\Settings;

class ClearCacheCommand extends Command
{
    protected $signature = 'schema-settings:clear-cache 
                            {scope? : The scope to clear cache for (optional)}';

    protected $description = 'Clear the settings cache';

    public function handle(): int
    {
        $scope = $this->argument('scope');

        try {
            Settings::clearCache($scope);

            if ($scope) {
                $this->info("Cache cleared for scope: {$scope}");
            } else {
                $this->info('All settings cache has been cleared.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
