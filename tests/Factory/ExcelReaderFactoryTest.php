<?php

namespace Port\Csv\Tests\Factory;

use Port\Excel\Factory\ExcelReaderFactory;

class ExcelReaderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped();
        }
    }

    public function testGetReader()
    {
        $factory = new ExcelReaderFactory();
        $reader = $factory->getReader(new \SplFileObject(__DIR__.'/../fixtures/data_column_headers.xlsx'));
        $this->assertInstanceOf('\Port\Reader\ExcelReader', $reader);
        $this->assertCount(4, $reader);

        $factory = new ExcelReaderFactory(0);
        $reader = $factory->getReader(new \SplFileObject(__DIR__.'/../fixtures/data_column_headers.xlsx'));
        $this->assertCount(3, $reader);
    }
}