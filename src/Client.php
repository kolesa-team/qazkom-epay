<?php
namespace Epay;

use Epay\Exceptions\Amount;
use Epay\Exceptions\Certificate;
use Epay\Exceptions\Common;
use Epay\Exceptions\Currency;
use Epay\Exceptions\Order;

/**
 * Клиентский класс.
 */
class Client
{
    /**
     * Доступные валюты.
     *
     * @var array
     */
    protected $currencyEnum = array(
        840 => 'USD',
        398 => 'KZT',
    );

    /**
     * Конфигурация.
     *
     * @var array
     */
    protected $config = array();

    /**
     * Конструктор.
     *
     * @param  string                $configPath
     * @throws Common\ConfigNotFound
     */
    public function __construct($configPath)
    {
        if (!is_readable($configPath)) {
            throw new Common\ConfigNotFound();
        }

        $this->config = parse_ini_file($configPath);
    }

    /**
     * Возвращает ID валюты.
     *
     * @param  string       $key
     * @return null|integer
     */
    public function getCurrencyId($key = 'KZT')
    {
        $types = array_flip($this->currencyEnum);

        return isset($types[$key]) ? $types[$key] : null;
    }

    /**
     * Возвращает содержимое XML-файла с подстановкой переменных из массива.
     *
     * @param $filename
     * @param  array               $transformations
     * @return mixed
     * @throws Common\FileNotFound
     */
    public function processXml($filename, array $transformations = array())
    {
        if (!is_readable($filename)) {
            throw new Common\FileNotFound;
        }

        $content = file_get_contents($filename);
        $values  = array_values($transommonformations);
        $keys    = array_map(function ($item) {
            return '[' . $item . ']';
        }, array_keys($transformations));

        return str_replace($keys, $values, $content);
    }

    /**
     * Разбирает XML на массив значений.
     *
     * @param $xml
     * @param $tag
     * @return array
     */
    public function splitSign($xml, $tag)
    {
        $result            = array();
        $letterStart       = stristr($xml, '<' . $tag);
        $signStart         = stristr($xml, '<' . $tag . '_SIGN');
        $signEnd           = stristr($xml, '</' . $tag . '_SIGN');
        $documentEnd       = stristr($signEnd, '>');
        $result['LETTER']  = substr($letterStart, 0, -1 * strlen($signStart));
        $result['SIGN']    = substr($signStart, 0, -1 * strlen($documentEnd) + 1);
        $rawSignStart      = stristr($result['SIGN'], '>');
        $rawSignEnd        = stristr($rawSignStart, '</');
        $result['RAWSIGN'] = substr($rawSignStart, 1, -1 * strlen($rawSignEnd));

        return $result;
    }

    /**
     * Создаёт подписанный XML-запрос.
     *
     * @param  integer                  $orderId
     * @param  integer                  $currencyCode
     * @param  integer                  $amount
     * @param  boolean                  $base64encode
     * @return string
     * @throws Amount\EmptyAmount
     * @throws Certificate\UnknownError
     * @throws Common\FileNotFound
     * @throws Currency\EmptyId
     * @throws Currency\InvalidId
     * @throws Order\EmptyId
     * @throws Order\NotNumeric
     * @throws Order\NullId
     */
    public function processRequest($orderId, $currencyCode, $amount, $base64encode = true)
    {
        switch (true) {
            case strlen($orderId) < 1:
                throw new Order\EmptyId();
                break;

            case !is_numeric($orderId):
                throw new Order\NotNumeric();
                break;

            case $orderId < 1:
                throw new Order\NullId();
                break;

            case empty($currencyCode):
                throw new Currency\EmptyId();
                break;

            case !array_key_exists($currencyCode, $this->currencyEnum):
                throw new Currency\InvalidId();
                break;

            case $amount == 0:
                throw new Amount\EmptyAmount();
                break;

            case strlen($this->config['PRIVATE_KEY_FN']) == 0:
                throw new Certificate\UnknownError('Path for Private key not found');
                break;

            case strlen($this->config['XML_TEMPLATE_FN']) == 0:
                throw new Certificate\UnknownError('Path for Private key not found');
                break;
        }

        $request = array(
            'MERCHANT_CERTIFICATE_ID' => $this->config['MERCHANT_CERTIFICATE_ID'],
            'MERCHANT_NAME'           => $this->config['MERCHANT_NAME'],
            'ORDER_ID'                => sprintf('%06d', $orderId),
            'CURRENCY'                => $currencyCode,
            'MERCHANT_ID'             => $this->config['MERCHANT_ID'],
            'AMOUNT'                  => $amount,
        );

        $request = $this->processXml($this->config['XML_TEMPLATE_FN'], $request);
        $sign = new Sign($this->config);
        $sign->setInvert(true);

        $xml = sprintf(
            '<document>%s<merchant_sign type="RSA">%s</merchant_sign></document>',
            $request,
            $sign->sign64($request)
        );

        if ($base64encode) {
            $xml = base64_encode($xml);
        }

        return $xml;
    }

    /**
     * Проверят ответ от банка.
     *
     * @param $response
     * @return array|string
     */
    public function processResponse($response)
    {
        $parser = new Xml();
        $result = $parser->parse($response);

        if (in_array("ERROR", $result)) {
            return $result;
        }

        if (in_array("DOCUMENT", $result)) {
            $sign = new Sign($this->config);
            $sign->setInvert(true);

            $data  = $this->splitSign($response, 'BANK');
            $check = $sign->checkSign64($response, 'BANK');

            switch ($check) {
                case 1:
                    $data['CHECKRESULT'] = '[SIGN_GOOD]';
                    break;

                case 0:
                    $data['CHECKRESULT'] = '[SIGN_BAD]';
                    break;

                default:
                    $data['CHECKRESULT'] = '[SIGN_CHECK_ERROR]: '; // Как сюда засунуть результат? . $kkb->estatus;
                    break;
            }

            return array_merge($result, $data);
        }

        return "[XML_DOCUMENT_UNKNOWN_TYPE]";
    }
}
