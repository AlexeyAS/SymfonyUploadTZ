<?php


namespace App\Service;


use App\Entity\Reference;
use App\Entity\Upload;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use JetBrains\PhpStorm\NoReturn;
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
    private EntityManagerInterface $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /** Получение исходных данных после подтверждения форма */
    public function formSubmit($form): array
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
        $oldReference = $this->em->getRepository(Reference::class)->findOneBy(['uniqId' => $uniqId]);
        if ($oldReference) {
            unlink($oldReference->getFilepath());
            $reference = $oldReference;
        }
        
        return ['uniqId' => $uniqId, 'file' => $file, 'reference' => $reference, 'upload' => $upload];
    }
    
    /** Переименование, сохранение файла */
    public function saveFile(UploadedFile $file, $uniqId, $fileDir, Reference $reference)
    {
        /**
         * Переименование файла, получение строки ошибок
         */
        $safeFilename = $this->getRename($file->getClientOriginalName())['name'] ?: $file->getClientOriginalName();
        /** Перенос файла из папки tmp */
        try {
            $file->move($fileDir, $uniqId . ',' . $safeFilename);
        } catch (FileException $e) {
            throw new FileException("Ошибка при копировании файла из кэша $e");
        }
        
        /** Запись значений в таблицу */
        $reference->setFilename($safeFilename);
        $reference->setUniqId($uniqId);
        $reference->setFilepath($fileDir . '/' . $uniqId . ',' . $safeFilename);
        $reference->setError($this->getRename($file->getClientOriginalName())['error']);
        $this->em->persist($reference);
        $this->em->flush();
    }
    
    /**
     * Импорт записей в БД, экспорт значений в файл CSV
     * @throws CannotInsertRecord
     */
    #[NoReturn] public function importCsv(UploadedFile $file, Reference $reference, Upload $upload)
    {
        /** Чтение из CSV файла, запись массив */
        $array = [];
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
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
        $rsm->addScalarResult('file_id', 'file_id');
        for ($i = 0; $i < count($array); ++$i) {
            $array[$i][2] = $this->getRename($array[$i][1])['error'];
            $array[$i][1] = $this->getRename($array[$i][1])['name'] ?: $array[$i][1];
            $query = 'INSERT INTO upload (id, hash, name, error, file_id) ' . 'VALUES (nextval(' . "'upload_id_seq'" . '),' . "'" . $array[$i][0] . "', '" . $array[$i][1] . "', '" . $array[$i][2] . "', '" . $reference->getId() . "') ON CONFLICT " . '("hash")' . "DO UPDATE SET name = '" . $array[$i][1] . "', error = '" . $array[$i][2] . "', file_id = '" . $reference->getId() . "'";
            $this->em->createNativeQuery($query, $rsm)->getResult();
        }
        
        /**
         * По умолчанию получаем загруженные исходные значения + поле Ошибки
         * Чтобы получить загруженные значения без повтора по полю КОД - раскомментировать строку ниже
         */
//        $array = $this->getUnique($array);
        /** Получить все значения из таблицы */
//        $array = $this->getAll($rsm);
        
        /** Запись в файл csv */
        $writer = Writer::createFromFileObject(new SplTempFileObject());
        $writer->insertOne(['КОД', 'НАЗВАНИЕ', 'ОШИБКА']);
        $writer->insertAll(array_slice($array, 1));
        $writer->output($reference->getUniqId() . ',' . $reference->getFilename());
        die;
    }
    
    /**
     * Переименование файла, содержимого CSV по полю Название
     * Получение ошибки именований (файла, поля Название) и значения
     */
    protected function getRename($originalName): array
    {
        $chars = preg_split('//u', $originalName, -1, PREG_SPLIT_NO_EMPTY);
        $errors = preg_grep('/^([а-яА-ЯЁёa-zA-Z0-9-.]+)$/u', $chars, PREG_GREP_INVERT);
        $result['error'] = $result['name'] = null;
        if ($errors) {
            $safeName = '';
            $keyOld = 0;
            foreach ($errors as $key => $char) {
                if ($keyOld == 0) {
                    $safeName = mb_substr($originalName, $keyOld, $key);
                } else {
                    $safeName .= mb_substr($originalName, $keyOld + 1, $key - $keyOld - 1);
                }
                $keyOld = $key;
            }
            $safeName .= mb_substr($originalName, $keyOld + 1, mb_strlen($originalName) - $keyOld - 1);
            $string = count($errors) > 1 ?
                'Недопустимые символы "%s" в поле Название' :
                'Недопустимый символ "%s" в поле Название';
            $result['error'] = sprintf($string, implode('", "', array_values($errors)));
            $result['name'] = $safeName;
        }
        return $result;
    }
    
    /** Получить все значения из таблицы*/
    private function getAll($rsm)
    {
        $query = "SELECT * FROM upload ";
        return $this->em->createNativeQuery($query, $rsm)->getResult();
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