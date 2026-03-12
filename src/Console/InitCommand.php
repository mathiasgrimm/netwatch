<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected static $defaultName = 'netwatch:init';

    protected function configure(): void
    {
        $this
            ->setName('netwatch:init')
            ->setDescription('Generate a netwatch.php config file in the current directory')
            ->addOption('laravel', 'l', InputOption::VALUE_NONE, 'Generate a Laravel-aware config that reads from your app config')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing netwatch.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPath = getcwd() . '/netwatch.php';
        $laravel = $input->getOption('laravel') || $this->detectLaravel();
        $force = $input->getOption('force');

        if (file_exists($targetPath) && !$force) {
            $output->writeln('<error>netwatch.php already exists. Use --force to overwrite.</error>');
            return Command::FAILURE;
        }

        $content = $laravel ? $this->laravelTemplate() : $this->standaloneTemplate();

        file_put_contents($targetPath, $content);

        $type = $laravel ? 'Laravel' : 'standalone';
        $output->writeln("<info>Created netwatch.php ({$type} config)</info>");

        return Command::SUCCESS;
    }

    private function detectLaravel(): bool
    {
        return file_exists(getcwd() . '/bootstrap/app.php')
            && file_exists(getcwd() . '/artisan');
    }

    private function standaloneTemplate(): string
    {
        return <<<'PHP'
<?php

use Mathiasgrimm\Netwatch\Probe\PhpRedisProbe;
use Mathiasgrimm\Netwatch\Probe\PdoProbe;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;
use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

return [
    'iterations' => 10,

    'probes' => [
        'redis' => [
            'probe' => new PhpRedisProbe('tcp://127.0.0.1:6379'),
        ],
        'mysql' => [
            'probe' => new PdoProbe('mysql:host=127.0.0.1;port=3306', 'root', ''),
        ],
        'pgsql' => [
            'probe' => new PdoProbe('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'postgres', ''),
        ],
        'laravel' => [
            'probe' => new HttpProbe('https://laravel.com'),
        ],
        'cloudflare' => [
            'probe' => new TcpPingProbe('1.1.1.1', 443),
        ],
        'google-dns' => [
            'probe' => new TcpPingProbe('8.8.8.8', 443),
        ],
    ],
];
PHP;
    }

    private function laravelTemplate(): string
    {
        return <<<'PHP'
<?php

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Mathiasgrimm\Netwatch\Probe\PhpRedisProbe;
use Mathiasgrimm\Netwatch\Probe\PdoProbe;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;
use Mathiasgrimm\Netwatch\Probe\S3Probe;
use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

$probes = [];

// Database — reads from config/database.php
$db = config('database.connections.' . config('database.default'));

if ($db) {
    $driver = $db['driver']; // mysql, pgsql, sqlite
    $dsn = match ($driver) {
        'pgsql' => "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}",
        'sqlite' => "sqlite:{$db['database']}",
        default => "{$driver}:host={$db['host']};port={$db['port']};dbname={$db['database']}",
    };

    $probes['database'] = [
        'probe' => new PdoProbe($dsn, $db['username'] ?? null, $db['password'] ?? null),
    ];
}

// Redis — reads from config/database.php
$redis = config('database.redis.default');

if ($redis) {
    $redisHost = $redis['host'] ?? '127.0.0.1';
    $redisPort = $redis['port'] ?? 6379;

    $probes['redis'] = [
        'probe' => new PhpRedisProbe(
            address: "{$redisHost}:{$redisPort}",
            username: $redis['username'] ?? null,
            password: $redis['password'] ?? null,
        ),
    ];
}

// S3 — reads from config/filesystems.php
$s3 = config('filesystems.disks.s3');

if ($s3 && ($s3['key'] ?? null)) {
    $probes['s3'] = [
        'probe' => new S3Probe(
            bucket: $s3['bucket'],
            region: $s3['region'] ?? 'us-east-1',
            key: $s3['key'],
            secret: $s3['secret'],
            endpoint: $s3['endpoint'] ?? null,
        ),
    ];
}

// App URL — reads from config/app.php
$appUrl = config('app.url');

if ($appUrl && $appUrl !== 'http://localhost') {
    $probes['app'] = [
        'probe' => new HttpProbe($appUrl),
    ];
}

return [
    'iterations' => (int) config('netwatch.iterations', 10),
    'probes' => $probes,
];
PHP;
    }
}
