<?php

namespace Tests\Browser;

use Facebook\WebDriver\WebDriverKeys;
use PHPUnit\Framework\Assert;
use Tests\Browser\Components;

/**
 * Laravel Dusk Browser extensions
 */
class Browser extends \Laravel\Dusk\Browser
{
    /**
     * Assert specified rcmail.env value
     */
    public function assertEnvEquals($key, $expected)
    {
        $this->assertEquals($expected, $this->getEnv($key));

        return $this;
    }

    /**
     * Assert specified checkbox state
     */
    public function assertCheckboxState($selector, $state)
    {
        if ($state) {
            $this->assertChecked($selector);
        }
        else {
            $this->assertNotChecked($selector);
        }

        return $this;
    }

    /**
     * Assert Task menu state
     */
    public function assertTaskMenu($selected)
    {
        $this->with(new Components\Taskmenu(), function ($browser) use ($selected) {
            $browser->assertMenuState($selected);
        });

        return $this;
    }

    /**
     * Assert toolbar menu state
     */
    public function assertToolbarMenu($active, $disabled)
    {
        $this->with(new Components\Toolbarmenu(), function ($browser) use ($active, $disabled) {
            $browser->assertMenuState($active, $disabled);
        });

        return $this;
    }

    /**
     * Close toolbar menu (on phones)
     */
    public function closeToolbarMenu()
    {
        $this->with(new Components\Toolbarmenu(), function ($browser) {
            $browser->closeMenu();
        });

        return $this;
    }

    /**
     * Select taskmenu item
     */
    public function clickTaskMenuItem($name)
    {
        $this->with(new Components\Taskmenu(), function ($browser) use ($name) {
            $browser->clickMenuItem($name);
        });

        return $this;
    }

    /**
     * Select toolbar menu item
     */
    public function clickToolbarMenuItem($name, $dropdown_action = null)
    {
        $this->with(new Components\Toolbarmenu(), function ($browser) use ($name, $dropdown_action) {
            $browser->clickMenuItem($name, $dropdown_action);
        });

        return $this;
    }

    /**
     * Shortcut to click an element while holding CTRL key
     */
    public function ctrlClick($selector)
    {
        $this->driver->getKeyboard()->pressKey(WebDriverKeys::LEFT_CONTROL);
        $this->element($selector)->click();
        $this->driver->getKeyboard()->releaseKey(WebDriverKeys::LEFT_CONTROL);
    }

    /**
     * Visit specified task/action with logon if needed
     */
    public function go($task = 'mail', $action = null, $login = true)
    {
        $this->with(new Components\App(), function ($browser) use ($task, $action, $login) {
            $browser->gotoAction($task, $action, $login);
        });

        return $this;
    }

    /**
     * Check if in Phone mode
     */
    public static function isPhone()
    {
        return getenv('TESTS_MODE') == 'phone';
    }

    /**
     * Check if in Tablet mode
     */
    public static function isTablet()
    {
        return getenv('TESTS_MODE') == 'tablet';
    }

    /**
     * Check if in Desktop mode
     */
    public static function isDesktop()
    {
        return !self::isPhone() && !self::isTablet();
    }

    /**
     * Change state of the Elastic's pretty checkbox
     */
    public function setCheckboxState($selector, $state)
    {
        // Because you can't operate on the original checkbox directly
        $this->ensurejQueryIsAvailable();

        if ($state) {
            $run = "if (!element.prev().is(':checked')) element.click()";
        }
        else {
            $run = "if (element.prev().is(':checked')) element.click()";
        }

        $this->script(
            "var element = jQuery('$selector')[0] || jQuery('input[name=$selector]')[0];"
            ."element = jQuery(element).next('.custom-control-label'); $run;"
        );

        return $this;
    }

    /**
     * Returns content of a downloaded file
     */
    public function readDownloadedFile($filename)
    {
        $filename = TESTS_DIR . "downloads/$filename";

        // Give the browser a chance to finish download
        if (!file_exists($filename)) {
            sleep(2);
        }

        Assert::assertFileExists($filename);

        return file_get_contents($filename);
    }

    /**
     * Removes downloaded file
     */
    public function removeDownloadedFile($filename)
    {
        @unlink(TESTS_DIR . "downloads/$filename");

        return $this;
    }

    /**
     * Wait for UI (notice/confirmation/loading/error/warning) message
     * and assert it's text
     */
    public function waitForMessage($type, $text)
    {
        $selector = '#messagestack > div.' . $type;

        $this->waitFor($selector)->assertSeeIn($selector, $text);

        return $this;
    }

    /**
     * Execute code within body context.
     * Useful to execute code that selects elements outside of a component context
     */
    public function withinBody($callback)
    {
        if ($this->resolver->prefix != 'body') {
            $orig_prefix = $this->resolver->prefix;
            $this->resolver->prefix = 'body';
        }

        call_user_func($callback, $this);

        if (isset($orig_prefix)) {
            $this->resolver->prefix = $orig_prefix;
        }

        return $this;
    }
}
