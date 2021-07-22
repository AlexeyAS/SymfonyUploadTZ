<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
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
            ->addArgument('rows', InputArgument::OPTIONAL, 'Number of rows', 100000)
            ->addArgument('lenght', InputArgument::OPTIONAL, 'Name lenght', 8)
            ->addOption('tmp', null, InputOption::VALUE_NONE, 'Save file to /tmp');
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $array = [];
        $io = new SymfonyStyle($input, $output);
        $rows = $input->getArgument('rows');
        $lenght = $input->getArgument('lenght');

        if ($rows) $io->note(sprintf('Вы ввели следующее кол-во сток: %s', $rows));
        if ($lenght) $io->note(sprintf('Вы ввели следующую длину файла: %s', $lenght));

        $filename = uniqid('export') . '.csv';
        $writer = Writer::createFromFileObject(new SplTempFileObject());

        $io->progressStart($rows);
        for ($i = 0; $i < $rows; $i++) {
            $array[$i]['hash'] = uniqid();
            $array[$i]['name'] = 'имя' . $i + 1 . '-' . $this->generateName($lenght);
            $io->progressAdvance();
        }

        $writer->insertOne(['КОД', 'НАЗВАНИЕ']);
        $writer->insertAll($array);
        file_put_contents($filename, $writer->toString());

        if ($input->getOption('tmp'))
            $this->filesystem->rename($filename, '/tmp/' . $filename);
        else
            $this->filesystem->rename($filename, reset($this->fileDirectory) . '/' . $filename);

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