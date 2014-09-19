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
        //        $this->application->register(new UserServiceProvider());


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

        //        //FIXME too closely tied to user implementation, clean up
        //        $user = [
        //            'id' => 1,
        //            'name' => 'bob',
        //            'email' => 'bob@example.com',
        //            'password' => 'nonesuch',
        //            'salt' => 'dummy',
        //            'roles' => 'ROLE_USER',
        //            'time_created' => time()
        //        ];
        //        $this->dbConnection->insert('users', $user);

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

        $requiredTables = ['chapter'];

        foreach ($requiredTables as $table) {
            $this->assertTrue(in_array($table, $tablesPresent), "Table '$table' is required");
        }
    }

    public function testStoreChapter()
    {
        $book = new Book($this->dbConnection, $this->application);
        $this->assertTrue($book instanceof Book);

        $chapter = new Chapter();

        $this->assertTrue($chapter instanceof Chapter);
        $chapter->setSubject('Test blog post');
        $chapter->setBody('Post body');
        $chapter->setChapterNumber(1);

        //        $blogPost->setCreatorId(1);

        //        $user = new User('user@example.org'); // todo mock this?
        //        $user->setId(1); // for test purpose
        //        $chapter->setCreator($user);

        $this->assertFalse((bool)$chapter->getId());

        $book->savePost($chapter);
        $this->assertTrue((bool)$chapter->getId());
    }

    public function testFetchChapter()
    {
        $rawPost = [
            'title' => 'Fetch Me',
            'body_text' => 'Fascinating Content',
            'created_at' => date('Y-m-d h:i:s'),
            'updated_at' => date('Y-m-d h:i:s'),
            'is_activated' => 1,
            'chapter_id' => 3
        ];

        $this->dbConnection->insert('chapter', $rawPost);

        $sql = "SELECT MIN(id) FROM chapter";
        $presentPostId = $this->dbConnection->fetchColumn($sql);

        $this->assertTrue((bool)$presentPostId);

        $book = new Book($this->dbConnection, $this->application);

        $newPost = $book->fetchChapterById($presentPostId);

        $this->assertTrue($newPost instanceof Chapter);

        $rawPost = [
            'title' => 'Fetch Me First',
            'body_text' => 'Fascinating Content',
            'created_at' => date('Y-m-d h:i:s'),
            'updated_at' => date('Y-m-d h:i:s'),
            'is_activated' => 1,
            'chapter_id' => 2
        ];

        $this->dbConnection->insert('chapter', $rawPost);

        $multiPosts = $book->fetchChapters();
        $this->assertTrue(is_array($multiPosts));
        $this->assertSame(2, count($multiPosts));
        $this->assertTrue(
            $multiPosts[0]->getChapterNumber() < $multiPosts[1]->getChapterNumber(),
            'Chapters should be returned in order'
        );
    }

    public function testWithTimeAndSecurity()
    {
        $rawPosts = [
            [
                'title' => 'Old post',
                'body_text' => 'Earlier Fascinating Content',
                'created_at' => date('Y-m-d H:i:s', time() - 3600),
                'updated_at' => date('Y-m-d H:i:s', time() - 3600),
                'chapter_id' => 1,
                'is_activated' => 1,
            ],
            [
                'title' => 'Private post',
                'body_text' => 'Earlier Fascinating Content',
                'created_at' => date('Y-m-d H:i:s', time() - 3000),
                'updated_at' => date('Y-m-d H:i:s', time() - 3000),
                'chapter_id' => 2,
                'is_activated' => 0,
            ],
            [
                'title' => 'Another Private post',
                'body_text' => 'Earlier Fascinating Content',
                'created_at' => date('Y-m-d H:i:s', time() - 2000),
                'updated_at' => date('Y-m-d H:i:s', time() - 2000),
                'chapter_id' => 3,
                'is_activated' => 0,
            ],
            [
                'title' => 'Future post',
                'body_text' => 'Earlier Fascinating Content',
                'created_at' => date('Y-m-d H:i:s', time() + 3600),
                'updated_at' => date('Y-m-d H:i:s', time() + 3600),
                'chapter_id' => 4,
                'is_activated' => 1,
            ]
        ];

        foreach ($rawPosts as $rawPost) {
            $this->dbConnection->insert('chapter', $rawPost);
        }

        $blog = new Book($this->dbConnection, $this->application);

        $allRecentPosts = $blog->fetchChapters(false);
        $this->assertEquals(4, count($allRecentPosts), 'Expecting 4 recent posts');

        $publicRecentPosts = $blog->fetchChapters(true);
        $this->assertEquals(2, count($publicRecentPosts), 'Expecting 2 recent public posts');

        $allPosts = $blog->fetchAllChaptersNoBody();
        $this->assertEquals(4, count($allPosts), 'Expecting 4 recent posts in archive');

        $publicPosts = $blog->fetchAllChaptersNoBody(true);
        $this->assertEquals(2, count($publicPosts), 'Expecting 2 public posts in archive');

    }
}
