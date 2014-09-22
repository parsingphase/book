<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 10/09/14
 * Time: 14:23
 */

namespace Phase\Book\Silex;


use Phase\Adze\Application;
use Phase\Book\Book;
use Phase\Book\Chapter;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Silex Controller for blog-related pages
 * @package Phase\Book\Silex
 */
class BookController
{
    /**
     * @var Book
     */
    protected $book;

    /**
     * @var Application
     */
    protected $app;

    public function __construct(Book $book, Application $app)
    {
        $this->book = $book;
        $this->app = $app;
    }

    public function indexAction()
    {
        $chapters = $this->book->fetchChapters();
        return $this->app->render('@book/index.html.twig', ['chapters' => $chapters]);
    }

    public function readChapterAction($chapterId, $slug)
    {
        $chapter = $this->book->fetchChapterById($chapterId);
        $index = $this->book->fetchAllChaptersNoBody();

        if (!($chapter->isActive()
            || $this->app->getSecurityContext()->isGranted('ROLE_ADMIN'))
        ) {
            throw new AccessDeniedHttpException; // FIXME just 404?
        }

        if ($slug !== $chapter->getSlug()) {
            return $this->app->redirect(
                $this->app->path(
                    'book.readChapter',
                    ['chapterId' => $chapterId, 'slug' => $chapter->getSlug()]
                )
            );
        }
        return $this->app->render('@book/chapter.html.twig', ['chapter' => $chapter, 'chapters' => $index]);
    }

    public function newChapterAction(Request $request)
    {
        // There may be neater ways of doing this?
        if (!$this->app->getSecurityContext()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException;
        }

        //Forms ref: http://symfony.com/doc/2.5/book/forms.html
        $action = $this->app->url('book.newChapter');

        $chapter = new Chapter();
        $chapter->setCreationTime(new \DateTime());

        $form = $this->createChapterForm($chapter, $action);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->book->saveChapter($chapter);

            // redirect somewhere
            return $this->app->redirect(
                $this->app->path(
                    'book.readChapter',
                    ['chapterId' => $chapter->getChapterNumber(), 'slug' => $chapter->getSlug()]
                )
            );
        }

        return $this->app->render('@book/editChapter.html.twig', ['chapterForm' => $form->createView()]);
    }

    public function editChapterAction(Request $request, $uid)
    {
        // There may be neater ways of doing this?
        if (!$this->app->getSecurityContext()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException;
        }

        $chapter = $this->book->fetchChapterById($uid);

        //Forms ref: http://symfony.com/doc/2.5/book/forms.html
        $action = $this->app->url('book.editChapter', ['uid' => $chapter->getId(), 'slug' => $chapter->getSlug()]);
        $form = $this->createChapterForm($chapter, $action);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->book->saveChapter($chapter);

            $newBookId = $chapter->getId();

            // redirect somewhere
            return $this->app->redirect(
                $this->app->path(
                    'book.readChapter',
                    ['chapterId' => $chapter->getChapterNumber(), 'slug' => $chapter->getSlug()]
                )
            );
        }

        return $this->app->render('@book/editChapter.html.twig', ['chapterForm' => $form->createView()]);
    }

    /**
     * @param Chapter $chapter
     * @param $action
     * @return Form
     */
    protected function createChapterForm(Chapter $chapter, $action)
    {
        //NB: data can be array or target object
        $formBuilder = $this->app->getFormFactory()->createBuilder('form', $chapter)
            ->add('subject')
            ->add('chapterNumber', 'integer', ['label'=>'Chapter #'])
            ->add('active', 'checkbox', ['value' => 1])
            ->add('body', 'textarea')
            ->add('save', 'submit')
            ->setAction($action);
        /* @var FormBuilder $formBuilder Interface definition means PhpStorm chokes there */

        if ($chapter->getId()) {
            $formBuilder->add('id', 'hidden');
        }

        $form = $formBuilder->getForm();
        return $form;
    }
}
