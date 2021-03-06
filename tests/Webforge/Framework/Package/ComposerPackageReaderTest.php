<?php

namespace Webforge\Framework\Package;

class ComposerPackageReaderTest extends \Webforge\Code\Test\Base {
  
  protected $reader;
  
  public function setUp() {
    $this->reader = new ComposerPackageReader();
  }

  public function testFromDirectoryReturnsAnPackageWithInfosFromTheComposerJSON() {
    $package = $this->reader->fromDirectory($dir = $this->getPackageRoot('ACME'));
    
    $this->assertInstanceOf('Webforge\Framework\Package\Package', $package);
    $this->assertEquals('acme/intranet-application', $package->getIdentifier());
    $this->assertEquals((string) $dir, (string) $package->getRootDirectory());
  }
  
  public function testFromDirectoryPackageAutoLoadInfoIsPreFilledFromACME() {
    $package = $this->reader->fromDirectory($dir = $this->getPackageRoot('ACME'));
    
    $this->assertInstanceOf('Webforge\Framework\Package\Package', $package);
    $this->assertInstanceOf('Webforge\Setup\AutoLoadInfo', $autoLoad = $package->getAutoLoadInfo());
    $this->assertEquals(
      array('ACME\IntranetApplication'=>array('lib/')),
      $autoLoad->getPrefixes(),
      'Prefixes from AutoLoadInfo for ACME is damaged'
    );
  }
  
  public function testFromDirectoryWithoutJSONFileFails() {
    $this->setExpectedException('Webforge\Common\Exception');
    $this->reader->fromDirectory($this->getPackageRoot('Blank'));
  }

  public function testReaderSetsDirectoryLocationsFromComposerJson() {
    $package = $this->reader->fromDirectory($root = $this->getPackageRoot('serien-loader'));

    $this->assertEquals(
      (string) $root->sub('application/config/'),
      (string) $package->getDirectory('etc')
    );
  }

  protected function getPackageRoot($name) {
    return $this->getTestDirectory('packages/'.$name.'/');
  }
}
