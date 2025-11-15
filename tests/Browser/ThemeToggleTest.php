<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ThemeToggleTest extends DuskTestCase
{
    /** @test */
    public function it_applies_dark_class_when_localstorage_is_dark(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                // Set localStorage to dark and reload
                ->script(["localStorage.setItem('theme','dark');"]);

            $browser->refresh()
                ->waitForLocation('/')
                ->pause(150) // allow head script + Alpine store
                ->assertScript('document.documentElement.classList.contains("dark") === true');
        });
    }

    /** @test */
    public function it_removes_dark_class_when_localstorage_is_light(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->script(["localStorage.setItem('theme','light');"]);

            $browser->refresh()
                ->waitForLocation('/')
                ->pause(150)
                ->assertScript('document.documentElement.classList.contains("dark") === false');
        });
    }
}
