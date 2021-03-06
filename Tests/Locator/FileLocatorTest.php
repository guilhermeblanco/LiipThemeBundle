<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\Tests\Locator;

use Liip\ThemeBundle\Locator\FileLocator;
use Liip\ThemeBundle\ActiveTheme;

use Symfony\Component\HttpKernel\KernelInterface;


class FileLocatorFake extends FileLocator {
    public $lastTheme;
}

class FileLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected function getKernelMock()
    {
        $data = debug_backtrace();
        $bundleName = substr($data[1]['function'], 4);
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\Bundle')
            ->setMockClassName('LiipMock'.$bundleName)
            ->disableOriginalConstructor()
            ->getMock();
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($this->getFixturePath()));

        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->any())
            ->method('getBundle')
            ->will($this->returnValue(array($bundle)));

        return $kernel;
    }

    protected function getFixturePath()
    {
        return __DIR__ . '/../Fixtures';
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::__construct
     * @covers Liip\ThemeBundle\Locator\FileLocator::setCurrentTheme
     */
    public function testConstructor()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     */
    public function testLocate()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/views/template', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/themes/foo/template', $file);
    }

    /**
     * @contain Liip\ThemeBundle\Locator\FileLocator::locate
     */
    public function testLocateActiveThemeUpdate()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocatorFake($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $this->assertEquals('foo', $fileLocator->lastTheme);
        $activeTheme->setName('bar');
        $fileLocator->locate('Resources/themes/foo/template', $this->getFixturePath(), true);
        $this->assertEquals('bar', $fileLocator->lastTheme);
    }

    /**
     * This verifies that the default view gets used if the currently active
     * one doesn't contain a matching file.
     *
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     */
    public function testLocateViewFallback()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/views/defaultTemplate', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/views/defaultTemplate', $file);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     */
    public function testLocateAllFiles() {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foobar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $expectedFiles = array(
            $this->getFixturePath().'/Resources/themes/foobar/template',
            $this->getFixturePath().'/Resources/views/template',
        );

        $files = $fileLocator->locate('@ThemeBundle/Resources/views/template', $this->getFixturePath(), false);
        $this->assertEquals($expectedFiles, $files);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     */
    public function testLocateParentDelegation()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('Resources/themes/foo/template', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/themes/foo/template', $file);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     */
    public function testLocateRootDirectory()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/views/rootTemplate', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/themes/foo/LiipMockLocateRootDirectory/views/rootTemplate', $file);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     */
    public function testLocateOverrideDirectory()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/views/override', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/LiipMockLocateOverrideDirectory/views/override', $file);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     * @expectedException RuntimeException
     */
    public function testLocateInvalidCharacter()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/../views/template', $this->getFixturePath(), true);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     * @expectedException RuntimeException
     */
    public function testLocateNoResource()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/bogus', $this->getFixturePath(), true);
    }

    /**
     * @covers Liip\ThemeBundle\Locator\FileLocator::locate
     * @covers Liip\ThemeBundle\Locator\FileLocator::locateResource
     * @expectedException InvalidArgumentException
     */
    public function testLocateNotFound()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/nonExistant', $this->getFixturePath(), true);
    }
}
