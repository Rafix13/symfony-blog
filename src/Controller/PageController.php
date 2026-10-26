<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageFormType;

use App\Entity\Contact;
use App\Form\ContactFormType;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Category;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;


final class PageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        $repository = $doctrine->getRepository(Category::class);

        $categories = $repository->findAll();

        return $this->render('page/index.html.twig', ['categories' => $categories]);
    }


    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('about.html.twig', []);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(ManagerRegistry $doctrine, Request $request): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactFormType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contacto = $form->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($contacto);
            $entityManager->flush();
            return $this->redirectToRoute('thankyou', []);
        }
        return $this->render('contact.html.twig', array(
            'form' => $form->createView()
        ));
    }

    #[Route('/thankyou', name: 'thankyou')]
    public function thankyou(): Response
    {
        return $this->render('thankyou.html.twig', []);
    }

}
