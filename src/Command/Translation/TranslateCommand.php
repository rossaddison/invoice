<?php

declare(strict_types=1);

namespace App\Command\Translation;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * e.g  yii translator/translate i.active af-ZA
 * Result: aktief
 */

final class TranslateCommand extends Command
{
    protected static string $defaultName = 'translator/translate';
    protected static string $defaultDescription = 'Translates a message';

    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure()
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Message that will be translated.');
        $this->addArgument('locale', InputArgument::OPTIONAL, 'Translation locale.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var mixed $input->getArgument('message')
         * @var string $message
         */
        $message = $input->getArgument('message');
        /**
         * @var mixed $input->getArgument('locale')
         * @var string $locale
         */
        $locale = $input->getArgument('locale');

        $output->writeln($this->translator->translate($message, [], null, $locale));

        return 0;
    }
}
