<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

before(function() {
	Model\Post::$context = NULL;
});

test(function() {

	$storage = new Nette\Caching\Storages\FileStorage(TEMP_DIR);
	$connection = new Nette\Database\Connection(MYSQL_DSN, MYSQL_USER, MYSQL_PASSWORD);
	Nette\Database\Helpers::loadFromFile($connection, SCHEMA_PATH);

	$context = new Model\Context($connection, $storage, TRUE);

	Assert::same($context, Model\Post::$context);

	$id = 'non-exising-id';
	$nonExistingPost = Model\Post::fromId($id);
	Assert::false($nonExistingPost);

	$post1 = Model\Post::create([
		'type' => 'movie',
		'title' => 'Hello world'
	]);

	Assert::same($post1->type, 'movie');
	Assert::same($post1->title, 'Hello world');

	$post2 = Model\Post::create([
		'type' => 'movie',
		'title' => 'Apocalypse'
	]);

	$post1->link('related', $post2);

	Assert::same($post2->related('related')->fetch()->post_id, $post1->id);
	Assert::same($post1->related('related')->fetch()->post_id, $post2->id);

	Assert::count(1, $post1->linked('related'));
	Assert::count(0, $post1->linkedBy('related'));

	Assert::count(0, $post2->linked('related'));
	Assert::count(1, $post2->linkedBy('related'));
});