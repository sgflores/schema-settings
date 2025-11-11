<?php

namespace SgFlores\SchemaSetting\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SgFlores\SchemaSetting\Exceptions\SchemaSettingException;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Facades\Settings;
use SgFlores\SchemaSetting\Http\Requests\SettingsRequest;

/**
 * SettingsController - API Controller for Schema Settings
 *
 * This controller handles HTTP requests for retrieving settings schema with values.
 * It supports both single and multiple settings requests with proper
 * validation, error handling, and logging.
 *
 * API Endpoints:
 * - GET /api/schema-settings?key=site_name (single setting)
 * - GET /api/schema-settings?keys[]=site_name&keys[]=maintenance_mode (multiple settings)
 * - GET /api/schema-settings (all settings)
 *
 * Features:
 * - Request validation using SettingsRequest
 * - Automatic error handling and logging
 * - Support for both single and multiple requests
 * - Clean JSON responses
 */
class SettingsController extends Controller
{
    /**
     * Handles API requests for settings schema with values.
     * Supports single key, multiple keys, or all settings.
     *
     * GET /api/schema-settings?key=site_name
     * GET /api/schema-settings?keys[]=site_name&keys[]=maintenance_mode
     * GET /api/schema-settings
     */
    public function __invoke(SettingsRequest $request): JsonResponse
    {
        // Get validated keys from the request
        $keys = $request->getKeys();

        try {
            // Use getSchemaWithValues method for optimized retrieval
            $data = Settings::getSchemaWithValues($keys);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (SettingNotFoundException $e) {
            // Handle known Settings exceptions
            Log::warning('Settings Not Found: '.$e->getMessage(), [
                'keys' => $keys,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);

        } catch (SchemaSettingException $e) {
            // Handle other Settings exceptions
            Log::warning('Settings Error: '.$e->getMessage(), [
                'keys' => $keys,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);

        } catch (Exception $e) {
            // Handle unexpected errors
            Log::error('Settings Unexpected Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'keys' => $keys,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An internal server error occurred while retrieving settings.',
            ], 500);
        }
    }
}
