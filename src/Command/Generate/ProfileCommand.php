<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ProfileCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Generator\ProfileGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;

/**
 * Class ProfileCommand
 *
 * @package Drupal\Console\Command\Generate
 */

class ProfileCommand extends Command
{
    use ConfirmationTrait;
    use CommandTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ProfileGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * ProfileCommand constructor.
     *
     * @param Manager          $extensionManager
     * @param ProfileGenerator $generator
     * @param StringConverter  $stringConverter
     * @param Validator        $validator
     * @param $appRoot
     */
    public function __construct(
        Manager $extensionManager,
        ProfileGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        $appRoot
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:profile')
            ->setDescription($this->trans('commands.generate.profile.description'))
            ->setHelp($this->trans('commands.generate.profile.help'))
            ->addOption(
                'profile',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.profile')
            )
            ->addOption(
                'machine-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.machine-name')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.description')
            )
            ->addOption(
                'core',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.core')
            )
            ->addOption(
                'dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.dependencies'),
                ''
            )
            ->addOption(
                'themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.themes'),
                ''
            )
            ->addOption(
                'distribution',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.distribution')
            )->setAliases(['gpr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $profile = $this->validator->validateModuleName($input->getOption('profile'));
        $machine_name = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $dependencies = $this->validator->validateExtensions($input->getOption('dependencies'), 'module', $io);
        $themes = $this->validator->validateExtensions($input->getOption('themes'), 'theme', $io);
        $distribution = $input->getOption('distribution');
        $profile_path = $this->appRoot . '/profiles';

        $this->generator->generate(
            $profile,
            $machine_name,
            $profile_path,
            $description,
            $core,
            $dependencies,
            $themes,
            $distribution
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //$stringUtils = $this->getStringHelper();
        $validators = $this->validator;

        try {
            // A profile is technically also a module, so we can use the same
            // validator to check the name.
            $profile = $input->getOption('profile') ? $validators->validateModuleName($input->getOption('profile')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return 1;
        }

        if (!$profile) {
            $profile = $io->ask(
                $this->trans('commands.generate.profile.questions.profile'),
                '',
                function ($profile) use ($validators) {
                    return $validators->validateModuleName($profile);
                }
            );
            $input->setOption('profile', $profile);
        }

        try {
            $machine_name = $input->getOption('machine-name') ? $validators->validateModuleName($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return 1;
        }

        if (!$machine_name) {
            $machine_name = $io->ask(
                $this->trans('commands.generate.profile.questions.machine-name'),
                $this->stringConverter->createMachineName($profile),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machine_name);
        }

        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.profile.questions.description'),
                $this->trans('commands.generate.profile.suggestions.my-useful-profile')
            );
            $input->setOption('description', $description);
        }

        $core = $input->getOption('core');
        if (!$core) {
            $core = $io->ask(
                $this->trans('commands.generate.profile.questions.core'),
                '8.x'
            );
            $input->setOption('core', $core);
        }

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            if ($io->confirm(
                $this->trans('commands.generate.profile.questions.dependencies'),
                true
            )
            ) {
                $dependencies = $io->ask(
                    $this->trans('commands.generate.profile.options.dependencies'),
                    ''
                );
            }
            $input->setOption('dependencies', $dependencies);
        }

        $distribution = $input->getOption('distribution');
        if (!$distribution) {
            if ($io->confirm(
                $this->trans('commands.generate.profile.questions.distribution'),
                false
            )
            ) {
                $distribution = $io->ask(
                    $this->trans('commands.generate.profile.options.distribution'),
                    $this->trans('commands.generate.profile.suggestions.my-kick-ass-distribution')
                );
                $input->setOption('distribution', $distribution);
            }
        }
    }

    /**
     * @return ProfileGenerator
     */
    protected function createGenerator()
    {
        return new ProfileGenerator();
    }
}
