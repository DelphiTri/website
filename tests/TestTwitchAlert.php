<?php

use Destiny\Common\Config;
use Destiny\Common\User\UserService;
use Destiny\StreamLabs\StreamLabsService;
use Destiny\StreamLabs\StreamLabsAlertsType;
use Doctrine\DBAL\DBALException;

class TwitchAlertTest extends PHPUnit\Framework\TestCase {

    /**
     * @return StreamLabsService
     * @throws DBALException
     */
    private function getService(){
        $service = StreamLabsService::instance();
        $service->setAuth(UserService::instance()->getAuthByUserAndProvider(Config::$a['streamlabs']['default_user'], 'streamlabs'));
        return $service;
    }

    /**
     * @throws DBALException
     */
    public function testOne() {
        $r = $this->getService()->sendAlert([
            'type' => StreamLabsAlertsType::ALERT_SUBSCRIPTION,
            'message' => '*Billy* bought a *Catarrian Shirt*!'
        ]);
        print_r(json_decode($r->getBody(), true));
        $this->assertArrayHasKey('success', json_decode($r->getBody(), true));
    }

    /**
     * @throws DBALException
     */
    public function testTwo() {
        $r = $this->getService()->sendAlert([
            'type' => StreamLabsAlertsType::ALERT_DONATION,
            'message' => sprintf("*%s* has donated *%s*! %s", 'Billy', '$' . number_format(32, 2), '')
        ]);
        //print_r(json_decode($r->getBody(), true));
        $this->assertArrayHasKey('success', json_decode($r->getBody(), true));
    }

    /**
     * @throws DBALException
     */
    public function testThree() {
        $r = $this->getService()->sendDonation([
            'name' => 'PersonX',
            'message' => 'This is a test message from testThree',
            'identifier' => 'PersonY#23',
            'amount' => '20',
            'currency' => 'USD'
        ]);
        print_r(json_decode($r->getBody(), true));
        $this->assertArrayHasKey('donation_id', json_decode($r->getBody(), true));
    }

}