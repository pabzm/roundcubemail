<?php

namespace Tests\Browser\Settings;

use Tests\Browser\Components\App;
use Tests\Browser\Components\Dialog;
use Tests\Browser\Components\Popupmenu;

class ResponsesTest extends \Tests\Browser\TestCase
{
    public static function setUpBeforeClass()
    {
        \bootstrap::init_db();
    }

    public function testResponses()
    {
        $this->browse(function ($browser) {
            $browser->go('settings', 'responses');

            $browser->with(new App(), function ($browser) {
                // check task and action
                $browser->assertEnv('task', 'settings');
                $browser->assertEnv('action', 'responses');

                // these objects should be there always
                $browser->assertObjects(['responseslist']);
            });

            if ($browser->isDesktop()) {
                $browser->assertVisible('#settings-menu li.responses.selected');
            }

            // Responses list
            $browser->assertPresent('#responses-table')
                ->assertMissing('#responses-table tr');

            // Toolbar menu
            $browser->assertToolbarMenu(['create'], ['delete']);
        });
    }

    /**
     * Test response creation
     */
    public function testResponseCreate()
    {
        $this->browse(function ($browser) {
            $browser->go('settings', 'responses');

            if ($browser->isPhone()) {
                $browser->assertVisible('.floating-action-buttons a.create:not(.disabled)')
                    ->click('.floating-action-buttons a.create')
                    ->waitFor('#preferences-frame');
            }
            else {
                $browser->clickToolbarMenuItem('create');
            }

            $browser->withinFrame('#preferences-frame', function($browser) {
                $browser->waitFor('form')
                    ->with('form', function ($browser) {
                        $browser->assertVisible('input[name=_name]')
                            ->assertValue('input[name=_name]', '')
                            ->assertSeeIn('label[for=ffname]', 'Name')
                            ->assertVisible('textarea[name=_text]')
                            ->assertValue('textarea[name=_text]', '')
                            ->assertSeeIn('label[for=fftext]', 'Response Text');
                    })
                    ->type('_name', 'Test')
                    ->type('_text', 'Response Body');

                if (!$browser->isPhone()) {
                    $browser->click('.formbuttons button.submit');
                }
            });

            if ($browser->isPhone()) {
                $browser->assertVisible('#layout-content .header a.back-list-button')
                    ->assertVisible('#layout-content .footer .buttons a.button.submit')
                    ->click('#layout-content .footer .buttons a.button.submit');
            }

            $browser->waitForMessage('confirmation', 'Successfully saved.')
                ->closeMessage('confirmation')
                ->waitFor('#preferences-frame');

            $browser->withinFrame('#preferences-frame', function($browser) {
                $browser->waitFor('form')
                    ->with('form', function ($browser) {
                        $browser->assertVisible('input[name=_name]')
                            ->assertValue('input[name=_name]', 'Test')
                            ->assertValue('textarea[name=_text]', 'Response Body');
                    });
            });

            if ($browser->isPhone()) {
                $browser->click('#layout-content .header a.back-list-button')
                    ->waitFor('#responses-table');
            }

            // Responses list
            $browser->with('#responses-table', function ($browser) {
                $browser->assertElementsCount('tbody tr', 1)
                    ->assertSeeIn('tbody tr:nth-child(1)', 'Test');
            });

            if ($browser->isPhone()) {
                $browser->click('#responses-table tbody tr:first-child')
                    ->waitFor('#preferences-frame');
            }

            // Toolbar menu (Delete button is active now)
            $browser->assertToolbarMenu(['create', 'delete']);
        });
    }

    /**
     * Test response deletion
     *
     * @depends testResponseCreate
     */
    public function testResponseDelete()
    {
        $this->browse(function ($browser) {
            $browser->clickToolbarMenuItem('delete');

            $browser->with(new Dialog(), function ($browser) {
                $browser->assertDialogTitle('Are you sure...')
                    ->assertDialogContent('Do you really want to delete this response text?')
                    ->assertButton('mainaction.delete', 'Delete')
                    ->assertButton('cancel', 'Cancel')
                    ->clickButton('mainaction.delete');
            });

            $browser->waitForMessage('confirmation', 'Successfully deleted.')
                ->closeMessage('confirmation');

            // Preview frame should reset to the watermark page
            $browser->withinFrame('#preferences-frame', function($browser) {
                $browser->waitUntilMissing('> div');
            });

            $browser->waitFor('#layout-list')
                ->assertElementsCount('#responses-table tbody tr', 0);

            // Toolbar menu (Delete button is inactive again)
            $browser->assertToolbarMenu(['create'], ['delete']);
        });
    }

    /**
     * Test responses in mail composer
     *
     * @depends testResponseDelete
     */
    public function testResponsesInComposer()
    {
        // Quickly create a set of responses
        $responses = array(
            array('name' => 'Test 1', 'text' => 'Response 1', 'format' => 'text', 'key' => substr(md5('Test 1'), 0, 16)),
            array('name' => 'Test 2', 'text' => 'Response 2', 'format' => 'text', 'key' => substr(md5('Test 2'), 0, 16)),
        );

        (new \rcube_user(1))->save_prefs(array('compose_responses' => $responses));

        $this->browse(function ($browser) {
            if ($browser->isPhone()) {
                $browser->click('a.back-sidebar-button');
            }

            // Goto Compose and test the responses menu
            $browser->clickTaskMenuItem('compose')
                ->waitFor('#compose-content')
                ->clickToolbarMenuItem('responses')
                ->with(new Popupmenu('responses-menu'), function ($browser) {
                    $browser->assertMenuState(['create.responses', 'edit.responses'])
                        ->with('#responseslist', function ($browser) {
                            $browser->assertElementsCount('li', 2)
                                ->assertSeeIn('li:nth-child(1) a.insertresponse', 'Test 1')
                                ->assertSeeIn('li:nth-child(2) a.insertresponse', 'Test 2');
                        })
                        ->closeMenu();
                });

            // Insert a response to the message body
            $browser->type('#composebody', 'Body and ')
                ->clickToolbarMenuItem('responses')
                ->waitFor('#responseslist')
                ->click('#responseslist li:nth-child(1) a.insertresponse')
                ->waitUntilMissing('#responses-menu')
                ->assertValue('#composebody', 'Body and Response 1');

            // TODO: Test HTML mode, test response creation
        });
    }
}
