<?php
namespace Epay\Exceptions\Certificate;

use Epay\Exceptions\Exception;

/**
 * Исключение ошибки пароля сертификата.
 */
class CertificatePasswordError extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 3;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Bad password read. Maybe empty password.';
}
