<?php
namespace Epay\Exceptions\Certificate;

use Epay\Exceptions\Exception;

/**
 * Исключение ошибки расшифровки сертификата.
 */
class CertificateDecryptError extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 2;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Bad decrypt. Verify your Cert password or Cert type.';
}
