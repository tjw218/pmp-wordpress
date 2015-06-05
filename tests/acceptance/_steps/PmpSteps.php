<?php
namespace AcceptanceTester;

class PmpSteps extends \AcceptanceTester
{

    public function login($username, $password)
    {
        $I = $this;
        $I->amOnPage('/wp-login.php');
        $I->fillField('Username', $username);
        $I->fillField('Password', $password);
        $I->click('Log In');
        $I->see('Dashboard');
    }

    public function pmpNavigate($text)
    {
        $I = $this;
        $I->click('Public Media Platform');
        $I->click($text, '#toplevel_page_pmp-search');
    }

}
