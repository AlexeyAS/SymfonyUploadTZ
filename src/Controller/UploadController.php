<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Producer\UploadProducer;
use App\Service\UploadService;
use App\Traits\RabbitmqTrait;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use App\Entity\Upload;
use App\Form\ImportCsvType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends AbstractController
{
    use RabbitmqTrait;

    /**
     * @throws \Exception
     */
    #[Route('/upload', name: 'upload')]
    public function index(Request $request, SluggerInterface $slugger, UploadService $uploadService): Response
    {
        $em = $this->getDoctrine()->getManager();
        $fileDir = $this->getParameter('file_directory');
        $data = $em->getRepository(Upload::class)->findBy([],['id'=>'DESC'], 10,0);
        $form = $this->createForm(ImportCsvType::class, new Upload(),
            ['reference' => false, 'data_class' => Upload::class]);
        $form->handleRequest($request);


//        $this->produceMessage();
//        $this->sendMessage();


        if ($form->isSubmitted() && $form->isValid() && $form->get('file')->getData()) {
            $formSubmit = $uploadService->formSubmit($form, $em);
            $uploadService->saveFile($formSubmit['file'], $formSubmit['uniqId'],
                $fileDir, $slugger, $em, $formSubmit['reference']);
            $uploadService->importCsv($formSubmit['file'], $formSubmit['reference'], $formSubmit['upload'], $em, $slugger);
        }

        return $this->render('upload/index.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
