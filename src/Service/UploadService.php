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
    public function formSubmit($form, EntityManagerInterface $em): array
    {
        $uniqId = uniqid();
        $upload = new Upload();
        $reference = new Reference();
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        if ($form->has('uniqId') && $form->get('uniqId')->getData()) {
            $uniqId = $form->get('uniqId')->getData();
        }
        $oldReference = $em->getRepository(Reference::class)->findOneBy(['uniqId' => $uniqId]);
        if ($oldReference) {
            unlink($oldReference->getFilepath());
            $reference = $oldReference;
        }
        return ['uniqId' => $uniqId, 'file' => $file, 'reference' => $reference, 'upload' => $upload];
    }

    public function saveFile(UploadedFile $file, $uniqId, $fileDir, SluggerInterface $slugger,
                             EntityManagerInterface $em, Reference $reference): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $fileName = $safeFilename . '.' . $file->guessExtension();
        $originalFilename .= $file->guessExtension();
        $newFilename = $uniqId . ',' . $fileName;
        try {
            $file->move($fileDir, $newFilename);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        $reference->setFilename($fileName);
        $reference->setUniqId($uniqId);
        $reference->setFilepath($fileDir . '/' . $newFilename);
        $reference->setError($this->getErrorName($originalFilename));

        $em->persist($reference);
        $em->flush();
        return $fileName;
    }

    /**
     * @throws CannotInsertRecord
     */
    public function importCsv(UploadedFile $file, Reference $reference, Upload $upload, EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $array = [];
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $reader = Reader::createFromPath($reference->getFilepath());
        $records = $reader->getRecords();
        foreach ($records as $key => $row) {
            $array[$key] = $row;
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('hash', 'hash');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('error', 'error');
        $rsm->addScalarResult('filepath', 'filepath');
        for ($i = 0; $i < count($array); ++$i) {
            $array[$i][2] = $this->getErrorName($array[$i][1]);
            $slugger->slug($array[$i][1]);
//            $filepath = implode('.', explode(',\', $reference->getFilepath()));
            $query = 'INSERT INTO upload (id, hash, name, error, filepath) ' . 'VALUES (nextval(' . "'upload_id_seq'" . '),' . "'" . $array[$i][0] . "', '" . $array[$i][1] . "', '" . $array[$i][2] . "', '" . $reference->getFilepath() . "') ON CONFLICT " . '("hash")' . "DO UPDATE SET name = '" . $array[$i][1] . "', error = '" . $array[$i][2] . "', filepath = '" . $reference->getFilepath() . "'";
            $em->createNativeQuery($query, $rsm)->getResult();
        }

        /** Получить загруженные значения без повтора по КОД*/
//      $result = $this->getUploaded($hash, $rsm, $em);
//        $result = array_filter($array, function ($val, $key){
//            dd($val);
//        }, ARRAY_FILTER_USE_BOTH);

        /** Получить все значения */
        //$result = $this->getAll($rsm, $em);

        $writer = Writer::createFromFileObject(new SplTempFileObject());
        $writer->insertOne(['КОД', 'НАЗВАНИЕ', 'ОШИБКА']);
        $writer->insertAll(array_slice($array, 1));
        $writer->output($reference->getFilename());
        die;

    }

    public function download(Reference $reference): Response
    {
        $response = new Response(file_get_contents($reference->getFilepath()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $reference->getFilename());
        return $response;
    }

    protected function getErrorName($originalFilename): ?string
    {
        $chars = preg_split('//',
            $originalFilename, -1, PREG_SPLIT_NO_EMPTY);
        $errorsName = preg_grep('/^([а-яА-ЯЁёa-zA-Z0-9-.]+)$/u', $chars, PREG_GREP_INVERT);
        if ($errorsName) {
            $string = count($errorsName) > 1 ?
                'Недопустимые символы "%s" в поле Название' :
                'Недопустимый символ "%s" в поле Название';
            return sprintf($string, implode(', ', array_values($errorsName)));
        }
        return null;
    }

//    /** Получить загруженные значения без повторов по КОД*/
//    private function getUploaded($hash, $rsm, EntityManagerInterface $em){
//        $query = "SELECT hash, name, error FROM upload WHERE hash IN (". $hash . ") GROUP BY hash, name, error";
//        return $em->createNativeQuery($query, $rsm)->getResult()[0];
//    }
    /** Получить все значения из таблицы*/
    private function getAll($rsm, EntityManagerInterface $em)
    {
        $query = "SELECT * FROM upload ";
        return $em->createNativeQuery($query, $rsm)->getResult();
    }

}