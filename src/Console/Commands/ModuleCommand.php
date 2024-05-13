<?php

namespace Ninjacode\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\confirm;

class ModuleCommand extends Command
{
    protected $signature = 'ninja:module {action} {name?}';
    protected $description = 'Install and update modules';

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
        $process = new Process(['git', 'clone', $this->genUrl($name), "./Modules/$folder"]);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful() && is_dir("./Modules/$folder")) {
            \Artisan::call("module:enable $folder");
            $this->info($process->getOutput());
            $this->saveDotNinja($this->genUrl($name), $folder);

            $this->renameGitDirectory($folder);
            exec('composer dump-autoload');
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

        $this->info('Updating: ' . $name);

        $this->renameGitDirectory($name);
        $process = new Process(['git', 'pull'], base_path("Modules/$name"));
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Failed to update $name: " . $process->getErrorOutput());
        } else {
            $this->info($process->getOutput());
        }
        $this->renameGitDirectory($name);
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

        $this->renameGitDirectory($name);
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
        $this->renameGitDirectory($name);
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
        $filePath = '.ninjamodule';
        $lineToWrite = $repoName . " " . $gitUrl . PHP_EOL;

        // Проверяем, существует ли файл
        if (file_exists($filePath)) {
            // Читаем все строки из файла
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $found = false;

            // Перебираем строки, ищем нужную репозиторию
            foreach ($lines as &$line) {
                if (strpos($line, $repoName) === 0) {
                    // Если нашли, обновляем строку
                    $line = $lineToWrite;
                    $found = true;
                    break;
                }
            }

            // Если репозитория нет в файле, добавляем её
            if (!$found) {
                $lines[] = $lineToWrite;
            }

            // Записываем обновлённые данные обратно в файл
            file_put_contents($filePath, implode(PHP_EOL, $lines) . PHP_EOL);
        } else {
            // Файл не существует, просто пишем строку
            file_put_contents($filePath, $lineToWrite);
        }
    }

    private function renameGitDirectory($modulePath)
    {
        $originalDir = './Modules/' . $modulePath . '/.git';
        $newDirPath = './Modules/' . $modulePath . '/.ninja_git';

        if (file_exists($originalDir)) {
            if (rename($originalDir, $newDirPath)) {
                $this->info("Renamed .git to .ninja_git successfully.");
            } else {
                $this->error("Failed to rename .git to .ninja_git.");
            }
        } elseif (file_exists($newDirPath)) {
            if (rename($newDirPath, $originalDir)) {
                $this->info("Renamed .ninja_git to .git successfully.");
            } else {
                $this->error("Failed to rename .ninja_git to .git.");
            }
        } else {
            $this->error("Directory does not exist.");
        }

    }

}
