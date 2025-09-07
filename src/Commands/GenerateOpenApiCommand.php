<?php

namespace Pjadanowski\OpenApiGenerator\Commands;

use Illuminate\Console\Command;
use Pjadanowski\OpenApiGenerator\RouteBasedOpenApiGenerator;
use cebe\openapi\Writer;

class GenerateOpenApiCommand extends Command
{
    protected $signature = 'openapi:generate 
                            {--output=public/openapi.json : Output file path}
                            {--title=API Documentation : API title}
                            {--api-version=1.0.0 : API version}
                            {--description= : API description}
                            {--format=json : Output format (json|yaml)}
                            {--routes=api/* : Route patterns to include}';

    protected $description = 'Generate OpenAPI documentation from Laravel routes and controllers';

    public function handle(RouteBasedOpenApiGenerator $generator): int
    {
        $this->info('🔍 Analyzing Laravel routes...');

        $this->info('📝 Generating OpenAPI documentation...');

        $openApi = $generator->generateFromRoutes([
            'title' => $this->option('title'),
            'version' => $this->option('api-version'),
            'description' => $this->option('description'),
        ]);

        $outputPath = $this->option('output');
        $format = $this->option('format');

        if ($format === 'yaml') {
            $content = Writer::writeToYaml($openApi);
        } else {
            $content = Writer::writeToJson($openApi);
        }

        if (!file_put_contents(base_path($outputPath), $content)) {
            $this->error("Failed to write to {$outputPath}");
            return self::FAILURE;
        }

        $this->info("✅ OpenAPI documentation generated: {$outputPath}");

        // Show some statistics
        $routes = collect($openApi->paths)->count();
        $schemas = collect($openApi->components->schemas)->count();

        $this->line("📊 Statistics:");
        $this->line("  - Routes processed: {$routes}");
        $this->line("  - Schemas generated: {$schemas}");

        return self::SUCCESS;
    }
}
