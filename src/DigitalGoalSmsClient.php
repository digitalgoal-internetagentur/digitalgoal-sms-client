<?php


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

    public function getToken()
    {

        $postdata = http_build_query(
            [
                'user_email' => $this->getUser(),
                'user_password' => $this->getPassword()
            ]
        );

        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            ]
        ];

        $context  = stream_context_create($opts);

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
            throw new Exception('DigitalGoal SMS Middleware: Login invalid');
        }

        return $response['items'][0]['token_hash'];

    }

    public function sendToMiddleware()
    {

        $postdata = http_build_query(
            [
                'sms_to' => $this->getSmsNumber(),
                'sms_text' => $this->getSmsText()
            ]
        );

        $opts = [
            'https' => [
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

    }

}