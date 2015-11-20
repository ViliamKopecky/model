<?php

namespace Model;

use Nette\Database\Table\ActiveRow;

class Post {

	public static $context;
	public $localContext;

	private static $ref = [];
	private $row;
	private $cached_values = [];

	public function __construct(ActiveRow $row) {
		$this->row = $row;
		static::$ref[$row->id] = $this;
	}

	public function __toString() {
		return (string) $this->row;
	}

	public static function fromId($id) {
		if(!empty(static::$ref[$id])) {
			return static::$ref[$id];
		}
		$row = static::$context->getPost($id);

		return $row ? static::fromRow($row) : FALSE;
	}

	public static function fromRow(ActiveRow $row) {
		return new static($row);
	}

	public static function create($values) {
		$type = $values['type'];
		$id = array_key_exists('id', $values) ? $values['id'] : md5(uniqid());

		unset($values['type']);
		unset($values['id']);

		$table = static::$context->getPosts();

		$row = $table->insert([
			'id' => $id,
			'type' => $type,
		]);

		$post = static::fromId($id);

		foreach($values as $prop => $value) {
			$post->setProp($prop, $value);
		}

		return $post;
	}

	public function getContext() {
		if(empty($this->localContext)) {
			$this->localContext = static::$context;
		}

		return $this->localContext;
	}

	public function getProp($key) {
		return $this->getContext()->getPostProp($this->row->id, $key);
	}

	public function setProp($key, $value) {
		return $this->getContext()->setPostProp($this->row->id, $key, $value);
	}

	public function __get($key) {
		if(isset($this->cached_values[$key])) {
			return $this->cached_values[$key];
		}
		switch($key) {
			case 'id':
				return $this->cached_values[$key] = $this->row->id;
			case 'type':
				return $this->cached_values[$key] = $this->row->type;
			default:
				return $this->cached_values[$key] = $this->getContext()->getPostProp($this->row->id, $key);
		}
	}

	public function __set($key, $value) {
		if(array_key_exists($key, $this->cached_values)) {
			return $this->cached_values[$key];
		}
		switch($key) {
			case 'id':
				throw new \Exception('Cannot change Post id');
			case 'type':
				$this->row->update([ 'type' => $value ]);
				return $this->cached_values[$key] = $value;
			default:
				return $this->cached_values[$key] = $this->getContext()->setPostProp($this->row->id, $key, $value);
		}
	}

	public function related($link, $direction = 'any') {
		$query = $this->getContext()->getLinks()->where('link_type ?', $link);

		$id = $this->row->id;

		switch($direction) {
			case 'linker':
				$query
					->select("linked_id AS post_id")
					->where('linker_id ?', $id);
				break;
			case 'linked':
				$query
					->select("linker_id AS post_id")
					->where('linked_id ?', $id);
				break;
			default:
				// to-do: find better way to determine the 'other' post_id
				$query
					->select("REPLACE(REPLACE(CONCAT(linker_id, '|', linked_id), ?, ''), '|', '') AS ?", $id, 'post_id')
					->where('linker_id ? OR linked_id ?', $id, $id);
		}

		return $query;
	}

	public function linked($link) {
		return $this->related($link, 'linker');
	}

	public function linkedBy($link) {
		return $this->related($link, 'linked');
	}

	public function link($link, $post, $reverse = FALSE) {
		$vals = [
			'link_type' => $link,
			'linker_id' => (string) ($reverse ? $post : $this),
			'linked_id' => (string) ($reverse ? $this : $post),
		];
		return $this->getContext()->getLinks()->insert($vals);
	}
	
}
