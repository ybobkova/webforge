<?php

namespace Webforge\Framework\CLI;

use Webforge\Framework\Container;
use Webforge\Framework\Package\Registry;
use Webforge\Setup\ApplicationStorage;
use org\bovigo\vfs\vfsStream;
use Webforge\Common\System\Dir;
use Symfony\Component\Console\Tester\CommandTester;
use Mockery as m;

class CommandTestCase extends \Webforge\Code\Test\Base {

  protected $container, $registry;

  public $testOs;

  public function setUp() {
    $this->container = new Container();

    $this->output = $this->getMockForAbstractClass('Webforge\Console\CommandOutput');
    $this->input = m::mock('Webforge\Console\CommandInput');
    $this->interactionHelper = m::mock('Webforge\Console\InteractionHelper');

    $this->system = m::mock('Webforge\Common\System\System');

    $this->testos = \Webforge\Common\System\ExecutionSystem::UNIX;
    $this->system->shouldReceive('getOperatingSystem')->andReturn($this->testOs);
    
    $this->application = new Application($this->getPackageDir('/'), $this->container);
    parent::setUp();
  }

  protected function mockContainerPackageRegistry($methods = array()) {
    $mock = $this->getMock('Webforge\Framework\Package\Registry', $methods, array($this->container->getComposerPackageReader()));

    $this->container->setPackageRegistry($mock);

    return $mock;
  }

  protected function mockApplicationStorage($methods = array()) {
    $appDir = vfsStream::setup('appstorage');

    $mock = new ApplicationStorage('webforge-test', Dir::factoryTS(vfsStream::url('appstorage')));

    $this->container->setApplicationStorage($mock);

    return $mock;
  }

  protected function runCommand($name, Array $args = array()) {
    $command = $this->application->find($name);

    $tester = new CommandTester($command);
    $tester->execute(array_merge(
      array('command'=>$name), $args
    ));

    return $tester->getDisplay();
  }

  /**
   */
  public function expectInteraction($type) {
    return $this->interactionHelper
      ->shouldReceive($type)
      ->once()
      ->ordered('interact');
  }

  public function expectQuestion() {
    return $this->expectInteraction('askDefault');
  }

  public function expectSimpleQuestion() {
    return $this->expectInteraction('ask');
  }

  public function expectConfirm() {
    return $this->expectInteraction('confirm');
  }

  protected function getVirtualDirectory($name) {
    $dir = vfsStream::setup($name);

    return new Dir(vfsStream::url($name).'/');
  }

  protected function getVirtualDirectoryFromPhysical($name, Dir $physicalDirectory, $maxFileSize = 2048) {
    $dir = vfsStream::setup($name);

    vfsStream::copyFromFileSystem((string) $physicalDirectory, $dir, $maxFileSize);

    return new Dir(vfsStream::url($name).'/');
  }

  protected function expectInputValue($name, $value) {
    $this->input->shouldReceive('getValue')
      ->with($name)
      ->andReturn($value);
  }

  protected function expectInputFlag($name, $bool) {
    $this->input->shouldReceive('getFlag')
      ->with($name)
      ->andReturn($bool);
  }

  protected function initIO($command) {
    $command->initIO($this->input, $this->output, $this->interactionHelper, $this->system);
  }

  protected function executeCLI($command) {
    return $command->executeCLI($this->input, $this->output, $this->interactionHelper, $this->system);
  }

  protected function createVirtualPackage($packageNameInPackages) {
    $this->container->setPackageRegistry($this->registry = new Registry());

    $virtualDirectory = $this->getVirtualDirectoryFromPhysical(
      $packageNameInPackages, 
      $this->getTestDirectory()->sub('packages/'.$packageNameInPackages.'/')
    );

    $vpackage = $this->registry->addComposerPackageFromDirectory($virtualDirectory);

    return $vpackage;
  }

  protected function injectVirtualPackage($packageNameInPackages) {
    $this->container->setLocalPackage($vpackage = $this->createVirtualPackage($packageNameInPackages));

    return $vpackage;
  }
}
