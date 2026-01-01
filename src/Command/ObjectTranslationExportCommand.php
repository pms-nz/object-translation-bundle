<?php

namespace PmsNz\ObjectTranslationBundle\Command;

use PmsNz\ObjectTranslationBundle\ObjectManager;
use PmsNz\ObjectTranslationBundle\ObjectTranslator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\LocaleAwareInterface;

#[AsCommand(
    name: 'object-translation:export',
    description: 'Exports object translations to a CSV.',
)]
class ObjectTranslationExportCommand extends Command
{
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly ObjectTranslator $translator,
        private readonly LocaleAwareInterface $localeAware,
        private string $defaultLocale,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The CSV file to export to.')
        ->addOption(name: 'locale', shortcut: 'l', mode: InputOption::VALUE_OPTIONAL, description: 'The locale to use.', default: $this->translator->getDefaultLocale());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $locale = $input->getOption('locale');
        $fallbacks = array_reverse($this->translator->getFallbacks($locale));

        $locales = array_merge(
            [$this->defaultLocale],
            $fallbacks,
            $this->defaultLocale === $locale ? [] : [$locale],
        );

        $currentLocale = $this->localeAware->getLocale();
        $this->localeAware->setLocale($this->defaultLocale);

        $io->title('Exporting Object Translations to CSV');

        $fp = fopen($file, 'w');

        $header = array_merge(['type', 'id', 'field'], $locales);
        fputcsv($fp, $header, escape: '\\');

        foreach ($io->progressIterate($this->objectManager->allTranslatableObjects()) as $object) {
            $type = $this->objectManager->getTranslatableTypeFor($object);
            $id = $this->objectManager->getIdFor($object);

            foreach ($this->objectManager->translatableValuesFor($object) as $field => $value) {
                $values = array_map(
                    fn (string $locale): string => $locale === $this->defaultLocale
                        ? $value
                        : ($this->translator->getTranslation($type, $id, $locale, $field)->value ?? ''),
                    $locales
                );

                $row = array_merge([$type, $id, $field], $values);
                fputcsv($fp, $row, escape: '\\');
            }
        }
        fclose($fp);

        $io->success(sprintf('Exported to "%s"', $file));

        $this->localeAware->setLocale($currentLocale);

        return Command::SUCCESS;
    }
}
