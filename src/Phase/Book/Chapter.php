<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 09/09/14
 * Time: 14:16
 */

namespace Phase\Book;


use DateTime;
use SimpleUser\User;

/**
 * Single chapter, savable through the Book class
 * @package Phase\Book
 */
class Chapter
{

    /**
     * @var int|null
     */
    protected $id;

    protected $chapterId;

    /**
     * @var DateTime
     */
    protected $creationTime;
    /**
     * @var DateTime
     */
    protected $updateTime;
    protected $subject;
    protected $body;
    /**
     * @var bool
     */
    protected $isActive = false;

    /**
     * @param mixed $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Note: Changing this to a new value will prevent saving
     *
     * @param mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getChapterNumber()
    {
        return $this->chapterId;
    }

    /**
     * @param mixed $chapterId
     * @return $this
     */
    public function setChapterNumber($chapterId)
    {
        $this->chapterId = $chapterId;
        return $this;
    }


    /**
     * @param mixed $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param DateTime $time
     * @return $this
     */
    public function setCreationTime(DateTime $time)
    {
        $this->creationTime = $time;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @return DateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param DateTime $updateTime
     * @return $this
     */
    public function setUpdateTime($updateTime)
    {
        $this->updateTime = $updateTime;
        return $this;
    }


    public function getSlug()
    {
        $slug = $this->getSubject();
        $slug = preg_replace('/[\'"`]/', '', $slug);
        $slug = preg_replace('/[^a-z0-9]/i', '_', $slug);
        $slug = preg_replace('/_{2,}/', '_', $slug);
        return (strtolower($slug));
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $isActive
     * @return $this
     */
    public function setActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }



}
