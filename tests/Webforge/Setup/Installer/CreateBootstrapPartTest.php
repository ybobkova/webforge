<?php

namespace Webforge\Setup\Installer;

use Webforge\Framework\Package\Package;

class CreateBootstrapPartTest extends \Webforge\Code\Test\InstallerPartTestCase {
  
  public function setUp() {
    $this->chainClass = 'Webforge\\Setup\\Installer\\CreateBootstrapPart';
    parent::setUp();

    $this->part = new CreateBootstrapPart();
  }
  
  public function testPartCreatesTheBootstrapPHPFile() {
    $this->macro = $this->installer->dryInstall($this->part, $this->target);
    
    $this->assertArrayEquals(array('/bootstrap.php', '/lib/package.boot.php'), $this->getCopiedFiles($this->macro));
  }
}
