<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Form\ImportCsvType;
use App\Service\UploadService;
use App\Traits\RabbitmqTrait;
use League\Csv\Reader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * @IsGranted("ROLE_USER")
 **/
class ReferenceController extends AbstractController
{
    use RabbitmqTrait;

    /**
     * Тестовый метод (контроллер), сохранение файла, отображение списка загруженных файлов
     */
    #[Route('/reference', name: 'reference')]
    public function index(Request $request, SluggerInterface $slugger, UploadService $uploadService): Response
    {
        $em = $this->getDoctrine()->getManager();
        $fileDir = $this->getParameter('file_directory');
        $data = $em->getRepository(Reference::class)->findAll();
        $form = $this->createForm(ImportCsvType::class, new Reference(),
            ['reference' => true, 'data_class' => Reference::class]);
        $form->handleRequest($request);

        /** TODO Методы для работы с RabbitMQ (тест) */
        //$this->consumeMessage();
        //$this->receiveMessage();

        if ($form->isSubmitted() && $form->isValid() && $form->get('file')->getData()) {
            /** Получение исходных данных */
            $formSubmit = $uploadService->formSubmit($form, $em);
            /** Переименование, сохранение файла */
            $uploadService->saveFile($formSubmit['file'], $formSubmit['uniqId'],
                $fileDir, $slugger, $em, $formSubmit['reference']);
            /** Скачивание CSV файла */
            $reader = Reader::createFromPath($formSubmit['reference']->getFilepath());
            $reader->output($formSubmit['reference']->getUniqId() . ',' . $formSubmit['reference']->getFilename());
            die;
        }
        return $this->render('reference/index.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
