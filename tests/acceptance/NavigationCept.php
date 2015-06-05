<?php
$I = new AcceptanceTester\PmpSteps($scenario);
$I->wantTo('navigate through the PMP plugin');
$I->login('admin', 'admin');

$I->pmpNavigate('Search');
$I->see('Search the Platform');

$I->pmpNavigate('Settings');
$I->see('PMP Settings');
$I->see('API URL');
$I->see('Client ID');
$I->see('Client Secret');

$I->pmpNavigate('Groups & Permissions');
$I->see('PMP Groups & Permissions');
$I->seeElement('input', ['value' => 'Create new group']);

$I->pmpNavigate('Series');
$I->see('PMP Series');
$I->seeElement('input', ['value' => 'Create new series']);

$I->pmpNavigate('Properties');
$I->see('PMP Properties');
$I->seeElement('input', ['value' => 'Create new properties']);

$I->pmpNavigate('Manage saved searches');
$I->see('Manage saved searches');
