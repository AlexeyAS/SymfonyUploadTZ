<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use App\Service;
use SplFileObject;
use SplTempFileObject;
use League\Csv\Writer;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;

/**
 * Class GenerateCsvCommand
 */
#[AsCommand(
    name: 'app:generate',
    description: 'Генерация csv файла из рандомных значений',
)]
class GenerateCsvCommand extends Command
{
    private Filesystem $filesystem;
    private array      $fileDirectory;

    public function __construct(Filesystem $filesystem, $fileDirectory)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->fileDirectory = $fileDirectory;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('row', InputArgument::OPTIONAL, 'Number of rows', 100000)
            //            ->addOption('num', null, InputOption::VALUE_OPTIONAL, 'number', 100)
            ->addArgument('lenght', InputArgument::OPTIONAL, 'Number of rows', 8);
//            ->addOption('lenght', null, InputOption::VALUE_OPTIONAL, 'number', 8);
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $array = [];
        $io = new SymfonyStyle($input, $output);
        $row = $input->getArgument('row');
        $lenght = $input->getArgument('lenght');

        if ($row) {
            $io->note(sprintf('You passed an argument: %s', $row));
        }
        if ($lenght) {
            $io->note(sprintf('You passed an argument: %s', $lenght));
        }

        $filename = uniqid('export') . '.csv';
        $writer = Writer::createFromFileObject(new SplTempFileObject());

        for ($i = 0; $i < $row; $i++) {
            $array[$i]['hash'] = uniqid();
            $array[$i]['name'] = 'имя' . $i + 1 . '-' . $this->generateName($lenght);
            if ((($i + 1) % 1000) == 0) $io->success(sprintf('Сгенерирована строка: %s', $i + 1));
        }
        $writer->insertOne(['КОД', 'НАЗВАНИЕ']);
        $writer->insertAll($array);

        file_put_contents($filename, $writer->toString());
        $this->filesystem->rename($filename, reset($this->fileDirectory) . '/' . $filename);

//        if ($input->getOption('option1'))

        $io->success('Файл сгенерирован.');
        return Command::SUCCESS;
    }

    protected function generateName($lenght): string
    {
        $name = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
            . 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' . '0123456789@%*#!&*~_-.,<>^';
        $numChars = mb_strlen($chars);
        for ($ii = 0; $ii < $lenght; $ii++) $name .= mb_substr($chars, rand(1, $numChars) - 1, 1);
        return $name;
    }
}
//todo доделать настройку опций