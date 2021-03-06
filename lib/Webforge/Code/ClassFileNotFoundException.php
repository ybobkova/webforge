<?php

namespace Webforge\Code;

use Webforge\Framework\Package\PackageNotFoundException;

class ClassFileNotFoundException extends \Webforge\Common\Exception {
  
  public static function fromFQN($fqn) {
    return new static(sprintf("The File for the class '%s' cannot be found", $fqn));
  }
  
  public static function fromPackageNotFoundException($fqn, PackageNotFoundException $e) {
    return new static(sprintf("The Class '%s' cannot be found. %s", $fqn, $e->getMessage()), 0, $e);
  }
}
