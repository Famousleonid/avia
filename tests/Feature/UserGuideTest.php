<?php

namespace Tests\Feature;

use Tests\BuildsDomainData;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    use BuildsDomainData;

    public function test_authenticated_user_can_open_static_book_without_training_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.user-guide'));

        $response->assertOk();
        $response->assertSee('class="guide-reading"', false);
        $response->assertSee('data-guide-page', false);
        $response->assertSee('class="guide-steps"', false);
        $response->assertSee('<h2 class="page-title"', false);
        $response->assertDontSee('<h1 class="page-title"', false);
        $response->assertSee('font-size: clamp(1.05rem, 2vw, 1.35rem);', false);
        $response->assertSee('1. Getting started');
        $response->assertSee('2. Workorder');
        $response->assertSee('2.1 Workorders page');
        $response->assertSee('2.2 Getting started with a workorder');
        $response->assertSee('2.3 Filters');
        $response->assertSee('2.4 Open a workorder');
        $response->assertSee('2.5 Main');
        $response->assertSee('2.6 Main: header and work area');
        $response->assertSee('2.7 Tasks and notes');
        $response->assertSee('2.8 Processes and parts');
        $response->assertSee('2.9 TDR, pictures and PDF Library');
        $response->assertSee('2.10 TDR: parts and processes');
        $response->assertSee('2.13 TDR: paper workorder');
        $response->assertSee('3. Training');
        $response->assertSee('3.1 My training');
        $response->assertSee('4. Technician');
        $response->assertSee('4.1 Technician directory');
        $response->assertSee('5. Materials');
        $response->assertSee('5.1 Materials list');
        $response->assertSee('img/user-guide/technician-login.png', false);
        $response->assertSee('img/user-guide/technician-cabinet.png', false);
        $response->assertSee('img/user-guide/technician-workorders.png', false);
        $response->assertSee('img/user-guide/technician-workorders-assignment.png', false);
        $response->assertSee('img/user-guide/technician-workorders-open.png', false);
        $response->assertSee('img/user-guide/technician-workorders-filters-split.png', false);
        $response->assertSee('img/user-guide/technician-workorder-main-technician.png', false);
        $response->assertSee('img/user-guide/technician-workorder-main-header-only.png', false);
        $response->assertDontSee('img/user-guide/technician-workorder-main-header.png', false);
        $response->assertSee('guide-figure--main-header', false);
        $response->assertSee('guide-actions', false);
        $response->assertSee('bi bi-mortarboard', false);
        $response->assertSee('Opens the form to add or update your training record for this manual.', false);
        $response->assertDontSee('data-i18n="workorderMainDetailsLead"', false);
        $response->assertDontSee('data-i18n="workorderMainHeaderWhat"', false);
        $response->assertSee('img/user-guide/technician-workorder-main-workarea.png', false);
        $response->assertSee('img/user-guide/technician-tdr-filled-parts.svg', false);
        $response->assertSee('img/user-guide/technician-tdr-code.png', false);
        $response->assertSee('img/user-guide/technician-tdr-processes-button.png', false);
        $response->assertSee('img/user-guide/technician-tdr-processes-add.png', false);
        $response->assertSee('img/user-guide/technician-tdr-traveler-selection.svg', false);
        $response->assertSee('img/user-guide/technician-tdr-process-form.svg', false);
        $response->assertSee('Traveler checkboxes, Vendor and Form / Form traveler buttons.', false);
        $response->assertDontSee('Form checkboxes', false);
        $response->assertSee('The generated form uses current workorder and process data.', false);
        $response->assertDontSee('img/user-guide/technician-tdr.png', false);
        $response->assertSee('img/user-guide/technician-tdr-paper-forms.png', false);
        $response->assertSee('img/user-guide/technician-training.png', false);
        $response->assertSee('img/user-guide/technician-directory.png', false);
        $response->assertSee('img/user-guide/technician-materials.png', false);
        $response->assertSee('data-guide-order="30"', false);
        $response->assertSee('data-guide-order="31"', false);
        $response->assertSee('data-guide-order="32"', false);
        $response->assertSee('data-guide-order="33"', false);
        $response->assertSee('data-guide-order="40"', false);
        $response->assertSee('data-guide-order="50"', false);
        $response->assertSee('data-guide-order="60"', false);
        $response->assertSee('const technicianGuideTranslations', false);
        $response->assertSee('const tdrGuideTranslations', false);
        $response->assertSee('const tdrPartsAddTranslations', false);
        $response->assertSee('The blue image icon opens all photos for the selected workorder.', false);
        $response->assertSee('Filters change only the rows shown in the table.', false);
        $response->assertSee('filter-highlight--approved', false);
        $response->assertSee('Your manager assigns a workorder to you.', false);
        $response->assertSee('assignment-highlight--technician', false);
        $response->assertSee('Click its blue number.', false);
        $response->assertSee('The paper icons do not create a new workorder', false);
        $response->assertSee('Saved parts in TDR; Add creates the next part row.', false);
        $response->assertSee('On the saved part row, press the train icon in Action.', false);
        $response->assertSee('Add Process appears only when you are allowed to create a new process definition.', false);
        $response->assertSee('guide-page__split', false);
        $response->assertSee('grid-template-columns: minmax(0, 3.6fr) minmax(0, 1.4fr);', false);
        $response->assertSee('guide-page__title-row', false);
        $response->assertSee('guide-workflow', false);
        $response->assertSee('workflowCheck', false);
        $response->assertSee('workflowTdr', false);
        $response->assertSee('data-guide-order="22"', false);
        $response->assertSee("const scope = 'user-guide-book'", false);
        $response->assertSee("document.documentElement.dir = 'ltr'", false);
        $response->assertSee('const tdrPartsDetailedTranslations', false);
        $response->assertSee('href="' . route('workorders.index') . '"', false);
        $response->assertSee('width: min(100%, 1196px)', false);

        foreach (['en', 'ru', 'uk', 'he', 'de', 'kk', 'be'] as $language) {
            $response->assertSee('data-language="' . $language . '"', false);
        }

        $response->assertDontSee('<iframe', false);
        $response->assertDontSee('userGuideEmbed', false);
        $response->assertDontSee('guide-live-', false);
        $response->assertDontSee('data-workorders-stage', false);
    }

    public function test_technician_can_open_static_book_without_assignment(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->get(route('admin.user-guide'))
            ->assertOk()
            ->assertSee('Workorders page');
    }

    public function test_disassembly_images_share_the_navigation_and_reading_order(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'))
            ->get(route('admin.user-guide'))
            ->assertOk()
            ->assertSee('pages.forEach((page) => reading.appendChild(page))', false)
            ->assertDontSee('.guide-page#workorder-tasks { order:', false)
            ->assertDontSee('order: 26.5', false)
            ->assertDontSee('order: 27.1', false)
            ->assertSee('id="workorder-tasks" data-guide-page data-guide-order="27"', false)
            ->assertSee('id="workorder-disassembly-result" data-guide-page data-guide-order="27.1"', false)
            ->assertSee('img/user-guide/technician-disassembly.png', false)
            ->assertSee('img/user-guide/technician-disassembly-2.png', false);
    }

    public function test_log_card_has_two_static_pages_with_complete_translations(): void
    {
        $response = $this->actingAs($this->createUserWithRole('Technician'))
            ->get(route('admin.user-guide'))->assertOk();

        foreach (['mobile' => '27.2', 'desktop' => '27.3'] as $device => $order) {
            $response->assertSee('id="log-card-'.$device.'" data-guide-page data-guide-order="'.$order.'"', false);
            $response->assertSee("'log-card-".$device."': 'log-card'", false);
        }
        $response->assertSee('href="#log-card-mobile" role="listitem" data-guide-flow data-guide-stage="log-card"', false);
        $response->assertSee('Photo + read', false)->assertSee('Received S/N', false);
        $response->assertSee('the desktop Log Card table has no such recognition button', false);
        foreach (['mobile-select', 'mobile-photo', 'desktop-select', 'desktop-serials'] as $image) {
            $path = 'img/user-guide/log-card-'.$image.'.jpg';
            $response->assertSee($path, false);
            $this->assertFileExists(public_path($path));
        }

        $translations = require resource_path('lang/user-guide-log-card.php');
        foreach (['en', 'ru', 'uk', 'he', 'de', 'kk', 'be'] as $language) {
            $this->assertSame(array_keys($translations['en']), array_keys($translations[$language]));
            foreach ($translations[$language] as $text) $this->assertNotSame('', trim($text));
        }
    }

    public function test_before_photos_has_real_mobile_steps_and_complete_translations(): void
    {
        $response = $this->actingAs($this->createUserWithRole('Technician'))
            ->get(route('admin.user-guide'))->assertOk();

        foreach ([
            'workorder-mobile-photos' => '26.5',
            'workorder-mobile-camera' => '26.53',
            'workorder-mobile-photo-review' => '26.56',
            'workorder-resources' => '26.6',
        ] as $id => $order) {
            $response->assertSee('id="'.$id.'" data-guide-page data-guide-order="'.$order.'"', false);
            $response->assertSee('href="#'.$id.'"', false);
            $response->assertSee("'".$id."': 'photos'", false);
        }
        foreach (['mobile-groups', 'mobile-camera', 'mobile-landscape', 'mobile-review', 'desktop-gallery'] as $image) {
            $path = 'img/user-guide/before-photos-'.$image.'.jpg';
            $response->assertSee($path, false);
            $this->assertFileExists(public_path($path));
            $this->assertGreaterThan(0, getimagesize(public_path($path))[0]);
        }
        $response->assertSee('guide-photo-ring--camera', false)
            ->assertSee('guide-photo-ring--landscape', false)
            ->assertSee('Save photo', false)
            ->assertSee('Retake', false)
            ->assertSee('Photo not saved', false)
            ->assertDontSee('data-i18n="mobilePhotosCopy"', false);

        $translations = require resource_path('lang/user-guide-before-photos.php');
        foreach (['en', 'ru', 'uk', 'he', 'de', 'kk', 'be'] as $language) {
            $this->assertSame(array_keys($translations['en']), array_keys($translations[$language]));
            foreach ($translations[$language] as $text) $this->assertNotSame('', trim($text));
        }
    }

    public function test_tdr_follows_log_card_with_whole_illustrated_pages_in_contents_order(): void
    {
        $html = $this->actingAs($this->createUserWithRole('Technician'))
            ->get(route('admin.user-guide'))->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " toc-link ")]');
        $order = [];
        foreach ($links as $link) $order[] = substr($link->getAttribute('href'), 1);
        $start = array_search('log-card-mobile', $order, true);
        $this->assertNotFalse($start);
        $this->assertSame([
            'log-card-mobile', 'log-card-desktop', 'tdr-parts-processes',
            'tdr-traveler', 'tdr-process-form', 'workorder-processes',
            'repair-templates', 'repair-form', 'bushing-selection', 'bushing-processes',
            'bushing-batches', 'bushing-forms', 'tdr-paper-workorder',
        ], array_slice($order, $start, 13));
        $this->assertCount(count($order), array_unique($order));
        foreach ($order as $id) {
            $this->assertSame(1, $xpath->query('//article[@id="'.$id.'"]')->length);
        }
        foreach ([
            'tdr-parts-processes' => 4, 'tdr-traveler' => 1,
            'tdr-process-form' => 1, 'workorder-processes' => 2, 'tdr-paper-workorder' => 1,
            'repair-templates' => 1, 'repair-form' => 1, 'bushing-selection' => 1,
            'bushing-processes' => 2, 'bushing-batches' => 1, 'bushing-forms' => 2,
        ] as $id => $count) {
            $images = $xpath->query('//article[@id="'.$id.'"]//img');
            $this->assertSame($count, $images->length, $id.' must retain all illustrations');
            foreach ($images as $image) {
                $this->assertFileExists(public_path(ltrim(parse_url($image->getAttribute('src'), PHP_URL_PATH), '/')));
            }
        }
        $this->assertSame(3, $xpath->query('//section[@data-guide-stage-group="tdr"]/a')->length);
        $this->assertSame(3, $xpath->query('//section[@data-guide-stage-group="repair"]/a')->length);
        $this->assertSame(4, $xpath->query('//section[@data-guide-stage-group="bushings"]/a')->length);
        $this->assertStringContainsString("'tdr-process-form': 'tdr'", $html);
        $this->assertStringContainsString('pageOrder.get(left.id)', $html);
    }

    public function test_repair_and_bushings_have_illustrations_steps_and_seven_translations(): void
    {
        $response = $this->actingAs($this->createUserWithRole('Technician'))
            ->get(route('admin.user-guide'))->assertOk();
        $response->assertSee('href="#bushing-selection" role="listitem" data-guide-flow data-guide-stage="bushings"', false);
        $response->assertSee('...repairBushingGuideTranslations[selected]', false);
        foreach ([
            'workorder-processes' => 'repair', 'repair-templates' => 'repair', 'repair-form' => 'repair',
            'bushing-selection' => 'bushings', 'bushing-processes' => 'bushings',
            'bushing-batches' => 'bushings', 'bushing-forms' => 'bushings',
        ] as $id => $stage) {
            $response->assertSee("'{$id}': '{$stage}'", false);
        }
        $translations = require resource_path('lang/user-guide-repair-bushings.php');
        foreach (['en', 'ru', 'uk', 'he', 'de', 'kk', 'be'] as $language) {
            $this->assertCount(42, $translations[$language]);
            $this->assertSame(array_keys($translations['en']), array_keys($translations[$language]));
            foreach ($translations[$language] as $text) $this->assertNotSame('', trim($text));
        }
        foreach (array_keys($translations['en']) as $key) {
            $response->assertSee('data-i18n="'.$key.'"', false);
        }
        foreach (['repair-editor', 'repair-fields', 'repair-templates', 'repair-form',
            'bushing-selection', 'bushing-processes', 'bushing-save', 'bushing-batches',
            'bushing-form-selection', 'bushing-form'] as $image) {
            $path = 'img/user-guide/'.$image.'.png';
            $response->assertSee($path, false);
            $this->assertFileExists(public_path($path));
            $this->assertGreaterThan(0, getimagesize(public_path($path))[0]);
        }
        $response->assertSee('Save Bushing Data', false)->assertSee('Included in KIT', false);
        $response->assertDontSee('data-i18n="workorderProcessesCopy"', false);
    }

    public function test_old_interactive_guide_routes_are_removed(): void
    {
        $admin = $this->createUserWithRole('Admin');

        foreach ([
            'workorder-main',
            'tdr-report',
            'workorder-pictures',
            'training',
            'technicians',
            'materials',
            'mobile-workorders',
            'mobile-workorder',
            'mobile-workorder-pictures',
        ] as $page) {
            $this->actingAs($admin)
                ->get('/admin/user-guide/' . $page)
                ->assertNotFound();
        }
    }

    public function test_sidebar_shows_guide_link_to_every_role_branch(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);

        $sidebar = view('components.admin_menu_sidebar', [
            'themeToggleId' => 'test-theme-toggle',
        ])->render();

        $userGuidePosition = strpos($sidebar, '>User Guide</span>');
        $themePosition = strpos($sidebar, '>Thema</span>');
        $this->assertNotFalse($userGuidePosition);
        $this->assertNotFalse($themePosition);
        $this->assertLessThan($themePosition, $userGuidePosition);

        foreach (['Technician', 'Paint'] as $role) {
            $this->actingAs($this->createUserWithRole($role));

            $this->assertStringContainsString(
                'href="' . route('admin.user-guide', ['center' => 1]) . '"',
                view('components.admin_menu_sidebar')->render(),
                "User Guide must be available to {$role}."
            );
        }
    }
}
