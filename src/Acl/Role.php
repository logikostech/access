<?php


namespace Logikos\Access\Acl;


interface Role {
  public function name();
  public function description();
  public function inherits();
  public function toArray();
  public function __toString();
}