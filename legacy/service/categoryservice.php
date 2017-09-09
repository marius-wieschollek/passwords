<?php
namespace OCA\Passwords\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\Passwords\Db\Category;
use OCA\Passwords\Db\CategoryMapper;

class CategoryService {

	private $mapper;

	public function __construct(CategoryMapper $mapper){
		$this->mapper = $mapper;
	}

	public function findAll($userId) {
		return $this->mapper->findAll($userId);
	}

	private function handleException ($e) {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new NotFoundException($e->getMessage());
		} else {
			throw $e;
		}
	}

	public function find($id, $userId) {
		try {
			return $this->mapper->find($id, $userId);
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}

	public function create($categoryName, $categoryColour, $userId) {
		// validity check when used by API
		if (preg_match('/#([a-f0-9]{3}){1,2}\b/i', '#' . $categoryColour)) {
			$category = new Category();
			$category->setCategoryName(strip_tags($categoryName));
			$category->setCategoryColour($categoryColour);
			$category->setUserId($userId);
			return $this->mapper->insert($category);
		} else {
			return false;
		}
	}

	public function delete($id, $userId) {
		try {
			$category = $this->mapper->find($id, $userId);
			$this->mapper->delete($category);
			return $category;
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}
}
