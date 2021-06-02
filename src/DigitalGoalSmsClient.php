<?php

namespace DigitalGoalSmsClient\DigitalGoalSmsClient;

use DigitalGoalSmsClient\DigitalGoalSmsClient\Exception\ApiExeption;
use DigitalGoalSmsClient\DigitalGoalSmsClient\Exception\AuthExeption;
use DigitalGoalSmsClient\DigitalGoalSmsClient\Exception\SendExeption;

class DigitalGoalSmsClient
{

    /**
     * @var string
     */
    private $user = '';

    /**
     * @var string
     */
    private $password = '';

    /**
     * @var string
     */
    private $smsNumber = '';

    /**
     * @var string
     */
    private $smsText = '';

    /**
     * @var int
     */
    private $smsMandatorId = 0;

    /**
     * @var string
     */
    private $smsMandatorSystem = '';

    /**
     * @var \libphonenumber\PhoneNumberUtil
     */
    private $libPhoneNumber = null;

    public function __construct($user, $password)
    {

        $this->setUser($user);
        $this->setPassword($password);
        $this->setLibPhoneNumber(\libphonenumber\PhoneNumberUtil::getInstance());

    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return DigitalGoalSmsClient
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return DigitalGoalSmsClient
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getSmsNumber()
    {
        return $this->smsNumber;
    }

    /**
     * @param string $smsNumber
     * @return DigitalGoalSmsClient
     */
    public function setSmsNumber($smsNumber)
    {
        $smsNumber = $this->getLibPhoneNumber()->parse($smsNumber);
        $this->smsNumber = $this->getLibPhoneNumber()->format(
            $smsNumber,
            \libphonenumber\PhoneNumberFormat::INTERNATIONAL
        );
        return $this;
    }

    /**
     * @return string
     */
    public function getSmsText()
    {
        return $this->smsText;
    }

    /**
     * @param string $smsText
     * @return DigitalGoalSmsClient
     */
    public function setSmsText($smsText)
    {
        $this->smsText = $smsText;
        return $this;
    }

    /**
     * @return int
     */
    public function getSmsMandatorId(): int
    {
        return $this->smsMandatorId;
    }

    /**
     * @param int $smsMandatorId
     * @return DigitalGoalSmsClient
     */
    public function setSmsMandatorId(int $smsMandatorId): DigitalGoalSmsClient
    {
        $this->smsMandatorId = $smsMandatorId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSmsMandatorSystem(): string
    {
        return $this->smsMandatorSystem;
    }

    /**
     * @param string $smsMandatorSystem
     * @return DigitalGoalSmsClient
     */
    public function setSmsMandatorSystem(string $smsMandatorSystem): DigitalGoalSmsClient
    {
        $this->smsMandatorSystem = $smsMandatorSystem;
        return $this;
    }


    /**
     * @return \libphonenumber\PhoneNumberUtil
     */
    public function getLibPhoneNumber()
    {
        return $this->libPhoneNumber;
    }

    /**
     * @param \libphonenumber\PhoneNumberUtil $libPhoneNumber
     * @return DigitalGoalSmsClient
     */
    public function setLibPhoneNumber($libPhoneNumber)
    {
        $this->libPhoneNumber = $libPhoneNumber;
        return $this;
    }

    /**
     * @return mixed
     * @throws AuthExeption
     */
    public function getToken()
    {

        $postdata = http_build_query(
            [
                'user_email' => $this->getUser(),
                'user_password' => $this->getPassword()
            ]
        );

        $opts = [
            'http' =>
                [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $postdata
                ]
        ];

        $context = stream_context_create($opts);

        $response = file_get_contents(
            'https://sms-middleware.digitalgoal.de/auth',
            false,
            $context
        );

        $response = json_decode($response, true);

        if (
            !isset($response['items']) ||
            !isset($response['items'][0]) ||
            !isset($response['items'][0]['token_hash'])
        ) {
            throw new AuthExeption();
        }

        return $response['items'][0]['token_hash'];

    }

    /**
     * @return mixed
     * @throws SendExeption
     */
    public function sendToMiddleware()
    {

        $postdata = http_build_query(
            [
                'sms_to' => $this->getSmsNumber(),
                'sms_text' => $this->getSmsText(),
                'sms_mandator_sms_id' => $this->getSmsMandatorId(),
                'sms_mandator_system' => $this->getSmsMandatorSystem()
            ]
        );

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization' => $this->getToken()
                ],
                'content' => $postdata
            ]
        ];


        $context = stream_context_create($opts);
        $response = file_get_contents(
            'https://sms-middleware.digitalgoal.de/sms',
            false,
            $context
        );

        if (
            !isset($response['items']) ||
            !isset($response['items'][0]) ||
            !isset($response['items'][0]['sms_id'])
        ) {
            throw new SendExeption();
        }

        return $response['items'][0]['sms_id'];

    }

    /**
     * @return mixed
     * @throws ApiExeption
     */
    public function getSms()
    {

        $opts = [
            'http' => [
                'header' => [
                    'Authorization' => $this->getToken()
                ],
            ]
        ];


        $context = stream_context_create($opts);
        $response = file_get_contents(
            'https://sms-middleware.digitalgoal.de/sms',
            false,
            $context
        );

        $response = json_decode($response, true);

        if (
            !isset($response['status']) ||
            $response['status'] !== 'success'
        ) {
            throw new ApiExeption('api request failed');
        }

        return $response['items'];

    }

    /**
     * @param $middlewareSmsId
     * @return mixed
     * @throws ApiExeption
     */
    public function getSmsById($middlewareSmsId)
    {

        $opts = [
            'http' => [
                'header' => [
                    'Authorization' => $this->getToken()
                ],
            ]
        ];


        $context = stream_context_create($opts);
        $response = file_get_contents(
            'https://sms-middleware.digitalgoal.de/sms/' . $middlewareSmsId,
            false,
            $context
        );

        $response = json_decode($response, true);

        if (
            !isset($response['items']) ||
            !isset($response['items'][0])
        ) {
            throw new ApiExeption('could not get sms with id ' . $middlewareSmsId);
        }

        return $response['items'][0];

    }

    /**
     * @param $middlewareSmsId
     * @param $smsStatus
     * @return mixed
     * @throws ApiExeption
     */
    public function updateSmsById($middlewareSmsId, $smsStatus)
    {

        $opts = [
            'http' => [
                'method' => 'PUT',
                'header' => [
                    'Authorization' => $this->getToken()
                ]
            ]
        ];


        $context = stream_context_create($opts);
        $response = file_get_contents(
            'https://sms-middleware.digitalgoal.de/sms/' . $middlewareSmsId . '?sms_status=' . $smsStatus,
            false,
            $context
        );

        $response = json_decode($response, true);

        if (
            !isset($response['items']) ||
            !isset($response['items'][0])
        ) {
            throw new ApiExeption('could not get sms with id ' . $middlewareSmsId);
        }

        return $response['items'][0];

    }

}