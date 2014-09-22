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

    public function __construct(Book $blog, Application $app)
    {
        $this->book = $blog;
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

    public function archiveAction()
    {
        $pastOnly = $publicOnly = !$this->app->getSecurityContext()->isGranted('ROLE_ADMIN');

        $posts = $this->book->fetchAllChaptersNoBody($publicOnly, $pastOnly);
        return $this->app->render('@blog/archive.html.twig', ['posts' => $posts]);
    }

    public function newPostAction(Request $request)
    {
        // There may be neater ways of doing this?
        if (!$this->app->getSecurityContext()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException;
        }

        //Forms ref: http://symfony.com/doc/2.5/book/forms.html
        $action = $this->app->url('blog.newPost');

        $blogPost = new Chapter();
        $blogPost->setCreationTime(new \DateTime());
        $creator = $this->app->getCurrentUser();
        $blogPost->setCreator($creator);

        $form = $this->createChapterForm($blogPost, $action);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->book->savePost($blogPost);

            $newBookId = $blogPost->getId();

            // redirect somewhere
            return $this->app->redirect(
                $this->app->path(
                    'blog.post',
                    ['uid' => $newBookId, 'slug' => $blogPost->getSlug()]
                )
            );
        }

        return $this->app->render('@blog/editPost.html.twig', ['blogPostForm' => $form->createView()]);
    }

    public function editPostAction(Request $request, $uid)
    {
        // There may be neater ways of doing this?
        if (!$this->app->getSecurityContext()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException;
        }

        $blogPost = $this->book->fetchChapterById($uid);

        //Forms ref: http://symfony.com/doc/2.5/book/forms.html
        $action = $this->app->url('blog.editPost', ['uid' => $blogPost->getId(), 'slug' => $blogPost->getSlug()]);
        $form = $this->createChapterForm($blogPost, $action);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->book->savePost($blogPost);

            $newBookId = $blogPost->getId();

            // redirect somewhere
            return $this->app->redirect(
                $this->app->path(
                    'blog.post',
                    ['uid' => $newBookId, 'slug' => $blogPost->getSlug()]
                )
            );
        }

        return $this->app->render('@blog/editPost.html.twig', ['blogPostForm' => $form->createView()]);
    }

    /**
     * @param Chapter $blogPost
     * @param $action
     * @return Form
     */
    protected function createChapterForm(Chapter $blogPost, $action)
    {
        //NB: data can be array or target object
        $formBuilder = $this->app->getFormFactory()->createBuilder('form', $blogPost)
            ->add('subject')
            ->add('body', 'textarea')
            ->add('time', 'datetime')
            ->add('save', 'submit')
            ->add(
                'security',
                'choice',
                ['choices' => [Chapter::SECURITY_PUBLIC => 'Public', Chapter::SECURITY_PRIVATE => 'Private']]
            )
            ->setAction($action);
        /* @var FormBuilder $formBuilder Interface definition means PhpStorm chokes there */

        if ($blogPost->getId()) {
            $formBuilder->add('id', 'hidden');
        }

        $form = $formBuilder->getForm();
        return $form;
    }
}
