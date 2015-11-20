<?php

namespace Model;

use Nette\Database\Conventions\StaticConventions;

final class Conventions extends StaticConventions {

	public function getHasManyReference($table, $targetTable = NULL) {
		switch("$table:$targetTable") {
			case 'posts:props': return [ $targetTable, 'post_id' ];
		}
		parent::getHasManyReference($table, $targetTable);
	}
	
}