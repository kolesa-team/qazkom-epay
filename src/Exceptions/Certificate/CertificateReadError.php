<?php
namespace Epay\Exceptions\Certificate;

use Epay\Exceptions\Exception;

/**
 * Исключение ошибки чтения сертификата.
 */
class CertificateReadError extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 1;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Error reading Certificate. Verify Cert type.';
}
