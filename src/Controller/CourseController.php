<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/course')]
final class CourseController extends AbstractController
{   
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();
        $rol = "ROLE_USER";
        if (in_array("ROLE_TEACHER",$user->getRoles())) {
            $rol = "ROLE_TEACHER";
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->notByUser($user),
            'rol' => $rol,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/mine', name: 'app_course_mine', methods: ['GET'])]
    public function myCourses(CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        if (in_array('ROLE_USER', $roles)) {
            $rol = "ROLE_USER";
            if (in_array("ROLE_TEACHER", $roles)) {
                $rol = "ROLE_TEACHER";
            }
            return $this->render('course/mine.html.twig', [
                'courses' => $courseRepository->filterByUser($user),
                'rol' => $rol,
            ]);
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[isGranted('ROLE_TEACHER')]
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $course -> addUser($this->getUser());
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_mine', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        return $this->render('course/show.html.twig', [
            'course' => $course,
        ]);
    }

    #[isGranted('ROLE_TEACHER')]
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_mine', [], Response::HTTP_SEE_OTHER);
    }

    #[isGranted('ROLE_USER')]
    #[Route('/add_student/{id}<\d+>', name:'app_course_add_student', methods: ['GET', 'POST'])]
    public function addStudent(Request $request, Course $course, EntityManagerInterface $entityManager, CourseRepository $courseRepository): Response
    {
        $currentUser = $this->getUser();
        $course->addUser($currentUser);
        $entityManager->persist($course);
        $entityManager->flush();

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
