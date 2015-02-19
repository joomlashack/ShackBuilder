<?php
use \AcceptanceTester;

abstract class ExtensionInstallerAbstractCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    protected function loginIntoAdmin(AcceptanceTester $I)
    {
        $I->amOnUrl(TEST_HOST_BASEURL . '/administrator/index.php');
        $I->fillField('username', 'admin');
        $I->fillField('passwd', 'admin');
        $I->click('//*[@id="form-login"]/fieldset/div[3]/div/div/button');
    }

    /**
     * @before loginIntoAdmin
     */
    public function installExtension(AcceptanceTester $I)
    {
        $I->amOnUrl(TEST_HOST_BASEURL . '/administrator/index.php?option=com_installer');
        $I->click('Install from Directory');
        $I->fillField('//*[@id="install_directory"]', '/project/tests/_output/extension_installer');
        $I->click('//*[@id="directory"]/fieldset/div[2]/input');
        $I->dontSeeElementInDOM(['css' => '#system-message-container div.alert-error']);
        $I->seeElementInDOM(['css' => '#system-message-container div.alert-success']);
    }
}
