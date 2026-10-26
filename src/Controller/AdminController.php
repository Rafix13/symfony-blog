<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageFormType;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Category;
use App\Form\CategoryFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;



#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{

    #[Route('/admin/images', name: 'app_images')]
    public function images(
        ManagerRegistry $doctrine,
        Request $request,
        SluggerInterface $slugger,
        string $imagesDirectory,
        string $portfolioDirectory
    ): Response {
        $repositorio = $doctrine->getRepository(Image::class);
        $images = $repositorio->findAll();

        $image = new Image();
        $form = $this->createForm(ImageFormType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile !== null) {
                $originalFilename = pathinfo(
                    $uploadedFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' .
                    ($uploadedFile->guessExtension() ?? 'bin');

                try {
                    $uploadedFile->move($imagesDirectory, $newFilename);

                    if (!is_dir($portfolioDirectory)) {
                        mkdir($portfolioDirectory, 0755, true);
                    }

                    if (!copy(
                        $imagesDirectory . '/' . $newFilename,
                        $portfolioDirectory . '/' . $newFilename
                    )) {
                        throw new \RuntimeException(
                            'No se pudo copiar la imagen al portfolio.'
                        );
                    }

                    $image->setFile($newFilename);

                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($image);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_images');
                } catch (\Exception $e) {
                    $this->addFlash(
                        'error',
                        'No se pudo guardar la imagen: ' . $e->getMessage()
                    );
                }
            }
        }

        return $this->render('admin/images.html.twig', [
            'form' => $form->createView(),
            'images' => $images,
        ]);
    }


    #[Route('/admin/categories', name: 'app_categories')]
    public function categories(ManagerRegistry $doctrine, Request $request): Response
    {
        $repositorio = $doctrine->getRepository(Category::class);
        $categories = $repositorio->findAll();  

        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);        
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();    
            $entityManager = $doctrine->getManager();    
            $entityManager->persist($category);
            $entityManager->flush();
            // redirigimos a la misma ruta para que se vea la nueva categoría añadida 
            // y no se repita el envío del formulario al recargar la página
            return $this->redirectToRoute('app_categories');
        }
       
        return $this->render('admin/categories.html.twig', array(
            'form' => $form->createView(),
            'categories' => $categories   
        ));
    }

}
