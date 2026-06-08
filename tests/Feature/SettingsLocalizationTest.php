<?php

namespace Tests\Feature;

use App\Services\Settings\SettingService;
use Tests\TestCase;

class SettingsLocalizationTest extends TestCase
{
    public function test_all_settings_have_english_and_hungarian_labels_help_groups_and_option_labels(): void
    {
        $defaults = app(SettingService::class)->defaults();
        $groups = collect($defaults)->pluck('group')->unique()->values()->all();
        $options = collect($defaults)
            ->flatMap(fn (array $meta): array => $meta['options'] ?? [])
            ->unique()
            ->values()
            ->all();

        foreach (['en', 'hu'] as $locale) {
            $messages = include resource_path("lang/{$locale}/messages.php");
            $settings = $messages['settings'] ?? [];

            foreach (array_keys($defaults) as $key) {
                $field = str_replace('.', '_', $key);
                $this->assertArrayHasKey($field, $settings['fields'] ?? [], "Missing {$locale} settings field label for {$key}");
                $this->assertArrayHasKey($field, $settings['field_help'] ?? [], "Missing {$locale} settings field help for {$key}");

                if (($defaults[$key]['ui'] ?? true) !== false) {
                    $label = trim((string) ($settings['fields'][$field] ?? ''));
                    $this->assertNotSame('', $label, "Empty {$locale} settings field label for {$key}");
                    $this->assertNotSame($key, $label, "Raw {$locale} settings key used as label for {$key}");
                }
            }

            foreach ($groups as $group) {
                $this->assertArrayHasKey($group, $settings['groups'] ?? [], "Missing {$locale} settings group label for {$group}");
                $this->assertArrayHasKey($group, $settings['group_help'] ?? [], "Missing {$locale} settings group help for {$group}");
            }

            foreach ($options as $option) {
                $this->assertArrayHasKey($option, $settings['values'] ?? [], "Missing {$locale} settings option label for {$option}");
            }
        }
    }

    public function test_settings_help_text_does_not_use_generic_boilerplate(): void
    {
        foreach (['en', 'hu'] as $locale) {
            $messages = include resource_path("lang/{$locale}/messages.php");
            $help = $messages['settings']['field_help'] ?? [];
            $joined = implode("
", array_map('strval', $help));

            $this->assertStringNotContainsString('global runtime setting', $joined);
            $this->assertStringNotContainsString('Changes apply to the related Aptoria screens', $joined);
            $this->assertStringNotContainsString('globális működési beállítás', $joined);
            $this->assertStringNotContainsString('A módosítás mentés után a kapcsolódó Aptoria', $joined);
        }
    }

    public function test_settings_view_does_not_fall_back_to_service_descriptions(): void
    {
        $view = file_get_contents(resource_path('views/settings/index.blade.php'));

        $this->assertStringNotContainsString('(\$setting[\'description\'] ?? \'\')', $view);
        $this->assertStringNotContainsString('Str::headline', $view);
    }
}
