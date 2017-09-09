<?php
namespace OCA\Passwords\Db;

use JsonSerializable;

use \OCP\AppFramework\Db\Entity;

class Category extends Entity implements JsonSerializable {

	public $id;
	protected $userId;
	protected $categoryName;
	protected $categoryColour;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'user_id' => $this->userId,
			'category_name' => $this->categoryName,
			'category_colour' => $this->categoryColour
		];
	}
}
