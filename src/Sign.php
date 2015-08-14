<?php
namespace Epay;

use Epay\Exceptions\Certificate as Exceptions;

/**
 * Класс подписи.
 */
class Sign
{
    /**
     * Путь к файлу с публичным ключом.
     *
     * @var null|string
     */
    protected $publicKeyPath;

    /**
     * Путь к файлу с приватным ключом.
     *
     * @var null|string
     */
    protected $privateKeyPath;

    /**
     * Пароль от приватного ключа.
     *
     * @var null|string
     */
    protected $privateKeyPassword;

    /**
     * Флаг инверсии результата.
     *
     * @var boolean
     */
    protected $invertResult = false;

    /**
     * Требуется ли расшифровка приватного ключа.
     *
     * @var boolean
     */
    protected $privateKeyIsEncrypted = true;

    /**
     * Приватный ключ.
     *
     * @var string
     */
    protected $privateKey;

    /**
     * Публичный ключ.
     *
     * @var string
     */
    protected $publicKey;

    /**
     * Конструктор.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->publicKeyPath         = isset($config['PUBLIC_KEY_FN']) ? $config['PUBLIC_KEY_FN'] : null;
        $this->privateKeyPath        = isset($config['PRIVATE_KEY_FN']) ? $config['PRIVATE_KEY_FN'] : null;
        $this->privateKeyPassword    = isset($config['PRIVATE_KEY_PASS']) ? $config['PRIVATE_KEY_PASS'] : null;
        $this->privateKeyIsEncrypted = isset($config['PRIVATE_KEY_ENCRYPTED']) && $config['PRIVATE_KEY_ENCRYPTED'] == 1;
    }

    /**
     * Устанавливает флаг инверсии результата.
     *
     * @param  boolean    $invert
     * @return \Epay\Sign
     */
    public function setInvert($invert)
    {
        $this->invertResult = (bool) $invert;

        return $this;
    }

    /**
     * Возвращает подписанные сертификаторм данные.
     *
     * @param  string                  $data
     * @return boolean|string
     * @throws Exceptions\FileNotFound
     */
    public function sign($data)
    {
        $privateKey = $this->loadPrivateKey($this->privateKeyPath, $this->privateKeyPassword);

        if (false !== $privateKey) {
            $result = '';

            openssl_sign($data, $result, $privateKey);

            if ($this->invertResult === true) {
                $result = strrev($result);
            }

            return $result;
        }

        return false;
    }

    /**
     * Возвращает подписанные сертификатом данные, закодированные в base64.
     *
     * @param  string         $data
     * @return boolean|string
     */
    public function sign64($data)
    {
        $encoded = $this->sign($data);

        if (false !== $encoded) {
            return base64_encode($encoded);
        }

        return false;
    }

    /**
     * Проверяет подпись, кодированную в base64.
     *
     * @param $data
     * @param $string
     * @return integer
     * @throws Exceptions\FileNotFound
     */
    public function checkSign64($data, $string)
    {
        return $this->checkSign($data, base64_decode($string));
    }

    /**
     * Проверяет подпись.
     *
     * @param $data
     * @param $string
     * @return integer
     * @throws Exceptions\CertificateDecryptError
     * @throws Exceptions\CertificatePasswordError
     * @throws Exceptions\CertificateReadError
     * @throws Exceptions\FileNotFound
     * @throws Exceptions\UnknownError
     */
    public function checkSign($data, $string)
    {
        if (!is_readable($this->publicKeyPath)) {
            throw new Exceptions\FileNotFound();
        }

        if ($this->invertResult === true) {
            $string = strrev($string);
        }

        $publicKey = $this->loadPublicKey();
        $result    = openssl_verify($data, $string, $publicKey);

        $this->validateErrorString(openssl_error_string());

        return $result;
    }

    /**
     * Читает публичный ключ из файла.
     *
     * @return boolean|resource|string
     * @throws Exceptions\CertificateDecryptError
     * @throws Exceptions\CertificatePasswordError
     * @throws Exceptions\CertificateReadError
     * @throws Exceptions\FileNotFound
     * @throws Exceptions\UnknownError
     */
    public function loadPublicKey()
    {
        if (!is_readable($this->publicKeyPath)) {
            throw new Exceptions\FileNotFound();
        }

        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));

        $this->validateErrorString(openssl_error_string());

        if (!is_resource($publicKey)) {
            return false;
        }

        $this->publicKey = $publicKey;

        return $this->publicKey;
    }

    /**
     * Читает приватный ключ из файла.
     *
     * @return boolean|resource
     * @throws Exceptions\CertificateDecryptError
     * @throws Exceptions\CertificatePasswordError
     * @throws Exceptions\CertificateReadError
     * @throws Exceptions\FileNotFound
     * @throws Exceptions\UnknownError
     */
    public function loadPrivateKey()
    {
        if (!is_readable($this->privateKeyPath)) {
            throw new Exceptions\FileNotFound();
        }

        if (!$this->privateKeyIsEncrypted) {
            return file_get_contents($this->privateKey);
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath), $this->privateKeyPassword);

        $this->validateErrorString(openssl_error_string());

        if (!is_resource($privateKey)) {
            return false;
        }

        $this->privateKey = $privateKey;

        return $this->privateKey;
    }

    /**
     * Валидирует сообщение об ошибке.
     *
     * @param  string                              $error
     * @throws Exceptions\CertificateDecryptError
     * @throws Exceptions\CertificatePasswordError
     * @throws Exceptions\CertificateReadError
     * @throws Exceptions\UnknownError
     */
    protected function validateErrorString($error)
    {
        if (strlen($error) > 0) {
            switch (true) {
                case strpos($error, 'error:0906D06C') !== false:
                    throw new Exceptions\CertificateReadError();
                    break;

                case strpos($error, 'error:06065064') !== false:
                    throw new Exceptions\CertificateDecryptError();
                    break;

                case strpos($error, 'error:0906A068') !== false:
                    throw new Exceptions\CertificatePasswordError();
                    break;

                default:
                    throw new Exceptions\UnknownError($error);
                    break;
            }
        }
    }
}
