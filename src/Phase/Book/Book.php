<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 09/09/14
 * Time: 14:10
 */

namespace Phase\Book;


use Doctrine\DBAL\Connection;
use Phase\Adze\Application;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Access class for posts on a blog
 * @package Phase\Book
 */
class Book
{

    /**
     * @var Connection
     */
    protected $dbConnection;

    /**
     * @var Application
     */
    protected $app;

    /**
     * //TODO take in $app[] instead of DB and use user.provider, blog.provider ??? as per \SimpleUser\UserManager::__construct
     * TODO need to add a BookServiceProvider? (User dataobject does not have access to $app, only UserManager does)
     * TODO so need Book(Manager)::hydrateChapter($blogPost) calls $blogPost -> setCreator($userManager->getUser($blogPost->getCreatorId))?
     *
     * Set up access class using given DB connection
     * @param Connection $dbConnection
     * @param Application $app
     */
    public function __construct(Connection $dbConnection, Application $app)
    {
        $this->dbConnection = $dbConnection;
        $this->app = $app;
    }

    /**
     * Store a blog post to the configured DBAL
     *
     * @param Chapter $chapter
     * @return bool
     */
    public function savePost(Chapter $chapter)
    {
        $return = false;
        //        $creator = $blogPost->getCreator();

        //        if (!$creator) {
        //            throw new InvalidParameterException("Invalid user");
        //        }

        if ($chapter->getId()) {
            $updateCount = $this->dbConnection->update(
                'chapters',
                [
                    // Don't allow revision of creation time?
                    'updated_at' => $chapter->getCreationTime()->format('Y-m-d H:i:s'),
                    'title' => $chapter->getSubject(),
                    'body_text' => $chapter->getBody(),
                    'chapter_id' => $chapter->getChapterNumber(),
                    'is_activated' => $chapter->isActive() ? 1 : 0
                    //                    'security' => $blogPost->getSecurity(),
                    //                    'creatorId' => $creator->getId()
                ],
                ['id' => $chapter->getId()]
            );

            $return = (bool)$updateCount;
        } else {
            if (!$chapter->getCreationTime()) {
                $chapter->setCreationTime(new \DateTime());
            }

            $creationTimeString = $chapter->getCreationTime()->format('Y-m-d H:i:s');

            $updateCount = $this->dbConnection->insert(
                'chapters',
                [
                    'created_at' => $creationTimeString,
                    'updated_at' => $creationTimeString,
                    'title' => $chapter->getSubject(),
                    'body_text' => $chapter->getBody(),
                    'chapter_id' => $chapter->getChapterNumber(),
                    'is_activated' => $chapter->isActive() ? 1 : 0


                    //                    'security' => $chapter->getSecurity(),
                    //                    'creatorId' => $creator->getId()
                ]
            );

            if ($updateCount) {
                $id = $this->dbConnection->lastInsertId();
                if ($id) {
                    $chapter->setId($id);
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Load a blog post from the configured DBAL by primary key
     *
     * @param $presentPostId
     * @return null|Chapter
     */
    public function fetchChapterById($presentPostId)
    {
        $post = null;
        $sql = 'SELECT * FROM chapters WHERE id=?';
        $row = $this->dbConnection->fetchAssoc($sql, [$presentPostId]);
        if ($row) {
            $post = $this->createChapterFromDbRow($row);
        }
        return $post;
    }

    /**
     * Fetch $count of the most recent posts ordered newest-first, taking future time & security into account
     *
     * @param bool $publicOnly
     * @throws \InvalidArgumentException
     * @return Chapter[]
     */
    public function fetchChapters($publicOnly = true)
    {
        $posts = [];


        $whereParts = [];
        $queryParams = [];

        if ($publicOnly) {
            $whereParts[] = " is_activated = 1 ";
            //            $queryParams['security'] = Chapter::SECURITY_PUBLIC;
        }
        //
        //        if ($pastOnly) {
        //            $now = new \DateTime();
        //            $whereParts[] = " `time` <= :when "; // no "NOW()" in sqlite?
        //            $queryParams['when'] = $now->format('Y-m-d H:i');
        //        }

        if ($whereParts) {
            $whereClause = ' WHERE ' . join(' AND ', $whereParts);
        } else {
            $whereClause = '';
        }

        $sql = "SELECT * FROM chapters $whereClause ORDER BY `chapter_id` DESC";
        $rows = $this->dbConnection->fetchAll($sql, $queryParams);
        foreach ($rows as $row) {
            $posts[] = $this->createChapterFromDbRow($row);
        }
        return $posts;
    }


    /**
     * Fetch all posts ordered newest-first, but don't load their bodies; use for archive lists etc
     *
     * @param bool $publicOnly
     * @param bool $pastOnly
     * @return Chapter[]
     */
    public function fetchAllChaptersNoBody($publicOnly = false, $pastOnly = false)
    {

        $whereParts = [];
        $queryParams = [];

        if ($publicOnly) {
            $whereParts[] = " is_activated = 1 ";

        }

        //        if ($pastOnly) {
        //            $now = new \DateTime();
        //            $whereParts[] = " `time` <= :when "; // no "NOW()" in sqlite?
        //            $queryParams['when'] = $now->format('Y-m-d H:i');
        //        }

        if ($whereParts) {
            $whereClause = ' WHERE ' . join(' AND ', $whereParts);
        } else {
            $whereClause = '';
        }

        $posts = [];

        $sql = "SELECT id,chapter_id,title,is_activated, null as body_text,created_at,updated_at
          FROM chapters $whereClause ORDER BY `chapter_id` DESC";
        $rows = $this->dbConnection->fetchAll($sql, $queryParams);
        foreach ($rows as $row) {
            $posts[] = $this->createChapterFromDbRow($row);
        }
        return $posts;
    }

    /**
     * Create a post class from raw DB row / array
     *
     * @param $row
     * @return Chapter
     */
    protected function createChapterFromDbRow($row)
    {
        $post = new Chapter();
        $post->setId($row['id']);
        $post->setChapterNumber($row['chapter_id']);
        $post->setSubject($row['title']);
        $post->setCreationTime(new \DateTime($row['created_at']));
        $post->setUpdateTime(new \DateTime($row['updated_at']));
        $post->setBody($row['body_text']);
        $post->setActive((bool)$row['is_activated']);

        return $post;
    }
}
