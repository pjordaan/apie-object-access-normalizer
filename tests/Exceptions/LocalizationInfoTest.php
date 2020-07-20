<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Exceptions;

use PHPUnit\Framework\TestCase;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationInfo;

class LocalizationInfoTest extends TestCase
{
    public function testConstruction()
    {
        $testItem = new LocalizationInfo('error', ['pizza' => 'has anchovy'], 2);
        $this->assertSame('error', $testItem->getMessageString());
        $this->assertEquals(['pizza' => 'has anchovy'], $testItem->getReplacements());
        $this->assertSame(2, $testItem->getAmount());
    }
}
