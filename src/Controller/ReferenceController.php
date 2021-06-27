<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Form\ImportCsvType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @IsGranted("ROLE_USER")
 **/
class ReferenceController extends AbstractController
{
    #[Route('/', name: 'reference')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository(Reference::class)->findAll();
        $reference = new Reference();
        $oldReference = null;
        $form = $this->createForm(ImportCsvType::class, $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $uniqId = $form->get('uniqId')->getData() ?: uniqid();
            if ($uniqId) {
                $oldReference = $em->getRepository(Reference::class)->findOneBy(['uniqId' => $uniqId]);
                $reference = $oldReference ?: $reference;
            }

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $fileName = $safeFilename . '.' . $file->guessExtension();
                $chars = preg_split('//',
                    $originalFilename . $file->guessExtension(), -1, PREG_SPLIT_NO_EMPTY);
                $errorsName = preg_grep('/^([а-яА-ЯЁёa-zA-Z0-9-.]+)$/u', $chars, PREG_GREP_INVERT);

                $newFilename = $uniqId . ',' . $fileName;
                try {
                    if ($oldReference){
                        unlink($this->getParameter('file_directory') . '/'.
                            $reference->getUniqId().','.$reference->getFilename());
                    }
                    $file->move(
                        $this->getParameter('file_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $reference->setError(null);
                if ($errorsName) {
                    $string = count($errorsName) > 1 ?
                        'Недопустимые символы "%s" в поле Название' :
                        'Недопустимый символ "%s" в поле Название';
                    $reference->setError(sprintf($string, implode(', ', array_values($errorsName))));
                }
                $reference->setFilename($fileName);
                $reference->setUniqId($uniqId);
            }
            $em->persist($reference);
            $em->flush();
            return $this->redirectToRoute('reference');
        }

        return $this->render('reference/index.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }

    #[Route('/reference/import', name: 'referenceImport')]
    public function import(): Response
    {
        $reference = $this->getDoctrine()->getRepository(Reference::class)->findAll();
        return $this->render('reference/index.html.twig', ['data' => $reference]);
    }
}
