<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 09/09/14
 * Time: 15:15
 */

namespace Phase\Book;


use Doctrine\DBAL\Connection;
use Phase\Adze\Application;
use Silex\Provider\DoctrineServiceProvider;
use SimpleUser\User;
use SimpleUser\UserServiceProvider;

class BookTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $dbFile;

    /**
     * @var Connection
     */
    protected $dbConnection;

    protected $application; // Todo: mock this for improved separation of tests

    protected function setUp()
    {
        $dbFileSource = dirname(dirname(__DIR__)) . '/resources/blogtest.sqlite';
        $dbFile = $dbFileSource . '.tmp';

        $copied = copy($dbFileSource, $dbFile);

        if (!$copied) {
            throw new \Exception("Failed to create working copy of blogtest.sqlite");
        }

        $this->dbFile = $dbFile;

        $dbParams = [
            'driver' => 'pdo_sqlite',
            'path' => $dbFile
        ];

        $this->application = new Application(); //FIXME mock this!
        $this->application['db.options'] = $dbParams;
        $this->application['security.voters'] = function () {
            return [];
        }; // required by UserManager; set none
        $this->application->register(new DoctrineServiceProvider());
        $this->application->register(new UserServiceProvider());


        $this->application->boot();

        $this->dbConnection = $this->application->getDatabaseConnection();

        $tables = [];
        $moduleBaseDir = dirname(dirname(dirname(__DIR__)));
        $schemaFiles = [
            $moduleBaseDir . '/schemas/tables.php',
            $moduleBaseDir . '/vendor/parsingphase/adze/schemas/tables.php',
        ];

        foreach ($schemaFiles as $schemaFile) {
            $tables = array_merge($tables, require($schemaFile));
        }

        $schemaManager = $this->dbConnection->getSchemaManager();

        foreach ($tables as $table => $spec) {
            if ($schemaManager->tablesExist([$table])) {
                $schemaManager->dropTable($table);
            }
            $schemaManager->createTable($spec);
        }

        //FIXME too closely tied to user implementation, clean up
        $user = [
            'id' => 1,
            'name' => 'bob',
            'email' => 'bob@example.com',
            'password' => 'nonesuch',
            'salt' => 'dummy',
            'roles' => 'ROLE_USER',
            'time_created' => time()
        ];
        $this->dbConnection->insert('users', $user);

        parent::setUp();
    }

    public function testEnvironment()
    {
        $this->assertFileExists($this->dbFile);
        $this->assertTrue(is_file($this->dbFile), "Sqlite source must be regular file");

        $this->assertTrue($this->dbConnection instanceof Connection);

        $schemaManager = $this->dbConnection->getSchemaManager();

        $tablesPresent = $schemaManager->listTableNames();

        $this->assertTrue(is_array($tablesPresent) && count($tablesPresent), 'Must get some tables');

        $requiredTables = ['blog_post'];

        foreach ($requiredTables as $table) {
            $this->assertTrue(in_array($table, $tablesPresent), "Table '$table' is required");
        }
    }

    public function testStoreChapter()
    {
        $blog = new Book($this->dbConnection, $this->application);
        $this->assertTrue($blog instanceof Book);

        $blogPost = new Chapter();

        $this->assertTrue($blogPost instanceof Chapter);
        $blogPost->setSubject('Test blog post');
        $blogPost->setBody('Post body');
        //        $blogPost->setCreatorId(1);

        $user = new User('user@example.org'); // todo mock this?
        $user->setId(1); // for test purpose
        $blogPost->setCreator($user);

        $this->assertFalse((bool)$blogPost->getId());

        $blog->savePost($blogPost);
        $this->assertTrue((bool)$blogPost->getId());
    }

    public function testFetchChapter()
    {
        $rawPost = [
            'subject' => 'Fetch Me',
            'body' => 'Fascinating Content',
            'time' => date('Y-m-d h:i:s'),
            'security' => Chapter::SECURITY_PUBLIC,
            'creatorId' => 1
        ];

        $this->dbConnection->insert('blog_post', $rawPost);

        $sql = "SELECT MIN(id) FROM blog_post";
        $presentPostId = $this->dbConnection->fetchColumn($sql);

        $this->assertTrue((bool)$presentPostId);

        $blog = new Book($this->dbConnection, $this->application);

        $newPost = $blog->fetchPostById($presentPostId);

        $this->assertTrue($newPost instanceof Chapter);

        $rawPost = [
            'subject' => 'Fetch Me First',
            'body' => 'Earlier Fascinating Content',
            'time' => date('Y-m-d h:i:s', time() - 3600),
            'security' => Chapter::SECURITY_PUBLIC,
            'creatorId' => 1
        ];

        $this->dbConnection->insert('blog_post', $rawPost);

        $multiPosts = $blog->fetchRecentPosts();
        $this->assertTrue(is_array($multiPosts));
        $this->assertSame(2, count($multiPosts));
        $this->assertTrue($multiPosts[0]->getTime() > $multiPosts[1]->getTime());
    }

    public function testWithTimeAndSecurity()
    {
        $rawPosts = [
            [
                'subject' => 'Old post',
                'body' => 'Earlier Fascinating Content',
                'time' => date('Y-m-d H:i:s', time() - 3600),
                'security' => Chapter::SECURITY_PUBLIC,
                'creatorId' => 1
            ],
            [
                'subject' => 'Private post',
                'body' => 'Earlier Fascinating Content',
                'time' => date('Y-m-d H:i:s', time() - 3000),
                'security' => Chapter::SECURITY_PRIVATE,
                'creatorId' => 1
            ],
            [
                'subject' => 'Another Private post',
                'body' => 'Earlier Fascinating Content',
                'time' => date('Y-m-d H:i:s', time() - 2000),
                'security' => Chapter::SECURITY_PRIVATE,
                'creatorId' => 1
            ],
            [
                'subject' => 'Future post',
                'body' => 'Earlier Fascinating Content',
                'time' => date('Y-m-d H:i:s', time() + 3600),
                'security' => Chapter::SECURITY_PUBLIC,
                'creatorId' => 1
            ]
        ];

        foreach ($rawPosts as $rawPost) {
            $this->dbConnection->insert('blog_post', $rawPost);
        }

        $blog = new Book($this->dbConnection, $this->application);

        $allRecentPosts = $blog->fetchRecentPosts();
        $this->assertEquals(4, count($allRecentPosts), 'Expecting 4 recent posts');

        $publicRecentPosts = $blog->fetchRecentPosts(5, true);
        $this->assertEquals(2, count($publicRecentPosts), 'Expecting 2 recent public posts');

        $pastRecentPosts = $blog->fetchRecentPosts(5, false, true);
        $this->assertEquals(3, count($pastRecentPosts), 'Expecting 3 previous recent posts');

        $allPosts = $blog->fetchAllPostsNoBody();
        $this->assertEquals(4, count($allPosts), 'Expecting 4 recent posts in archive');

        $publicPosts = $blog->fetchAllPostsNoBody(true);
        $this->assertEquals(2, count($publicPosts), 'Expecting 2 public posts in archive');

        $pastPosts = $blog->fetchAllPostsNoBody(false, true);
        $this->assertEquals(3, count($pastPosts), 'Expecting 3 past posts in archive');
    }
}
