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
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing netwatch.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPath = getcwd().'/netwatch.php';
        $force = $input->getOption('force');

        if (file_exists($targetPath) && ! $force) {
            $output->writeln('<error>netwatch.php already exists. Use --force to overwrite.</error>');

            return Command::FAILURE;
        }

        file_put_contents($targetPath, $this->template());

        $output->writeln('<info>Created netwatch.php</info>');

        return Command::SUCCESS;
    }

    private function template(): string
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
}
