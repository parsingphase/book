<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 09/09/14
 * Time: 14:16
 */

namespace Phase\Blog;


use DateTime;

/**
 * Single blog entry, savable through the Blog class
 * @package Phase\Blog
 */
class BlogPost
{
    const SECURITY_PUBLIC = 'public';
    const SECURITY_PRIVATE = 'private';

    /**
     * @var int|null
     */
    protected $id;
    /**
     * @var DateTime
     */
    protected $time;
    protected $subject;
    protected $body;
    protected $security = self::SECURITY_PUBLIC;

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
     * @param mixed $security
     * @return $this
     */
    public function setSecurity($security)
    {
        $this->security = $security;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSecurity()
    {
        return $this->security;
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
    public function setTime(DateTime $time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTime()
    {
        return $this->time;
    }

    public function getSlug()
    {
        $slug = $this->getSubject();
        $slug = preg_replace('/[\'"`]/', '', $slug);
        $slug = preg_replace('/[^a-z0-9]/i', '_', $slug);
        $slug = preg_replace('/_{2,}/', '_', $slug);
        return (strtolower($slug));
    }

    public function isInPast()
    {
        return $this->getTime() <= new DateTime();
    }

    public function isPublic()
    {
        return $this->getSecurity() == self::SECURITY_PUBLIC;
    }
}
