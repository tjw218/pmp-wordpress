<?php
$I = new AcceptanceTester\PmpSteps($scenario);
$I->wantTo('pull a story from the PMP');
$I->login('admin', 'admin');

$I->pmpNavigate('Search');
$I->see('Search the Platform');
$I->click('Show advanced options');

$I->fillField('Enter keywords', 'title:obama');
$I->selectOption('has', 'Image');
$I->click('Search', '#pmp-search-form');
$I->waitForElement('.pmp-search-result');
$I->see('Obama', '.pmp-title');

$I->click('Create draft');
$I->see('Are you sure you want to create a draft of this story?');
$I->click('Cancel');
$I->dontSee('Are you sure you want to create a draft of this story?');

$I->click('Publish');
$I->see('Are you sure you want to publish this story?');
$I->click('Yes');

$I->waitForText('Edit Post', 10000);
$I->see('Edit Post');
$I->see('Published');
$I->see('Subscribe to updates for this post');
$I->seeElement('img.attachment-post-thumbnail');
$I->click('Move to Trash');
