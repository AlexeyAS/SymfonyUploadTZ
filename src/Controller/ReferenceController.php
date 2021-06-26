<?php

namespace App\Controller;

use App\Entity\Reference;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 **/
class ReferenceController extends AbstractController
{
    #[Route('/', name: 'reference')]
    public function index(): Response
    {

        $reference = $this->getDoctrine()->getRepository(Reference::class)->findAll();
        return $this->render('reference/index.html.twig', ['data' => $reference]);
    }

    #[Route('/reference/import', name: 'referenceImport')]
    public function import(): Response
    {
        $reference = $this->getDoctrine()->getRepository(Reference::class)->findAll();
        return $this->render('reference/index.html.twig', ['data' => $reference]);
    }
}
