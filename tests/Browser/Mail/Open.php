<?php

namespace Tests\Browser\Mail;

use Tests\Browser\Components\App;
use Tests\Browser\Components\Popupmenu;

class Open extends \Tests\Browser\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        \bootstrap::init_imap();
        \bootstrap::purge_mailbox('INBOX');

        // import email messages
        foreach (glob(TESTS_DIR . 'data/mail/list_00.eml') as $f) {
            \bootstrap::import_message($f, 'INBOX');
        }
    }

    /**
     * Test Open in New Window action
     */
    public function testOpenInNewWindow()
    {
        $this->browse(function ($browser) {
            if ($browser->isPhone()) {
                $this->markTestSkipped();
                return;
            }

            $browser->go('mail');

            $browser->waitFor('#messagelist tbody tr:first-child')
                ->ctrlClick('#messagelist tbody tr:first-child');

            $browser->clickToolbarMenuItem('more');

            $browser->with(new Popupmenu('message-menu'), function ($browser) {
                $uids = $browser->driver->executeScript('return rcmail.message_list.get_selection()');

                $this->assertCount(1, $uids);
                $this->assertTrue(is_int($uids[0]) && $uids[0] > 0);

                $uid = $uids[0];

                list($current_window, $new_window) = $browser->openWindow(function ($browser) {
                    $browser->clickMenuItem('extwin');
                });

                $browser->driver->switchTo()->window($new_window);

                $browser->with(new App(), function ($browser) use ($uid) {
                    $browser->assertEnv([
                            'task' => 'mail',
                            'action' => 'show',
                            'uid' => $uid,
                    ]);

                    // TODO: werify the toolbar, which is different here than in the preview frame
                });

                $browser->driver->close();
                $browser->driver->switchTo()->window($current_window);
            });
        });
    }
}
