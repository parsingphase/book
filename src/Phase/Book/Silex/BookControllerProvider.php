<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 06/09/14
 * Time: 21:16
 */

namespace Phase\Book\Silex;


use Phase\Adze\Application as AdzeApplication;
use Phase\Book\Book;
use Silex\Application as SilexApplication;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * Silex routing and controller setup for book-related pages
 *
 * @see Silex book chapter 6, ControllerProviders
 * @TODO rename to ....\Silex\BookControllerProvider?
 * @package Phase\Book
 */
class BookControllerProvider implements ControllerProviderInterface
{

    /**
     * Returns routes to connect to the given application.
     *
     * @param SilexApplication $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(SilexApplication $app)
    {
        // Make sure we're using an Adze app, and use that knowledge to set up the required template and resource paths
        // It's possible we should really do this as a ServiceProvider ... ?

        $app = AdzeApplication::assertAdzeApplication($app);
        $controllers = $app->getControllerFactory();

        $moduleBaseDir = dirname(dirname(dirname(dirname(__DIR__))));

        $app->getTwigFilesystemLoader()->addPath(
            $moduleBaseDir . '/templates/book',
            'book'
        );

        $app->getResourceController()->addPathMapping('parsingphase/book', $moduleBaseDir . '/resources');

        $app['book.controller'] = $app->share(
            function (AdzeApplication $app) {
                $dbConnection = $app->getDatabaseConnection();
                $book = new Book($dbConnection, $app);
                $bookController = new BookController($book, $app);
                return $bookController;
            }
        );

        // Now set up routes for the book subsystem

        $controllers->match(
            '/new',
            'book.controller:newChapterAction'
        )->bind('book.newChapter')->method('POST|GET');

        $controllers->match(
            '/part/{uid}/{slug}/edit', // NB UID not chapterId as we need to access non-active and alternate versions
            'book.controller:editChapterAction'
        )->bind('book.editChapter')->assert('uid', '\d+')->method('POST|GET')->secure('ROLE_ADMIN');

        $controllers->get(
            '/part/{chapterId}/{slug}',
            'book.controller:readChapterAction'
        )->bind('book.readChapter')->assert('chapterId', '\d+');

        $controllers->get(
            '/',
            'book.controller:indexAction'
        )->bind('book.index');

        return $controllers;

    }
}
