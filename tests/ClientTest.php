<?php
namespace Tests;

use Epay\Client;

/**
 * Тест класса Client.
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Позитивный тест конструктора.
     * 
     * @covers \Epay\Client::__construct()
     * @dataProvider provideTestConstructPositive
     * @param mixed $config
     * @param array $expected
     */
    public function testConstructPositive($config, $expected)
    {
        $client = new Client($config);

        $reflection = new \ReflectionProperty($client, 'config');
        $reflection->setAccessible(true);

        $actual = $reflection->getValue($client);

        $this->assertInternalType('array', $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Негативный тест конструктора.
     * 
     * @covers \Epay\Client::__construct()
     * @expectedException \Epay\Exceptions\Common\ConfigNotFound
     */
    public function testConstructNegative()
    {
        $client = new Client('invalid-type');
    }

    /**
     * Тест получения id валюты по ключу.
     *
     * @covers \Epay\Client::getCurrencyId()
     * @dataProvider provideTestGetCurrencyId
     * @param string       $key
     * @param integer|null $expected
     */
    public function testGetCurrencyId($key, $expected)
    {
        $client = new Client([]);

        $this->assertEquals($expected, $client->getCurrencyId($key));
    }

    /**
     * @covers \Epay\Client::splitSign()
     */
    public function testSplitSign()
    {
        $client = new Client([]);

        $result = $client->splitSign('
        <document>
            <bank name="Kazkommertsbank JSC">
                <merchant id="90002102">
                    <command type="reverse"/>
                    <payment reference="" approval_code="" orderid="" amount="" currency_code=""/>
                    <reason>Only for reverse</reason>
                </merchant>
                <merchant_sign type="RSA" cert_id="">
                    AGKJHSGHGIYTEG&DT*STT&IGHGFLKJHSGLKJHMNBFLKRSJHSKJFHKJHfldsflkjskksldjfl
                </merchant_sign>
                <response code="00" message="Approved">
            </bank>
            <bank_sign type="RSA" cert_id="">
                p25i1rUH7StnhOfnkHSOHguuPMePaGXtiPGEOrJE4bof1gFVH19mhDyHjfWa6OeJ80fidyvVf1X4
                ewyP0yG4GxJSl0VyXz7+PNLsbs1lJe42d1fixvozhJSSYN6fAxMN8hhDht6S81YK3GbDTE7GH498
                pU9HGuGAoDVjB+NtrHk=
            </bank_sign>
        </document>', 'BANK');

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('LETTER', $result);
        $this->assertArrayHasKey('SIGN', $result);
        $this->assertArrayHasKey('RAWSIGN', $result);
    }

    /**
     * @covers \Epay\Client::processXml()
     * @expectedException \Epay\Exceptions\Common\FileNotFound
     */
    public function testProcessXml()
    {
        $filename = ROOT_DIR . '/tests/data/template.xml';
        $trans    = array(
            "MERCHANT_CERTIFICATE_ID" => 'merchantCertificatId',
            "MERCHANT_NAME"           => 'merchantName',
            "ORDER_ID"                => "orderId",
            "AMOUNT"                  => "amount",
            "CURRENCY"                => "currency",
            "MERCHANT_ID"             => "merchantId",
        );
        $client   = new Client([]);
        $result   = $client->processXml($filename, $trans);

        $this->assertInternalType('string', $result);
        $this->assertFalse(strpos($result, "[MERCHANT_CERTIFICATE_ID]"));
        $this->assertFalse(strpos($result, "[MERCHANT_NAME]"));
        $this->assertFalse(strpos($result, "[ORDER_ID]"));
        $this->assertFalse(strpos($result, "[AMOUNT]"));
        $this->assertFalse(strpos($result, "[CURRENCY]"));
        $this->assertFalse(strpos($result, "[MERCHANT_ID]"));

        $client->processXml('non-existent-file.xml', $trans);
    }

    /**
     * Дата-провайдер для позитивного теста конструктора.
     * 
     * @return array
     */
    public function provideTestConstructPositive()
    {
        $mock = $this->getMock(\stdClass::class, ['toArray']);
        $mock->method('toArray')->willReturn(['key' => 'value']);

        return [
             [ROOT_DIR . '/tests/data/config.ini', ['key' => 'value']],
            [['key' => 'value'], ['key' => 'value']],
            [$mock, ['key' => 'value']],
        ];
    }

    /**
     * Дата-провайдер для теста получения id валюты по ключу.
     *
     * @return array
     */
    public function provideTestGetCurrencyId()
    {
        return [
            ['KZT', 398],
            ['USD', 840],
            ['EUR', null],
        ];
    }
}