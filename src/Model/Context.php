<?php

namespace Model;

use Nette\Database;
use Nette\Caching;
use Nette\Bridges;
use Tracy\Debugger;
use Nette\Utils\Strings;

final class Context {

	const
		POSTS_TABLE_NAME = 'posts',
		PROPS_TABLE_NAME = 'props',
		LINKS_TABLE_NAME = 'links';

	private $cacheStorage;
	private $databaseConnection;
	private $databaseContext;
	private $databaseStructure;

	public static $propTypes = [];

	public function __construct(Database\Connection $connection, Caching\IStorage $storage, $global = FALSE) {
		$this->databaseConnection = $connection;
		$this->cacheStorage = $storage;

		$this->databaseStructure = new Database\Structure($this->databaseConnection, $this->cacheStorage);
		$this->databaseContext = new Database\Context($this->databaseConnection, $this->databaseStructure, new Conventions());

		if($global) {
			$this->registerGlobally();
		}
	}

	public function registerGlobally() {
		Post::$context = $this;
	}

	public function unregisterGlobally() {
		if(Post::$context === $this) {
			Post::$context = NULL;
		}
	}

	public function addTracyBarPanel() {
		$panel = new Bridges\DatabaseTracy\ConnectionPanel($this->databaseConnection);
		Debugger::getBar()->addPanel($panel);
	}

	public static function propType2ColumnName($type) {
		if(array_key_exists($type, static::$propTypes)) {
			return static::$propTypes[$type];
		}

		return 'text_value';
	}

	public static function serializePropType($type, $value) {
		switch($type) {
			case 'meta':
				return json_encode($value);
		}
		return $value;
	}

	public static function unserializePropType($type, $value) {
		switch($type) {
			case 'meta':
				return json_decode($value);
		}
		return $value;
	}

	public function getDb() {
		return $this->databaseContext;
	}

	public function getDbTable($table) {
		return $this->getDb()->table($table);
	}

	public function getPosts() {
		return $this->getDbTable(static::POSTS_TABLE_NAME);
	}

	public function getProps() {
		return $this->getDbTable(static::PROPS_TABLE_NAME);
	}

	public function getLinks() {
		return $this->getDbTable(static::LINKS_TABLE_NAME);
	}

	public function getPost($id) {
		return $this->getPosts()->get($id);
	}

	public function getOrderedPosts($prop, $direction = 'ASC') {
		$direction = Strings::upper($direction);
		return $this->getPosts()->where(':props.prop_type', $prop)->order(':props.' . static::propType2ColumnName($prop) . ' ' . $direction);
	}

	public function fetchSinglePropValue(&$values, $type) {
		$values = static::unserializePropType($type, $values[static::propType2ColumnName($type)]);
	}

	public function getPostProps($id) {
		$props = $this->getProps()
			->select('id, prop_type, string_value, text_value, int_value, float_value, bool_value')
			->where('post_id ?', $id)
			->fetchPairs('prop_type');

		array_walk($props, 'fetchSinglePropValue');

		return $props;
	}

	public function getPostProp($id, $prop) {
		$col = static::propType2ColumnName($prop);
		$value = $this->getProps()->select($col)->where('post_id ? AND prop_type ?', $id, $prop)->fetchField($col);
		return static::unserializePropType($prop, $value);
	}

	public function setPostProp($id, $prop, $value) {
		$col = static::propType2ColumnName($prop);
		$this->getProps()->where('post_id ? AND prop_type ?', $id, $prop)->delete();
		$this->getProps()->insert([
			'post_id' => $id,
			'prop_type' => $prop,
			$col => static::serializePropType($prop, $value)
		]);

		return $value;
	}

}