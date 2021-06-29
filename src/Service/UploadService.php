<?php


namespace App\Service;


use App\Entity\Reference;
use App\Entity\Upload;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Doctrine\DBAL\Query\QueryBuilder;
use League\Csv\CannotInsertRecord;
use SplTempFileObject;

class UploadService
{
    /** Получение исходных данных после подтверждения форма */
    public function formSubmit($form, EntityManagerInterface $em): array
    {
        $uniqId = uniqid();
        $upload = new Upload();
        $reference = new Reference();
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        /** Если был введён уникальный ID, ... */
        if ($form->has('uniqId') && $form->get('uniqId')->getData()) {
            $uniqId = $form->get('uniqId')->getData();
        }
        /** ... Соответствующий ему файл будет перезаписан */
        $oldReference = $em->getRepository(Reference::class)->findOneBy(['uniqId' => $uniqId]);
        if ($oldReference) {
            unlink($oldReference->getFilepath());
            $reference = $oldReference;
        }

        return ['uniqId' => $uniqId, 'file' => $file, 'reference' => $reference, 'upload' => $upload];
    }

    /** Переименование, сохранение файла */
    public function saveFile(UploadedFile $file, $uniqId, $fileDir, SluggerInterface $slugger,
                             EntityManagerInterface $em, Reference $reference)
    {
        /**
         * Переименование файла
         * Если указать locale="ru", slugger не будет переименовывать в латиницу
         */
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename, '-', 'ru');
        $fileName = $safeFilename . '.' . $file->guessExtension();
        $originalFilename .= $file->guessExtension();
        $newFilename = $uniqId . ',' . $fileName;

        /** Перенос файла из папки tmp */
        try {
            $file->move($fileDir, $newFilename);
        } catch (FileException $e) {
            throw new FileException("Ошибка при копировании файла из кэша $e");
        }

        /** Запись значений в таблицу */
        $reference->setFilename($fileName);
        $reference->setUniqId($uniqId);
        $reference->setFilepath($fileDir . '/' . $newFilename);
        $reference->setError($this->getErrorName($originalFilename));
        $em->persist($reference);
        $em->flush();
    }

    /**
     * Импорт записей в БД, экспорт значений в файл CSV
     * @throws CannotInsertRecord
     */
    public function importCsv(UploadedFile $file, Reference $reference, Upload $upload, EntityManagerInterface $em, SluggerInterface $slugger)
    {
        /** Чтение из CSV файла, запись массив */
        $array = [];
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $reader = Reader::createFromPath($reference->getFilepath());
        $records = $reader->getRecords();
        foreach ($records as $key => $row) {
            $array[$key] = $row;
        }

        /** Создание SQL запроса мимо ORM */
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('hash', 'hash');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('error', 'error');
        $rsm->addScalarResult('filepath', 'filepath');
        for ($i = 0; $i < count($array); ++$i) {
            $array[$i][2] = $this->getErrorName($array[$i][1]);
            $slugger->slug($array[$i][1], '-', 'ru');
            $query = 'INSERT INTO upload (id, hash, name, error, filepath) ' . 'VALUES (nextval(' . "'upload_id_seq'" . '),' . "'" . $array[$i][0] . "', '" . $array[$i][1] . "', '" . $array[$i][2] . "', '" . $reference->getFilepath() . "') ON CONFLICT " . '("hash")' . "DO UPDATE SET name = '" . $array[$i][1] . "', error = '" . $array[$i][2] . "', filepath = '" . $reference->getFilepath() . "'";
            $em->createNativeQuery($query, $rsm)->getResult();
        }

        /**
         * По умолчанию получаем загруженные исходные значения + поле Ошибки
         * Чтобы получить загруженные значения без повтора по полю КОД - раскомментировать строку ниже
         */
//        $array = $this->getUnique($array);
        /** Получить все значения из таблицы */
//        $array = $this->getAll($rsm, $em);

        /** Запись в файл csv */
        $writer = Writer::createFromFileObject(new SplTempFileObject());
        $writer->insertOne(['КОД', 'НАЗВАНИЕ', 'ОШИБКА']);
        $writer->insertAll(array_slice($array, 1));
        $writer->output($reference->getUniqId() . ',' . $reference->getFilename());
        die;
    }

    /** Получение строки ошибки именований (файла, поля Название)*/
    protected function getErrorName($originalFilename): ?string
    {
        $chars = preg_split('//',
            $originalFilename, -1, PREG_SPLIT_NO_EMPTY);
        $errorsName = preg_grep('/^([а-яА-ЯЁёa-zA-Z0-9-.]+)$/u', $chars, PREG_GREP_INVERT);
        if ($errorsName) {
            $string = count($errorsName) > 1 ?
                'Недопустимые символы "%s" в поле Название' :
                'Недопустимый символ "%s" в поле Название';
            return sprintf($string, implode('", "', array_values($errorsName)));
        }
        return null;
    }

    /** Получить все значения из таблицы*/
    private function getAll($rsm, EntityManagerInterface $em)
    {
        $query = "SELECT * FROM upload ";
        return $em->createNativeQuery($query, $rsm)->getResult();
    }

    /** Получить загруженные значения без повторений по полю КОД */
    private function getUnique($array): array
    {
        $array = array_map("json_decode", array_unique(array_map("json_encode", array_reverse($array))));
        foreach ($array as $key => $value) {
            foreach ($array as $itemKey => $item) {
                if (strstr($item[0], $value[0]) && $itemKey > $key)
                    unset($array[$itemKey]);
            }
        }
        return array_reverse($array);
    }
}