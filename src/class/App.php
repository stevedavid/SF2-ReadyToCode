<?php
require 'Prompt.php';
require 'VagrantFileGenerator.php';
require 'Downloader.php';
require 'SkeletonInstall.php';

require_once '../vendor/symfony/process/Process.php';

use Symfony\Component\Process\Process;

/**
 * Class App
 */
class App
{
    /**
     * Run the main application
     */
    public function run()
    {
        // Retrieving debug mode if needed
        $debugOpt = getopt(null, ['no-interaction::']);
        if (array_key_exists('no-interaction', $debugOpt)) {
            $debug = $debugOpt['no-interaction'];
        }
        // Intanciate Prompt class with debug mode if needed
        $prompt = new Prompt(isset($debug) ? $debug : null);

        // Prompt questions
        if(!$prompt->run()) {
            echo Prompt::ERROR_PROMPT . "\n";
            return;
        }

        // Create root dir if not exists
        if($this->createDir($prompt->getRootDir())) {
            echo '... root dir just created' . "\n";
        } else {
            echo '... root dir already exists or insufficient permissions' . "\n";
        }

        // Download Symfony by Majora
        $downloader = new Downloader();
        $downloader->initialize($prompt->getRootDir());

        // Create Vagrantfile
        $this->createTemplate($prompt);

        // Launch install vagrant in project
        // $process = new Process(sprintf(
        //     "cd %s && make vm-install-project WEBROOT=%s",
        //     $prompt->getRootDir(),
        //     $prompt->getRootDir()
        // ));
        // $process->setTimeout(3600);
        // $process->run(function ($type, $buffer) {
        //     if (Process::ERR === $type) {
        //         echo 'ERR > '.$buffer;
        //     } else {
        //         echo 'OUT > '.$buffer;
        //     }
        // });
        // if (!$process->isSuccessful()) {
        //     throw new ProcessFailedException($process);
        // }

        // print $process->getOutput();

        // Install all skeletons
        $skeletonInstall = (new SkeletonInstall(
            $prompt->getSkeletons(),
            sprintf(
                "%s/%s",
                $prompt->getRootDir(),
                "skeletons"
            )
        ))->run();
    }

    /**
     * Create new directory if it doesn't exist
     *
     * @param $dir
     * @return bool
     */
    private function createDir($dir)
    {
        echo '... checking if root dir exists prior creation' . "\n";
        return !is_dir($dir) && @mkdir($dir, 0777);
    }

    /**
     * Create the template for the Vagrantfile
     *
     * @param Prompt $prompt
     */
    private function createTemplate(Prompt $prompt)
    {
        $vagrantGenerator = new VagrantFileGenerator();

        $twigEnvironment = $vagrantGenerator->loadEnvironment();

        $vagrantGenerator->loadAndWriteTemplate($twigEnvironment, [
            'ip' => $prompt->getIpVagrant(),
            'rootDir' => $prompt->getRootDir(),
            'projectName' => $prompt->getProjectName(),
        ]);
    }
}
