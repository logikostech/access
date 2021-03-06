<?php


namespace Logikos\Access\Acl;

use Iterator;
use Traversable;

abstract class EntityCollection extends \IteratorIterator implements Iterator, \Serializable {

  public function __construct(Traversable $iterator) {
    parent::__construct($this->rebuildAndValidateTraversable($iterator));
  }

  private function rebuildAndValidateTraversable(Traversable $t) {
    $entities = [];

    foreach ($t as $row)
      array_push($entities, $this->buildEntity($row));

    return new \ArrayIterator($entities);
  }

  abstract protected function buildEntity($row);

  /**
   * @param callable $cb
   * @return Entity|Role|Rule\Resource
   */
  public function find(callable $cb) {
    foreach ($this as $entity)
      if (call_user_func($cb, $entity, $this))
        return $entity;
  }

  public function findByString($string) {
    return $this->find(
        function ($entity) use ($string) {
          return method_exists($entity, '__toString')
              && $entity->__toString() === $string;
        }
    );
  }

  public function filter(callable $cb) {
    $results = [];
    foreach ($this as $entity) {
      if (call_user_func($cb, $entity, $this)) {
        array_push($results, $entity);
      }
    }

    return static::fromArray($results);
  }

  public function toArray() {
    $rows = [];
    foreach ($this as $entity) {
      array_push($rows, $entity->toArray());
    }
    return $rows;
  }

  public function toArrayOfStrings() {
    return $this->arrayMap('strval');
  }

  public function arrayMap(callable $callable) {
    $array = [];
    foreach ($this as $entity) {
      array_push($array, call_user_func($callable, $entity));
    }
    return $array;
  }

  public static function fromPdoStatement(\PDOStatement $sth) {
    return self::fromArray(self::PdoSthToArray($sth));
  }

  /**
   * @param array $array
   * @return static
   */
  public static function fromArray(array $array) {
    return new static(new \ArrayIterator($array));
  }



  protected static function PdoSthToArray(\PDOStatement $sth) {
    $rows = [];

    while ($row = $sth->fetch(\PDO::FETCH_ASSOC))
      array_push($rows, $row);

    return $rows;
  }

  public function serialize() {
    $rows = [];
    foreach ($this as $entity) {
      array_push($rows, $entity);
    }
    return serialize($rows);
  }

  public function unserialize($serialized) {
    $data = unserialize($serialized);
    $this->__construct(new \ArrayIterator($data));
  }
}