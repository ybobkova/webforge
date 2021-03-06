<?php

namespace Webforge\Framework;

use Webforge\Framework\Package\Package;
use Webforge\Common\System\Dir;
use InvalidArgumentException;
use Webforge\Common\String as S;

class DirectoryLocations {

  /**
   * @var Webforge\Common\System\Dir
   */
  protected $root;

  /**
   * @var array
   */
  protected $locations;

  /**
   * @param array $locations key: identifier, value: the path with / slashes and / at the end relative to $root
   */
  public function __construct(Dir $root, Array $locations) {
    $this->root = $root;
    $this->locations = $locations;
    $this->locations['root'] = '/';
  }

  public function setRoot(Dir $root) {
    // remember to refresh cache, when cached someday
    $this->root = $root;
    return $this;
  }

  public static function createFromPackage(Package $package) {
    return new static(
      $package->getRootDirectory(),
      array(
        'lib'=>'lib/',
        'tests'=>'tests/',
        'test-files'=>'tests/files/',
        'bin'=>'bin/',
        'etc'=>'etc/',
        'cache'=>'files/cache/',
        'tpl'=>'resources/tpl/',
        'tpl-cache'=>'cache/tpl/',
        'docs'=>'docs/',
        'vendor'=>'vendor/',
        'build'=>'build/',
        'logs'=>'files/logs/',

        'test-files'=>'tests/files/',
        
        'www'=>'www/',
        'cms-www'=>'www/cms/',
        'cms-uploads'=>'files/uploads/',
        'cms-images'=>'files/images/',

        'resources'=>'resources/',
        'prototypes'=>'resources/prototypes/',
        'assets-src'=>'resources/assets/',
        'assets-built'=>'www/assets/',

        'cms-tpl'=>'resources/tpl/', // dont use this anymore

        'doctrine-proxies'=>'files/cache/doctrine-proxies/'
      )
    );
  }

  public function get($identifier) {
    if (array_key_exists($identifier, $this->locations)) {
      return $this->root->sub($this->locations[$identifier]);
    }

    throw new InvalidArgumentException(sprintf("The identifier '%s' for a directory location is not known. available are: %s", $identifier, implode(', ', array_keys($this->locations))));
  }

  /**
   * @return bool
   */
  public function has($identifier) {
    return array_key_exists($identifier, $this->locations);
  }

  /**
   * @param string $alias (without slashes) only dashes and alpha numeric (lowercased)
   * @param string $sub path from root to dir with / at the end
   */
  public function add($alias, $sub) {
    $this->locations[$alias] = S::expand($sub, '/');
    return $this;
  }

  /**
   * @param string $alias (without slashes) only dashes and alpha numeric (lowercased)
   * @param string $sub path from root to dir with / at the end
   */
  public function set($alias, $sub) {
    return $this->add($alias, $sub);
  }

  /**
   * @param array $Locations key = alias value = subpath from root
   */
  public function addMultiple(Array $locations) {
    foreach ($locations as $alias => $sub) {
      $this->add($alias, $sub);
    }
    return $this;
  }
}
