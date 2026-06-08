<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSelectionTest extends TestCase
{
    public function test_english_is_the_default_and_hungarian_can_be_selected(): void
    {
        $this->get(route('setup.index'))->assertSee('Aptoria setup');

        $this->get('/language/hu')
            ->assertSessionHas('locale', 'hu');

        $this->get(route('setup.index'))->assertSee('Aptoria telepítő');
    }
}
