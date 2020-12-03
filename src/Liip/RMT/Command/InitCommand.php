<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Command;

use Exception;
use Phar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Liip\RMT\Information\InformationRequest;
use Liip\RMT\Information\InformationCollector;

/**
 * Create config settings file and rmt executable
 */
class InitCommand extends BaseCommand
{
    /** @var InformationCollector $informationCollector  */
    protected $informationCollector;
    protected $executablePath;
    protected $commandPath;
    protected $configPath;

    protected function buildPaths($configPath = null)
    {
        $projectDir = $this->getApplication()->getProjectRootDir();
        $this->executablePath = $projectDir.'/RMT';
        $this->configPath = $configPath ?? $projectDir.'/.rmt.yml';
        $this->commandPath = realpath(__DIR__.'/../../../../command.php');

        // If possible try to generate a relative link for the command if RMT is installed inside the project
        if (strpos($this->commandPath, $projectDir) === 0) {
            $this->commandPath = substr($this->commandPath, strlen($projectDir) + 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('init');
        $this->setDescription('Setup a new project configuration in the current directory');
        $this->setHelp('The <comment>init</comment> interactive task can be used to setup a new project');

        // Add an option to force re-creation of the config file
        $this->getDefinition()->addOption(new InputOption('force', null, InputOption::VALUE_NONE, 'Force update of the config file'));

        // Create an information collector and configure the different information request
        $this->informationCollector = new InformationCollector();
        $this->informationCollector->registerRequests([
            new InformationRequest('configonly', [
                'description' => 'if you want to skip creation of the RMT convenience script',
                'type' => 'yes-no',
                'command_argument' => true,
                'interactive' => true,
                'default' => 'n',
            ]),
            new InformationRequest('vcs', [
                'description' => 'The VCS system to use',
                'type' => 'choice',
                'choices' => ['git', 'hg', 'none'],
                'choices_shortcuts' => ['g' => 'git', 'h' => 'hg', 'n' => 'none'],
                'default' => 'none',
            ]),
            new InformationRequest('generator', [
                'description' => 'The generator to use for version incrementing',
                'type' => 'choice',
                'choices' => ['semantic-versioning', 'basic-increment'],
                'choices_shortcuts' => ['s' => 'semantic-versioning', 'b' => 'basic-increment'],
            ]),
            new InformationRequest('persister', [
                'description' => 'The strategy to use to persist the current version value',
                'type' => 'choice',
                'choices' => ['vcs-tag', 'changelog'],
                'choices_shortcuts' => ['t' => 'vcs-tag', 'c' => 'changelog'],
                'command_argument' => true,
                'interactive' => true,
            ]),
        ]);
        foreach ($this->informationCollector->getCommandOptions() as $option) {
            $this->getDefinition()->addOption($option);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->informationCollector->handleCommandInput($input);
        $this->getOutput()->writeBigTitle('Welcome to Release Management Tool initialization');
        $this->getOutput()->writeEmptyLine();

        // Security check for the config
        $configPath = $this->getApplication()->getConfigFilePath();
        if ($configPath !== null && file_exists($configPath) && $input->getOption('force') !== true) {
            throw new Exception("A config file already exist ($configPath), if you want to regenerate it, use the --force option");
        }

        // Guessing elements path
        $this->buildPaths($configPath);

        // disable the creation of the conveniance script when within a phar
        if (extension_loaded('phar') && Phar::running()) {
            $this->informationCollector->setValueFor('configonly', 'y');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        // Fill up questions
        if ($this->informationCollector->hasMissingInformation()) {
            foreach ($this->informationCollector->getInteractiveQuestions() as $name => $question) {
                $answer = $this->getOutput()->askQuestion($question, null, $this->input);
                $this->informationCollector->setValueFor($name, $answer);
                $this->getOutput()->writeEmptyLine();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->informationCollector->getValueFor('configonly') === 'n') {
            // Create the executable task inside the project home
            $this->getOutput()->writeln("Creation of the new executable <info>{$this->executablePath}</info>");
            file_put_contents(
                $this->executablePath,
                "#!/usr/bin/env php\n".
                "<?php\n".
                "define('RMT_ROOT_DIR', __DIR__);\n".
                "require '{$this->commandPath}';\n"
            );
            chmod('RMT', 0755);
        }

        // Create the config file from a template
        $this->getOutput()->writeln("Creation of the config file <info>{$this->configPath}</info>");
        $template = $this->informationCollector->getValueFor('vcs') === 'none' ?
            __DIR__.'/../Config/templates/no-vcs-config.yml.tmpl' :
            __DIR__.'/../Config/templates/default-vcs-config.yml.tmpl'
        ;
        $config = file_get_contents($template);
        $generator = $this->informationCollector->getValueFor('generator');
        foreach ([
            'generator' => $generator === 'semantic-versioning' ?
                'semantic # More complex versionning (semantic)' : 'simple  # Same simple versionning',
            'vcs' => $this->informationCollector->getValueFor('vcs'),
            'persister' => $this->informationCollector->getValueFor('persister'),
            'changelog-format' => $generator === 'semantic-versioning' ? 'semantic' : 'simple',
         ] as $key => $value) {
            $config = str_replace("%%$key%%", $value, $config);
        }
        file_put_contents($this->configPath, $config);

        // Confirmation
        $this->getOutput()->writeBigTitle('Success, you can start using RMT by calling "RMT release"');
        $this->getOutput()->writeEmptyLine();

        return 0;
    }

    public function getConfigData()
    {
        $config = [];

        $vcs = $this->informationCollector->getValueFor('vcs');
        if ($vcs !== 'none') {
            $config['vcs'] = $vcs;
        }

        $config['version-persister'] = $this->informationCollector->getValueFor('persister');

        return $config;
    }
}
