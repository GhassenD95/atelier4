<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookController extends AbstractController
{
    #[Route('/books', name: 'app_book')]
    public function index(EntityManagerInterface $em): Response
    {

        $books = $em->getRepository(Book::class)->findAll();
        $nbr_published = 0;

        foreach ($books as $book){
            $nbr_published = $book->isEnabled() ? $nbr_published + 1 : $nbr_published + 0;
        }
        $nbr_unpublished = count($books) - $nbr_published;

        return $this->render('book/index.html.twig', [
            'books' =>  $books,
            'nbr_published'=> $nbr_published,
            'nbr_unpublished'=>$nbr_unpublished
        ]);
    }

    #[Route('/book/create', name: "app_book_create")]
    public function create(EntityManagerInterface $entityManager): Response
    {
        $authors = $entityManager->getRepository(Author::class)->findAll();

        return $this->render("book/create.html.twig", [
            'authors' => $authors
        ]);
    }

    #[Route('/book/store', name: "app_book_store")]
    public function store(EntityManagerInterface $em, Request $request): RedirectResponse
    {
        //get request data
        $ref = $request->request->get('Ref');
        $title = $request->request->get("title");
        $dateString = $request->request->get('date');
        $category = $request->request->get('category');
        $author_id = $request->request->get('author');

        // Check if the book already exists
        $book_exist = $em->getRepository(Book::class)->findOneBy(['Ref' => $ref]);

        //book exists, no need to store
        if ($book_exist) {
            $this->addFlash('error', "Book exists.");
            return $this->redirectToRoute("app_book_create");
        }

        // Check for empty fields
        if (empty($ref) || empty($title) || empty($dateString) || empty($category) || empty($author_id)) {
            $this->addFlash('error', "Fill all fields.");
            return $this->redirectToRoute("app_book_create");
        }


        // Find the author
        $author = $em->getRepository(Author::class)->find($author_id);
        if (!$author) {
            $this->addFlash('error', "Author for book not found.");
            return $this->redirectToRoute("app_book_create");
        }

        // Create and set up the book
        $book = new Book();
        $book->setRef($ref)
            ->setAuthor($author)
            ->setCategory($category)
            ->setTitle($title)
            ->setPublicationDate(\DateTime::createFromFormat('Y-m-d', $dateString))
            ->setEnabled(true);

        // Increment author's number of books
        $number_of_books = $author->getNbBooks();
        $author->setNbBooks($number_of_books + 1);

        // Persist the book entity
        $em->persist($book);
        $em->flush();

        return $this->redirectToRoute("app_book");
    }

    #[Route('/book/edit/{id}', name: "app_book_edit")]
    public function edit(EntityManagerInterface $em, $id): Response
    {
        $book = $em->getRepository(Book::class)->find($id);
        $authors = $em->getRepository(Author::class)->findAll();
        if(!$book){
            $this->addFlash("error", "Cannot edit, book not found.");
            return $this->redirectToRoute("app_book");

        }
        return $this->render("book/edit.html.twig", [
            'book' => $book,
            'authors' => $authors
        ]);
    }

    #[Route('/book/update/{id}', name: "app_book_update", methods: ['PUT', 'POST'])]
    public function update($id, EntityManagerInterface $em, Request $request): RedirectResponse
    {

        $book = $em->getRepository(Book::class)->find($id);
        $books = $em->getRepository(Book::class)->findAll();

        if (!$book){
            $this->addFlash("error", "cannot update.book does not exist.");
            return $this->redirectToRoute("app_book");
        }

        $ref = $request->request->get('Ref');
        $title = $request->request->get("title");
        $dateString = $request->request->get('date');
        $category = $request->request->get('category');
        $author_id = $request->request->get('author');


        if (empty($ref) || empty($title) || empty($dateString) || empty($category) || empty($author_id)) {
            $this->addFlash('error', "Fill all fields.");
            return $this->redirectToRoute("app_book_edit", ['id' => $id]);
        }

        $author = $em->getRepository(Author::class)->find($author_id);

        //if ref exists already, cannot edit, ref should be unique
        foreach ($books as $b) {
            if($b->getId() != $book->getId()){
                if($b->getRef() == $ref){
                    $this->addFlash("error", "cannot edit, ref already exists.");
                    return $this->redirectToRoute("app_book_edit", ['id' => $id]);
                }
            }
        }


        $book->setTitle($title)
            ->setRef($ref)
            ->setCategory($category)
            ->setEnabled(true)
            ->setPublicationDate(\DateTime::createFromFormat('Y-m-d', $dateString))
            ->setAuthor($author);


        $em->persist($book);
        $em->flush();


        return $this->redirectToRoute("app_book");

    }


    #[Route('/book/destroy/{id}', name: "app_book_destroy", methods: ['DELETE', 'POST'])]
    public function destroy($id, EntityManagerInterface $em): RedirectResponse
    {
        // Find the book by ID
        $book = $em->getRepository(Book::class)->find($id);

        // Check if the book exists
        if (!$book) {
            $this->addFlash("error", "Book not found.");
            return $this->redirectToRoute("app_book");
        }

        // Decrement the author's book count
        $author = $book->getAuthor();
        if ($author) {
            $nb_books = $author->getNbBooks();
            $author->setNbBooks(max(0, $nb_books - 1)); // Prevent negative count

            // Remove the author if they have no books left
            if ($author->getNbBooks() === 0) {
                $em->remove($author);
                $em->flush();
            }
        }

        // Remove the book
        $em->remove($book);
        $em->flush();

        return $this->redirectToRoute("app_book");
    }


    public function show($id, EntityManagerInterface $em){
        $book = $em->getRepository(Book::class)->find($id);

        if(!$book){
            $this->addFlash("error", "book not found");
            return $this->redirectToRoute("app_book");
        }

        return $this->render();

    }

}
