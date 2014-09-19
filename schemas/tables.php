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

$chapters = new Table('chapter');
$chapters->addColumn('id', Type::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
$chapters->setPrimaryKey(['id']);
$chapters->addColumn('chapter_id', Type::INTEGER, ['unsigned' => true]);
$chapters->addColumn('title', Type::STRING)->setLength(255)->setNotnull(true);
$chapters->addColumn('body_text', Type::TEXT)->setNotnull(true);
$chapters->addColumn('is_activated', Type::INTEGER, ['unsigned' => true]);
$chapters->addColumn('created_at', Type::DATETIME)->setNotnull(true); // see if sqlite handles this?
$chapters->addColumn('updated_at', Type::DATETIME)->setNotnull(true); // see if sqlite handles this?

$tables['chapter'] = $chapters;


return $tables;
