<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageFormType;

use App\Entity\Post;
use App\Form\PostFormType;

use App\Entity\Contact;
use App\Form\ContactFormType;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Category;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


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

    #[Route('/blog/new', name: 'new_post')]
    #[IsGranted('ROLE_USER')]
    public function newPost(
        ManagerRegistry $doctrine,
        Request $request,
        SluggerInterface $slugger
    ): Response {
        $post = new Post();

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' .
                    $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/images/posts',
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw $e;
                }

                $post->setImage($newFilename);
            }

            $post->setSlug(strtolower($slugger->slug($post->getTitle())));
            $post->setPostUser($this->getUser());
            $post->setNumLikes(0);
            $post->setNumComments(0);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('blog');
        }

        return $this->render('blog/new_post.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
