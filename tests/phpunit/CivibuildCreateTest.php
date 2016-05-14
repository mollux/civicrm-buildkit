<?php

use \Civi\Civibuild\ProcessUtil;

/**
 * Class CivibuildCreateTest
 *
 * Execute "civibuild create" and ensure it prodcues a website.
 *
 * @todo Check if an instance named "civibuild-test" already exist before creating it
 * @todo Destroy created test intance after run "civibuild create ..." test
 */
class CivibuildCreateTest extends \Civi\Civibuild\CivibuildTestCase {

  /**
   * Create a test instance name Civibuild-test
   * (assumes no one will have created such an instance)
   */
  public function testWpDemoBasic() {
    $result = ProcessUtil::runOk($this->cmd(
      'civibuild create civibuild-test --force --type wp-demo --civi-ver master' .
      ' --url http://civibuild-test.localhost'
    ));

    $this->assertTrue(is_dir($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages')), 'Expect to find packages dir');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/DUMMY-PATCH-DATA.txt')), 'Expect pristine core');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages/FIRST-DUMMY.txt')), 'Expect pristine packages');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages/SECOND-DUMMY.txt')), 'Expect pristine packages');
    $this->assertRegExp('; - CMS_ROOT: [^\n]+/build/civibuild-test;', $result->getOutput());
    $this->assertRegExp('; - CIVI_DB_DSN: mysqli://.*:.*@.*/.*;', $result->getOutput());

    $configFile = $this->getPrjDir() . '/build/civibuild-test.sh';
    $this->assertFileExists($configFile);
    $config = file_get_contents($configFile);
    $this->assertRegExp(';CMS_ROOT=[^\n]+/build/civibuild-test;', $config);
  }

  public function testWpDemoWithPatch() {
    $corePatch = __DIR__ . '/CivibuildCreateTest.core-1.patch';
    $pkgPatch1 = __DIR__ . '/CivibuildCreateTest.pkg 1.patch';
    $pkgPatch2 = __DIR__ . '/CivibuildCreateTest.pkg 2.patch';

    $result = ProcessUtil::runOk($this->cmd(
      'civibuild create civibuild-test --force --type wp-demo --civi-ver master' .
      ' --url http://civibuild-test.localhost' .
      ' --patch ' . escapeshellarg(";civicrm-packages;$pkgPatch1") .
      ' --patch ' . escapeshellarg(";civicrm-core;$corePatch") .
      ' --patch ' . escapeshellarg(";civicrm-packages;$pkgPatch2")
    ));

    $this->assertTrue(is_dir($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages')), 'Expect to find packages dir');
    $this->assertTrue(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/DUMMY-PATCH-DATA.txt')), 'Expect patched core');
    $this->assertTrue(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages/FIRST-DUMMY.txt')), 'Expect patched packages');
    $this->assertTrue(file_exists($this->getAbsPath('civibuild-test', 'wp-content/plugins/civicrm/civicrm/packages/SECOND-DUMMY.txt')), 'Expect patched packages');
    $this->assertRegExp('; - CMS_ROOT: .*/build/civibuild-test;', $result->getOutput());
    $this->assertRegExp('; - CIVI_DB_DSN: mysqli://.*:.*@.*/.*;', $result->getOutput());
  }

  public function testWpDemoWithConflictedPatch() {
    $corePatch = __DIR__ . '/CivibuildCreateTest.core-1.patch';
    $pkgPatch1 = __DIR__ . '/CivibuildCreateTest.pkg 1.patch';
    $pkgPatch2 = __DIR__ . '/CivibuildCreateTest.pkg 2.patch';
    $pkgPatchConflict = __DIR__ . '/CivibuildCreateTest.pkg conflict.patch';

    try {
      ProcessUtil::runOk($this->cmd(
        'civibuild download civibuild-test --force --type wp-demo --civi-ver master' .
        ' --url http://civibuild-test.localhost' .
        ' --patch ' . escapeshellarg(";civicrm-packages;$pkgPatch1") .
        ' --patch ' . escapeshellarg(";civicrm-core;$corePatch") .
        ' --patch ' . escapeshellarg(";civicrm-packages;$pkgPatch2") .
        ' --patch ' . escapeshellarg(";civicrm-packages;$pkgPatchConflict")
      ));
      $this->fail("Expected download to fail with error");
    } catch (\Civi\Civibuild\Exception\ProcessErrorException $e) {
      $result = $e->getProcess();
      $this->assertEquals(95, $result->getExitCode());
      $this->assertContains('COMMAND: git apply --check', $result->getErrorOutput());
      $this->assertContains('Failed to apply patch(es)', $result->getOutput());
      $this->assertNotRegExp('; - CMS_ROOT: .*/build/civibuild-test;', $result->getOutput());
    }
  }

  public function testDrupalDemoPHP7() {
    $result = ProcessUtil::runOk($this->cmd(
      'civibuild create civibuild-test --force --type drupal-demo --civi-ver php7' .
      ' --url http://civibuild-test.localhost'
    ));

    $this->assertTrue(is_dir($this->getAbsPath('civibuild-test', 'sites/all/modules/civicrm/packages')), 'Expect to find packages dir');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'sites/all/modules/civicrm/DUMMY-PATCH-DATA.txt')), 'Expect pristine core');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'sites/all/modules/civicrm/packages/FIRST-DUMMY.txt')), 'Expect pristine packages');
    $this->assertFalse(file_exists($this->getAbsPath('civibuild-test', 'sites/all/modules/civicrm/packages/SECOND-DUMMY.txt')), 'Expect pristine packages');
    $this->assertRegExp('; - CMS_ROOT: [^\n]+/build/civibuild-test;', $result->getOutput());
    $this->assertRegExp('; - CIVI_DB_DSN: mysqli://.*:.*@.*/.*;', $result->getOutput());

    $configFile = $this->getPrjDir() . '/build/civibuild-test.sh';
    $this->assertFileExists($configFile);
    $config = file_get_contents($configFile);
    $this->assertRegExp(';CMS_ROOT=[^\n]+/build/civibuild-test;', $config);
  }


}
