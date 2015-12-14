<?php
namespace Tests;

use Epay\Xml;

/**
 * Тест класса Xml.
 */
class XmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Тестовый объект.
     *
     * @var \Epay\Xml
     */
    protected $xml;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->xml = new Xml();
    }

    /**
     * Тест конструктора.
     * @covers \Epay\Xml::__construct()
     */
    public function testConstruct()
    {
        $reflection = new \ReflectionProperty($this->xml, 'parser');
        $reflection->setAccessible(true);
        $parser = $reflection->getValue($this->xml);

        $this->assertInternalType('resource', $parser);
    }

    /**
     * Тест парсинга xml.
     * @covers \Epay\Xml::parse()
     */
    public function testParse()
    {
        $result = $this->xml->parse('<data attr="attr_value">char_value</data>');

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('DATA_ATTR', $result);
        $this->assertEquals($result['DATA_ATTR'], 'attr_value');
        $this->assertArrayHasKey('DATA_CHARDATA', $result);
        $this->assertEquals($result['DATA_CHARDATA'], 'char_value');
    }

    /**
     * Тест обработки открывающего тега.
     * @covers \Epay\Xml::openTag()
     */
    public function testOpenTag()
    {
        $reflection = new \ReflectionProperty($this->xml, 'parser');
        $reflection->setAccessible(true);
        $parser = $reflection->getValue($this->xml);

        $this->xml->openTag($parser, 'test', array('attribute' => 'value'));

        $reflection = new \ReflectionProperty($this->xml, 'result');
        $reflection->setAccessible(true);
        $result = $reflection->getValue($this->xml);

        $reflection = new \ReflectionProperty($this->xml, 'currentTag');
        $reflection->setAccessible(true);
        $currentTag = $reflection->getValue($this->xml);

        $this->assertEquals('test', $currentTag);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('TAG_test', $result);
        $this->assertArrayHasKey('test_attribute', $result);
        $this->assertEquals('value', $result['test_attribute']);
    }

    /**
     * Тест обработки закрывающего тега.
     * @covers \Epay\Xml::closeTag()
     */
    public function testCloseTag()
    {
        $reflection = new \ReflectionProperty($this->xml, 'parser');
        $reflection->setAccessible(true);
        $parser = $reflection->getValue($this->xml);

        $this->xml->closeTag($parser, 'test');
    }

    /**
     * Тест обработки символьных данных.
     * @covers \Epay\Xml::cdata()
     */
    public function testCdata()
    {
        $reflection = new \ReflectionProperty($this->xml, 'parser');
        $reflection->setAccessible(true);
        $parser = $reflection->getValue($this->xml);

        $this->xml->cdata($parser, 'something');

        $reflection = new \ReflectionProperty($this->xml, 'result');
        $reflection->setAccessible(true);
        $result = $reflection->getValue($this->xml);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('_CHARDATA', $result);
        $this->assertEquals('something', $result['_CHARDATA']);
    }
}
