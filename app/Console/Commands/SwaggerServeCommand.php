<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SwaggerServeCommand extends Command
{
    protected $signature = 'swagger:serve
                            {--port=8800 : Port untuk Swagger UI server}
                            {--host=127.0.0.1 : Host untuk Swagger UI server}';

    protected $description = 'Generate dokumentasi OpenAPI lalu jalankan Swagger UI di port terpisah';

    public function handle(): int
    {
        $port    = (int) $this->option('port');
        $host    = (string) $this->option('host');
        $docRoot = public_path('swagger');

        if (!is_dir($docRoot)) {
            mkdir($docRoot, 0755, true);
        }

        $this->info('Generating OpenAPI documentation...');

        $exitCode = $this->call('l5-swagger:generate');
        if ($exitCode !== 0) {
            $this->error('Gagal generate dokumentasi Swagger. Cek anotasi @OA di controllers.');
            return self::FAILURE;
        }

        $this->ensureIndexHtml($docRoot);

        $url = "http://{$host}:{$port}";
        $this->newLine();
        $this->line("  <info>Swagger UI</info> → <href={$url}>{$url}</href>");
        $this->line('  Tekan <comment>Ctrl+C</comment> untuk menghentikan server.');
        $this->newLine();

        passthru(sprintf('php -S %s:%d -t "%s"', $host, $port, $docRoot), $serverCode);

        return $serverCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function ensureIndexHtml(string $docRoot): void
    {
        $path = $docRoot . DIRECTORY_SEPARATOR . 'index.html';

        if (file_exists($path)) {
            return;
        }

        file_put_contents($path, $this->buildIndexHtml());
        $this->line('  Dibuat: public/swagger/index.html');
    }

    private function buildIndexHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MinFara AI Bot — API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #fafafa; }
    .topbar { display: none; }
  </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
  SwaggerUIBundle({
    url: 'swagger.json',
    dom_id: '#swagger-ui',
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIBundle.SwaggerUIStandalonePreset,
    ],
    layout: 'BaseLayout',
    deepLinking: true,
    persistAuthorization: true,
    docExpansion: 'list',
    filter: true,
  });
</script>
</body>
</html>
HTML;
    }
}
