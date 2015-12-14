<?php
namespace Tests;

use Epay\Exceptions\Exception;
use Epay\Sign;

/**
 * Тест класса Sign.
 */
class SignTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Тестовый объект.
     *
     * @var \Epay\Sign
     */
    protected $sign;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->sign = new Sign(array(
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
    }

    /**
     * @covers \Epay\Sign::__construct()
     */
    public function testConstruct()
    {
        $sign = new Sign(array());
        $this->assertInstanceOf('\Epay\Sign', $sign);

        foreach (array('publicKeyPath', 'privateKeyPath', 'privateKeyPassword') as $property) {
            $reflection = new \ReflectionProperty($sign, $property);
            $reflection->setAccessible(true);

            $this->assertEquals(null, $reflection->getValue($sign));
        }

        $reflection = new \ReflectionProperty($sign, 'privateKeyIsEncrypted');
        $reflection->setAccessible(true);

        $this->assertFalse($reflection->getValue($sign));
    }

    /**
     * Тест установки флага инверсии.
     * @covers \Epay\Sign::setInvert()
     */
    public function testSetInvert()
    {
        $reflection = new \ReflectionProperty($this->sign, 'invertResult');
        $reflection->setAccessible(true);

        $this->sign->setInvert(true);
        $this->assertTrue($reflection->getValue($this->sign));

        $this->sign->setInvert(false);
        $this->assertFalse($reflection->getValue($this->sign));
    }

    /**
     * @dataProvider provideTestSign
     * @covers \Epay\Sign::sign()
     * @param boolean $invert
     * @param string $data
     */
    public function testSign($invert, $data)
    {
        $result = $this->sign->setInvert($invert)->sign($data);

        $this->assertNotFalse($result);
    }

    /**
     * @dataProvider provideTestSign
     * @covers \Epay\Sign::sign64()
     * @param boolean $invert
     * @param string $data
     */
    public function testSign64($invert, $data)
    {
        $result = $this->sign->setInvert($invert)->sign64($data);

        $this->assertNotFalse($result);
    }

    /**
     * @covers \Epay\Sign::checkSign64()
     */
    public function testCheckSign64()
    {
        $data   = '<bank name="Kazkommertsbank JSC">
      <customer name="John Cardholder" mail="klient@mymail.com" phone="223322">
         <merchant cert_id="7269C18D00010000005E" name="Shop Name">
            <order order_id="000282" amount="3100" currency="398">
               <department merchant_id="90028101" amount="1300" rl=ASDFG" />
            </order>
         </merchant>
         <merchant_sign type="RSA/">
      </customer>
      <customer_sign type="SSL">
         4817C411000100000084
         </customer_sign>
      <results timestamp="2006-11-22 12:20:30 ">
         <payment merchant_id="90050801" amount="320.50" reference="109600746891" approval_code="730190" response_code="00" Secure="No" card_bin="KAZ" c_hash="6A2D7673A8EEF25A2C33D67CB5AAD091"/>
      </results>
    </bank>';
        $sign = 'JI3RZMEvexNlDmKsOQhe0pzHuKijnbhvnLu99qh7h+Ju8HvSfGNbEJxXUL58M94tXvu7w0BXSY7M' .
      'HePGqz32JuMLAncuzyMwq845linW/sH/WvbZ+6SSYfxDMnvgX0S/pKxbhSXs7lGVBngXOwq7Bhsk' .
      '8GcDUkWAM5UAsKpEKoI=';

        $result = $this->sign->setInvert(true)->checkSign64($data, $sign);

        $this->assertEquals(1, $result);
    }

    /**
     * @covers \Epay\Sign::validateErrorString()
     * @dataProvider provideTestValidateErrorString
     * @param string $string
     * @param string $exception
     */
    public function testValidateErrorString($string, $exception)
    {
        $reflection = new \ReflectionMethod($this->sign, 'validateErrorString');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->sign, $string);
        } catch (Exception $e) {
            $this->assertInstanceOf($exception, $e);
        }
    }

    /**
     * Дата-провайдер для теста подписи.
     *
     * @return array
     */
    public function provideTestSign()
    {
        return array(
            array(false, 'test data'),
            array(true,  'test data'),
        );
    }

    /**
     * Дата-провайдер для теста проверки сообщения об ошибке.
     *
     * @return array
     */
    public function provideTestValidateErrorString()
    {
        return array(
            array("error:0906D06C", "\\Epay\\Exceptions\\Certificate\\CertificateReadError"),
            array("error:06065064", "\\Epay\\Exceptions\\Certificate\\CertificateDecryptError"),
            array("error:0906A068", "\\Epay\\Exceptions\\Certificate\\CertificatePasswordError"),
            array("something-else", "\\Epay\\Exceptions\\Certificate\\UnknownError"),
            array("", ""),
        );
    }
}