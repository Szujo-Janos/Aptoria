<?php

namespace Tests\Feature;

use Tests\TestCase;

class SemanticIconRegistryTest extends TestCase
{
    public function test_documentation_and_import_icons_have_renderer_paths(): void
    {
        $iconScript = file_get_contents(public_path('assets/aptoria-ui/assets/js/aptoria-icons.js'));

        foreach (['book-open', 'file-input', 'file-search', 'sitemap', 'table'] as $icon) {
            $this->assertStringContainsString("'{$icon}': '<", $iconScript);
        }

        foreach (['package-plus' => 'file-input', 'package-search' => 'file-search', 'rocket' => 'play-circle', 'terminal' => 'file-code-2', 'wrench' => 'tool'] as $alias => $target) {
            $this->assertStringContainsString("'{$alias}': '{$target}'", $iconScript);
        }
    }
}
