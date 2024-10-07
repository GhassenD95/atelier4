<?php

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorController extends AbstractController
{
    #[Route('/authors', name: 'app_authors')]
    public function index(EntityManagerInterface $em): Response
    {

        $authors = $em->getRepository(Author::class)->findAll();

        return $this->render('author/index.html.twig', [
            'authors' => $authors,
        ]);
    }


    #[Route('/authors/create', name: "app_author_create")]
    public function create(): Response
    {
        return $this->render("author/create.html.twig");
    }

    #[Route('/authors/store', name: 'app_author_store', methods: ['POST'])]
    public function store(EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $username = $request->request->get("username");
        $email = $request->request->get("email");

        if (empty($username) || empty($email)) {
            $this->addFlash("error", "Invalid inputs");
            return $this->redirectToRoute("app_author_create");
        }

        $author = new Author();
        $author->setUsername($username);
        $author->setEmail($email);
        $author->setNbBooks('0');
        $em->persist($author);
        $em->flush();

        return $this->redirectToRoute("app_authors");
    }

    #[Route('/authors/destroy/{id}', name: "app_author_destroy", methods: ['DELETE', 'POST'])]
    public function destroy($id, EntityManagerInterface $em): RedirectResponse|Response
    {
        $author = $em->getRepository(Author::class)->find($id);

        if (!$author) {
            $this->addFlash("error", "author not found.");
            return $this->redirectToRoute("app_authors");
        }


        $em->remove($author);
        $em->flush();


        return $this->redirectToRoute("app_authors");
    }

    #[Route('/authors/edit/{id}', name: "app_author_edit")]
    public function edit($id, EntityManagerInterface $em): Response
    {

        $author = $em->getRepository(Author::class)->find($id);
        if(!$author){
            $this->addFlash("error", "author not found.");
            return $this->redirectToRoute("app_book");
        }
        return $this->render("author/edit.html.twig", [
                'author' => $author
            ]
        );
    }

    #[Route('/authors/update/{id}', name: "app_author_update", methods: ['PUT', 'POST'])]
    public function update($id, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $author = $em->getRepository(Author::class)->find($id);
        if (!$author) {
            $this->addFlash("error", "no author found to edit. ");
            return $this->redirectToRoute("app_author_update");
        }
        $username = $request->request->get("username");
        $email = $request->request->get("email");

        if (empty($username) || empty($email)) {
            $this->addFlash('error', "invalid inputs.");
            return $this->redirectToRoute("app_author_edit", ['id' => $id]);
        }

        $author->setUsername($username);
        $author->setEmail($email);
        $em->persist($author);
        $em->flush();

        return $this->redirectToRoute("app_authors");
    }

}
