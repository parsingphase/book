<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 11/09/14
 * Time: 09:52
 */

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

$tables = [];

$blogPost = new Table('blog_post');
$blogPost->addColumn('id', Type::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
$blogPost->setPrimaryKey(['id']);
$blogPost->addColumn('time', Type::DATETIME)->setNotnull(true); // see if sqlite handles this?
$blogPost->addColumn('subject', Type::STRING)->setLength(255)->setNotnull(true);
$blogPost->addColumn('body', Type::TEXT)->setNotnull(true);
$blogPost->addColumn('security', Type::STRING)->setNotnull(true); //TODO Enums not supported, use ints / class consts?
$blogPost->addColumn('creatorId', Type::INTEGER)->setNotnull(true)->setDefault(1); // default for existing records

$tables['blog_post'] = $blogPost;


return $tables;
