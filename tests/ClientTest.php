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
        $client = new Client(array());

        $this->assertEquals($expected, $client->getCurrencyId($key));
    }

    /**
     * @covers \Epay\Client::splitSign()
     */
    public function testSplitSign()
    {
        $client = new Client(array());

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
        $client   = new Client(array());
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
     * @covers \Epay\Client::processRequest()
     * @dataProvider provideTestProcessRequestNegative
     * @param array  $config
     * @param mixed  $orderId
     * @param mixed  $currencyCode
     * @param mixed  $amount
     * @param string $expectedClassName
     */
    public function testProcessRequestNegative($config, $orderId, $currencyCode, $amount, $expectedClassName)
    {
        $client = new Client($config);

        try {
            $xml = $client->processRequest($orderId, $currencyCode, $amount);
        } catch (\Exception $e) {
            $this->assertInstanceOf($expectedClassName, $e);
        }
    }

    /**
     * @covers \Epay\Client::processRequest()
     * @throws \Epay\Exceptions\Amount\EmptyAmount
     * @throws \Epay\Exceptions\Certificate\UnknownError
     * @throws \Epay\Exceptions\Currency\EmptyId
     * @throws \Epay\Exceptions\Currency\InvalidId
     * @throws \Epay\Exceptions\Order\EmptyId
     * @throws \Epay\Exceptions\Order\NotNumeric
     * @throws \Epay\Exceptions\Order\NullId
     */
    public function testProcessRequestPositive()
    {
        $client = new Client(array(
            'MERCHANT_CERTIFICATE_ID' => '00c182b189',
            'MERCHANT_NAME'           => 'Demo Shop',
            'PRIVATE_KEY_FN'          => ROOT_DIR . '/tests/data/cert.prv',
            'PRIVATE_KEY_PASS'        => 'nissan',
            'PRIVATE_KEY_ENCRYPTED'   => 1,
            'XML_TEMPLATE_FN'         => ROOT_DIR . '/tests/data/template.xml',
            'XML_TEMPLATE_CONFIRM_FN' => ROOT_DIR . '/tests/data/template_confirm.xml',
            'PUBLIC_KEY_FN'           => ROOT_DIR . '/tests/data/kkbca_test.pub',
            'MERCHANT_ID'             => '92061101',
        ));

        $xml = $client->processRequest(1, $client->getCurrencyId('KZT'), 1000);

        $this->assertInternalType('string', $xml);

        $xml = $client->processRequest(1, $client->getCurrencyId('KZT'), 1000, true);

        $this->assertInternalType('string', $xml);
    }

    /**
     * @covers \Epay\Client::processConfirmation()
     * @dataProvider provideTestProcessConfirmationNegative
     * @param array  $config
     * @param mixed  $reference
     * @param mixed  $approvalCode
     * @param mixed  $orderId
     * @param mixed  $currencyCode
     * @param mixed  $amount
     * @param string $expectedClassName
     */
    public function testProcessConfirmationNegative(
        $config,
        $reference,
        $approvalCode,
        $orderId,
        $currencyCode,
        $amount,
        $expectedClassName
    ) {
        $client = new Client($config);

        try {
            $xml = $client->processConfirmation($reference, $approvalCode, $orderId, $currencyCode, $amount);
        } catch (\Exception $e) {
            $this->assertInstanceOf($expectedClassName, $e);
        }
    }

    /**
     * @covers \Epay\Client::processConfirmation()
     * @throws \Epay\Exceptions\Amount\EmptyAmount
     * @throws \Epay\Exceptions\Certificate\UnknownError
     * @throws \Epay\Exceptions\Order\EmptyId
     * @throws \Epay\Exceptions\Order\NotNumeric
     * @throws \Epay\Exceptions\Order\NullId
     */
    public function testProcessConfirmationPositive()
    {
        $client = new Client(array(
            'MERCHANT_CERTIFICATE_ID' => '00c182b189',
            'MERCHANT_NAME'           => 'Demo Shop',
            'PRIVATE_KEY_FN'          => ROOT_DIR . '/tests/data/cert.prv',
            'PRIVATE_KEY_PASS'        => 'nissan',
            'PRIVATE_KEY_ENCRYPTED'   => 1,
            'XML_TEMPLATE_FN'         => ROOT_DIR . '/tests/data/template.xml',
            'XML_TEMPLATE_CONFIRM_FN' => ROOT_DIR . '/tests/data/template_confirm.xml',
            'PUBLIC_KEY_FN'           => ROOT_DIR . '/tests/data/kkbca_test.pub',
            'MERCHANT_ID'             => '92061101',
        ));

        $xml = $client->processConfirmation('reference', 'code', 1, $client->getCurrencyId('KZT'), 1000);

        $this->assertInternalType('string', $xml);

        $xml = $client->processConfirmation('reference', 'code', 1, $client->getCurrencyId('KZT'), 1000, true);

        $this->assertInternalType('string', $xml);
    }

    public function testProcessResponse()
    {
        $client = new Client(array(
            'MERCHANT_CERTIFICATE_ID' => '00c182b189',
            'MERCHANT_NAME'           => 'Demo Shop',
            'PRIVATE_KEY_FN'          => ROOT_DIR . '/tests/data/cert.prv',
            'PRIVATE_KEY_PASS'        => 'nissan',
            'PRIVATE_KEY_ENCRYPTED'   => 1,
            'XML_TEMPLATE_FN'         => ROOT_DIR . '/tests/data/template.xml',
            'XML_TEMPLATE_CONFIRM_FN' => ROOT_DIR . '/tests/data/template_confirm.xml',
            'PUBLIC_KEY_FN'           => ROOT_DIR . '/tests/data/kkbca_test.pub',
            'MERCHANT_ID'             => '92061101',
        ));
        $response = '
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
        <response code="00" message="Approved"/>
    </bank>
    <bank_sign type="RSA" cert_id="">
        p25i1rUH7StnhOfnkHSOHguuPMePaGXtiPGEOrJE4bof1gFVH19mhDyHjfWa6OeJ80fidyvVf1X4
        ewyP0yG4GxJSl0VyXz7+PNLsbs1lJe42d1fixvozhJSSYN6fAxMN8hhDht6S81YK3GbDTE7GH498
        pU9HGuGAoDVjB+NtrHk=
    </bank_sign>
</document>
';

        $result = $client->processResponse($response);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('CHECKRESULT', $result);
        $this->assertEquals('[SIGN_GOOD]', $result['CHECKRESULT']);
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

    /**
     * Дата-провайдер для негативного теста создания запроса.
     *
     * @return array
     */
    public function provideTestProcessRequestNegative()
    {
        $config = array(
            'MERCHANT_CERTIFICATE_ID' => '00c182b189',
            'MERCHANT_NAME'           => 'Demo Shop',
            'PRIVATE_KEY_FN'          => ROOT_DIR . '/tests/data/cert.prv',
            'PRIVATE_KEY_PASS'        => 'nissan',
            'PRIVATE_KEY_ENCRYPTED'   => 1,
            'XML_TEMPLATE_FN'         => ROOT_DIR . '/tests/data/template.xml',
            'XML_TEMPLATE_CONFIRM_FN' => ROOT_DIR . '/tests/data/template_confirm.xml',
            'PUBLIC_KEY_FN'           => ROOT_DIR . '/tests/data/kkbca_test.pub',
            'MERCHANT_ID'             => '92061101',
        );

        return array(
            array(
                $config,
                '',
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\EmptyId',
            ),
            array(
                $config,
                'not-number',
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\NotNumeric',
            ),
            array(
                $config,
                0,
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\NullId',
            ),
            array(
                $config,
                1,
                '',
                1000,
                '\\Epay\\Exceptions\\Currency\\EmptyId',
            ),
            array(
                $config,
                1,
                'unknown-currency',
                1000,
                '\\Epay\\Exceptions\\Currency\\InvalidId',
            ),
            array(
                $config,
                1,
                398,
                0,
                '\\Epay\\Exceptions\\Amount\\EmptyAmount',
            ),
            array(
                array_merge($config, array('XML_TEMPLATE_FN' => '')),
                1,
                398,
                100,
                '\\Epay\\Exceptions\\Certificate\\UnknownError',
            ),
            array(
                array_merge($config, array('PRIVATE_KEY_FN' => '')),
                1,
                398,
                100,
                '\\Epay\\Exceptions\\Certificate\\UnknownError',
            ),
        );
    }

    /**
     * Дата-провайдер для негативного теста создания подтверждения.
     *
     * @return array
     */
    public function provideTestProcessConfirmationNegative()
    {
        $config = array(
            'MERCHANT_CERTIFICATE_ID' => '00c182b189',
            'MERCHANT_NAME'           => 'Demo Shop',
            'PRIVATE_KEY_FN'          => ROOT_DIR . '/tests/data/cert.prv',
            'PRIVATE_KEY_PASS'        => 'nissan',
            'PRIVATE_KEY_ENCRYPTED'   => 1,
            'XML_TEMPLATE_FN'         => ROOT_DIR . '/tests/data/template.xml',
            'XML_TEMPLATE_CONFIRM_FN' => ROOT_DIR . '/tests/data/template_confirm.xml',
            'PUBLIC_KEY_FN'           => ROOT_DIR . '/tests/data/kkbca_test.pub',
            'MERCHANT_ID'             => '92061101',
        );

        return array(
            array(
                $config,
                'reference',
                'code',
                '',
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\EmptyId',
            ),
            array(
                $config,
                'reference',
                'code',
                'orderId',
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\NotNumeric',
            ),
            array(
                $config,
                'reference',
                'code',
                0,
                398,
                1000,
                '\\Epay\\Exceptions\\Order\\NullId',
            ),
            array(
                $config,
                'reference',
                'code',
                1,
                398,
                0,
                '\\Epay\\Exceptions\\Amount\\EmptyAmount',
            ),
            array(
                array_merge($config, array('PRIVATE_KEY_FN' => '')),
                'reference',
                'code',
                1,
                398,
                1000,
                '\\Epay\\Exceptions\\Certificate\\UnknownError',
            ),
            array(
                array_merge($config, array('XML_TEMPLATE_CONFIRM_FN' => '')),
                'reference',
                'code',
                1,
                398,
                1000,
                '\\Epay\\Exceptions\\Certificate\\UnknownError',
            ),
        );
    }
}