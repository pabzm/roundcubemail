<?php

namespace Tests\Browser\Settings;

class Responses extends \Tests\Browser\DuskTestCase
{
    public function testIdentities()
    {
        $this->browse(function ($browser) {
            $this->go('settings', 'responses');

            // check task and action
            $this->assertEnvEquals('task', 'settings');
            $this->assertEnvEquals('action', 'responses');

            $objects = $this->getObjects();

            // these objects should be there always
            $this->assertContains('responseslist', $objects);

            if ($this->isDesktop()) {
                $browser->assertVisible('#settings-menu li.responses.selected');
            }

            // Responses list
            $browser->assertPresent('#responses-table');
            $browser->assertMissing('#responses-table tr');

            // Toolbar menu
            $this->assertToolbarMenu(['create'], ['delete']);
        });
    }
}
