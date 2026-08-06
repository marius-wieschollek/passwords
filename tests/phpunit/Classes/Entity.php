<?php
namespace OCP\AppFramework\Db;

/**
 * Minimal stand-in for the Nextcloud entity base class.
 * Implements enough of the magic accessor behaviour that the entities
 * in src/lib/Db can be used in tests without a Nextcloud installation.
 */
class Entity {

    private array $_updatedFields = [];

    private array $_fieldTypes = [];

    public function __call($methodName, $args) {
        if(str_starts_with($methodName, 'set')) {
            $this->setter(lcfirst(substr($methodName, 3)), $args);

            return null;
        }

        if(str_starts_with($methodName, 'get')) {
            return $this->getter(lcfirst(substr($methodName, 3)));
        }

        if(str_starts_with($methodName, 'is')) {
            return $this->getter(lcfirst(substr($methodName, 2)));
        }

        throw new \BadFunctionCallException($methodName.' does not exist');
    }

    protected function markFieldUpdated(string $attribute): void {
        $this->_updatedFields[ $attribute ] = true;
    }

    protected function addType($fieldName, $type): void {
        $this->_fieldTypes[ $fieldName ] = $type;
    }

    public function getFieldTypes(): array {
        return $this->_fieldTypes;
    }
}
