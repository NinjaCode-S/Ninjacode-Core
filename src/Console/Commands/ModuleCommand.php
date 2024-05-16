<?php

namespace Ninjacode\Core\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\form;

class ModuleCommand extends Command
{
    protected $signature = 'ninja:module {action} {name?}';
    protected $description = 'Install and update modules';

    private $ninjamodule = '.ninjamodule';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $folder = basename(ucfirst(basename($name)), '.git');

        switch ($action) {
            case "install":
            case "i":
                $this->installModule($name, $folder);
                break;
            case "update":
            case "u":
                $this->updateModule($name);
                break;
            case "disable":
            case "d":
                $this->disableModule($folder);
                break;
            case "enable":
            case "e":
                $this->enableModule($folder);
                break;
            case "push":
            case "p":
                $this->pushModule($folder);
                break;
            default:
                $this->error("Unknown command: " . $action);
                break;
        }
    }

    private function installModule($name, $folder)
    {
        $this->info('Installing: ' . $name);

        $path = "./Modules/$folder";
        $process = new Process(['git', 'clone', $this->genUrl($name), $path]);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful() && is_dir($path)) {
            \Artisan::call("module:enable $folder");
            $this->info($process->getOutput());
            $this->saveDotNinja($this->genUrl($name), $folder);

            $this->removeGit($path);
            exec('composer dump-autoload');
            Artisan::call('optimize:clear');
        } else {
            $this->error("Failed to install $folder");
        }
    }

    private function updateModule($name)
    {
        if (!$name) {
            $modules = glob(base_path('Modules/*'), GLOB_ONLYDIR);
            foreach ($modules as $module) {
                $this->updateModule(basename($module));
            }
            return;
        }

        $trx = Carbon::now()->format('Y-m-d___H-i');
        $path = "./Modules/$name";
        $t_path = "./Trash/$name/$trx";
        mkdir($t_path, 0755, 1);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Команды для Windows
            $commandMove = 'move /Y "' . $path . '\*" "' . $t_path . '"';
            $commandRemove = 'rd /s /q "' . $path . '"';
        } else {
            // Команды для Unix/Linux и macOS
            $commandMove = 'mv ' . $path . "/* " . $t_path;
            $commandRemove = 'rm -rf ' . $path;
        }

        exec($commandMove);
        exec($commandRemove);

        $process = new Process(['git', 'clone', $this->findModuleUrl($name), $path]);

        $process->setWorkingDirectory(base_path());
        $process->run();

        $this->removeGit($path);
        exec('composer dump-autoload');
        Artisan::call('optimize:clear');
    }

    private function pushModule($name)
    {
        if (!$name) {
            $modules = glob(base_path('Modules/*'), GLOB_ONLYDIR);
            foreach ($modules as $module) {
                $this->updateModule(basename($module));
            }
            return;
        }

        $this->info('Pushing: ' . $name);

        $process = new Process(['git', 'add', '.'], base_path("Modules/$name"));
        $process->run();
        $process = new Process(['git', 'commit', '-m "update module"'], base_path("Modules/$name"));
        $process->run();
        $process = new Process(['git', 'push'], base_path("Modules/$name"));
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Failed to push $name: " . $process->getErrorOutput());
        } else {
            $this->info($process->getOutput());
        }
    }

    private function disableModule($folder)
    {
        \Artisan::call("module:disable $folder");
    }

    private function enableModule($folder)
    {
        \Artisan::call("module:enable $folder");
    }

    private function genUrl($name)
    {
        if (strpos($name, 'https://') === 0) {
            // Это полный URL, вставляем учетные данные
            $schemeAndRest = explode('https://', $name, 2);
            if (count($schemeAndRest) == 2) {
                return "https://{$this->env('MODULES_CREDENTIALS')}@" . $schemeAndRest[1];
            }
        } else {
            // Это путь, строим полный URL
            $baseRepo = rtrim($this->env('MODULES_REPOSITORY'), '/');
            return "https://{$this->env('MODULES_CREDENTIALS')}@{$baseRepo}/{$name}.git";
        }
    }

    private function env($key)
    {
        return env($key, '');
    }

    private function saveDotNinja($gitUrl, $repoName)
    {
        $lineToWrite = $repoName . " " . $gitUrl . PHP_EOL;

        if (file_exists($this->ninjamodule)) {
            $lines = file($this->ninjamodule, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $found = false;

            foreach ($lines as &$line) {
                if (strpos($line, $repoName) === 0) {
                    // Если нашли, обновляем строку
                    $line = $lineToWrite;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $lines[] = $lineToWrite;
            }

            file_put_contents($this->ninjamodule, implode(PHP_EOL, $lines) . PHP_EOL);
        } else {
            file_put_contents($this->ninjamodule, $lineToWrite);
        }
    }

    private function findModuleUrl($moduleName)
    {
        if (file_exists($this->ninjamodule)) {
            $lines = file($this->ninjamodule, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos($line, $moduleName) === 0) {
                    $parts = explode(' ', $line, 2);
                    if (count($parts) === 2) {
                        return $parts[1];
                    }
                }
            }
        }
        return null;
    }

    private function removeGit($modulePath)
    {
        if (!env('MODULES_ENV')) {
            $dir = $modulePath . '/.git';
            exec('rm -rf ' . $dir);
        }
    }

}
