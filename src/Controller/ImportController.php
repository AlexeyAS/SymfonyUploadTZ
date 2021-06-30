<?php

namespace App\Controller;

use App\Entity\Reference;
//use App\Producer\UploadProducer;
use App\Service\UploadService;
//use App\Traits\RabbitmqTrait;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use App\Entity\Upload;
use App\Form\ImportCsvType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Exception;

/**
 * @IsGranted("ROLE_USER")
 **/
class ImportController extends AbstractController
{
//    use RabbitmqTrait;

    /**
     * Импорт-экспорт данных,
     * Запись значений CSV в Upload,
     * Запись данных о файле в Reference,
     * Сохранение файла,
     * Отображение среза загруженных значений
     * @throws Exception
     */
    #[Route('/', name: 'index')]
    public function index(Request $request, UploadService $uploadService): Response
    {
        $em = $this->getDoctrine()->getManager();
        $fileDir = $this->getParameter('file_directory');
        $data['upload'] = $em->getRepository(Upload::class)
            ->findBy([], ['id' => 'DESC'], 20, 0);
        $data['reference'] = $em->getRepository(Reference::class)
            ->findBy([], ['id' => 'DESC'], 20, 0);
        $count = count($data['upload'])>count($data['reference']) ? count($data['upload']) : count($data['reference']);
        $form = $this->createForm(ImportCsvType::class, new Reference());
        $form->handleRequest($request);

        /** TODO Методы для работы с RabbitMQ  (тест) */
        //$this->produceMessage();
        //$this->sendMessage();

        if ($form->isSubmitted() && $form->isValid() && $form->get('file')->getData()) {
            /** Получение исходных данных */
            $formSubmit = $uploadService->formSubmit($form);
            /** Переименование, сохранение файла */
            $uploadService->saveFile($formSubmit['file'], $formSubmit['uniqId'], $fileDir, $formSubmit['reference']);
            /** Импорт записей в БД, экспорт значений в файл CSV,  скачивание файла */
            $uploadService->importCsv($formSubmit['file'], $formSubmit['reference'], $formSubmit['upload']);
        }
        return $this->render('import/index.html.twig', [
            'data' => $data,
            'count' => $count,
            'form' => $form->createView()
        ]);
    }
}