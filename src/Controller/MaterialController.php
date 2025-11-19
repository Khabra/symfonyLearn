<?php

namespace App\Controller;

use App\Entity\Material;
use App\Form\MaterialType;
use App\Repository\CourseRepository;
use App\Repository\MaterialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/material')]
final class MaterialController extends AbstractController
{
    #[isGranted('ROLE_USER')]
    #[Route('/{course_id}<\d+>', name: 'app_material_index', methods: ['GET'])]
    public function index(int $course_id, MaterialRepository $materialRepository, CourseRepository $courseRepository): Response
    {
        $course = $courseRepository->findOneById((int)($course_id));
        return $this->render('material/index.html.twig', [
            'materials' => $materialRepository->filterByCourse($course),
            'course_id' => $course_id,
        ]);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route('/new/{course_id}<\d+>', name: 'app_material_new', methods: ['GET', 'POST'])]
    public function new(int $course_id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, CourseRepository $courseRepository): Response
    {
        $material = new Material();
        $course = $courseRepository->findOneById($course_id);
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $material->setCourse($course);
            $uploadedFile = $form->get('fileName')->getData();
            if($uploadedFile) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
                $uploadedFile->move($this->getParameter('materials_directory'), $newFilename);

                $material->setFileName($newFilename);
            }
            $entityManager->persist($material);
            $entityManager->flush();


            return $this->redirectToRoute('app_material_index', ['course_id' => (int)($course_id)], Response::HTTP_SEE_OTHER);
        }

        return $this->render('material/new.html.twig', [
            'material' => $material,
            'form' => $form,
            'course_id' => $course_id,
        ]);
    }

    // #[Route('/{id}', name: 'app_material_show', methods: ['GET'])]
    // public function show(Material $material): Response
    // {
    //     return $this->render('material/show.html.twig', [
    //         'material' => $material,
    //     ]);
    // }

    // #[Route('/{id}/edit', name: 'app_material_edit', methods: ['GET', 'POST'])]
    // public function edit(Request $request, Material $material, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(MaterialType::class, $material);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('material/edit.html.twig', [
    //         'material' => $material,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/delete/{id}', name: 'app_material_delete', methods: ['POST'])]
    public function delete(Request $request, Material $material, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$material->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($material);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
    }
}
