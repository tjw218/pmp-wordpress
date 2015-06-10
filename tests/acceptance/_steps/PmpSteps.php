<?php
namespace AcceptanceTester;

class PmpSteps extends \AcceptanceTester
{

    public function login($username, $password)
    {
        $I = $this;
        $I->amOnPage('/wp-login.php');
        // $I->fillField('Username', $username);
        // $I->fillField('Password', $password);
        // $I->click('Log In');
        $I->submitForm('#loginform', array('log' => $username, 'pwd' => $password, 'rememberme' => 'forever'));
        $I->waitForText('Dashboard');
        $I->amUsingSandbox();
    }

    public function amUsingSandbox()
    {
        $I = $this;
        $I->pmpNavigate('Settings');
        $I->seeInField('pmp_settings[pmp_api_url]', 'https://api-sandbox.pmp.io');
    }

    public function pmpNavigate($text)
    {
        $I = $this;
        $I->click('Public Media Platform');
        $I->click($text, '#toplevel_page_pmp-search');
    }

}
