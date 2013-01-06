<?php

namespace Webforge\Code\Generator;

use Psc\Code\Generate\DocBlock;

class GObjectTest extends \Webforge\Code\Test\Base {
  
  protected $o;
  
  public function setUp() {
    $this->o = $this->getMockForAbstractClass('GObject');
  }
  
  public function testCreateDocBlockWithGetter() {
    $this->assertInstanceof('Psc\Code\Generate\DocBlock', $this->o->getDocBlock());
  }
  
  public function testSetDocBlockReplacesDocBlock() {
    $docBlock = new DocBlock('my comment');
    $this->o->setDocBlock($docBlock);
    $this->assertSame($docBlock, $this->o->getDocBlock());
  }
  
  public function testHasDocBlock() {
    $this->assertFalse($this->o->hasDocBlock());
    
    $this->o->createDocBlock();
    
    $this->assertTrue($this->o->hasDocBlock());
  }
}
?>