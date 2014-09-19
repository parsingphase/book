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

        /*
         * We have a slight gotcha for IDE code analysis here; Adze defines the Route class that we get dynamically,
         * and Phase\Adze\Route implements the \Silex\Route\SecurityTrait - but the object we call is a
         * \Silex\ControllerCollection that uses __call to pass through to the route, leaving IDEs baffled and unable
         * to recognise that ControllerCollection can handle ->secure()
         *
         * For PHPStorm, see http://stackoverflow.com/a/12583095/113076
         *
         * (Settings -> Inspections -> PHP -> Undefined -> Undefined Method -> Downgrade severity if __magic methods are present)
         *
         * TODO Move this explanation to a bookpost on phase.org and link to that as required
         */

        $controllers->match(
            '/new',
            'book.controller:newChapterAction'
        )->bind('book.newChapter')->method('POST|GET');

//        $controllers->get(
//            '/archive',
//            'book.controller:archiveAction'
//        )->bind('book.archive');

        $controllers->match(
            '/part/{chapterId}/{slug}/edit',
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
