<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_mathjaxloader;

/**
 * Unit tests for the MathJax loader filter.
 *
 * @package   filter_mathjaxloader
 * @category  test
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \filter_mathjaxloader\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    /**
     * Test the functionality of {@see text_filter::map_language_code()}.
     *
     * @param string $moodlelangcode the user's current language
     * @param string $mathjaxlangcode the mathjax language to be used for the moodle language
     * @dataProvider map_language_code_expected_mappings
     */
    public function test_map_language_code($moodlelangcode, $mathjaxlangcode): void {
        $filter = new text_filter(\context_system::instance(), []);
        $this->assertEquals($mathjaxlangcode, $filter->map_language_code($moodlelangcode));
    }

    /**
     * Data provider for {@link self::test_map_language_code}
     *
     * @return array of [moodlelangcode, mathjaxcode] tuples
     */
    public static function map_language_code_expected_mappings(): array {
        return [
            ['cz', 'cs'], // Explicit mapping.
            ['cs', 'cs'], // Implicit mapping (exact match).
            ['ca_valencia', 'ca'], // Implicit mapping of a Moodle language variant.
            ['pt_br', 'pt-br'], // Explicit mapping.
            ['en_kids', 'en'], // Implicit mapping of English variant.
            ['de_kids', 'de'], // Implicit mapping of non-English variant.
            ['es_mx_kids', 'es'], // More than one underscore in the name.
            ['zh_tw', 'zh-hant'], // Explicit mapping of the Taiwain Chinese in the traditional script.
            ['zh_cn', 'zh-hans'], // Explicit mapping of the Simplified Chinese script.
        ];
    }

    /**
     * Test compatibility with glossary autolinker
     *
     * @return void
     */
    public function test_glossary_entry(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/glossary/classes/external.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable glossary filter at top level.
        filter_set_global_state('mathjaxloader', TEXTFILTER_ON);
        filter_set_global_state('glossary', TEXTFILTER_ON);
        $CFG->glossary_linkentries = 1;

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create a glossary.
        $glossary = $this->getDataGenerator()->create_module(
            'glossary',
            ['course' => $course->id, 'mainglossary' => 1]
        );

        // Create two entries with ampersands and one normal entry.
        /** @var \mod_glossary_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');
        $simple = $generator->create_content($glossary, ['concept' => 'simple']);
        $withmath = $generator->create_content($glossary, [
            'concept' => 'parabolic',
            'definition' => 'more \(y = x^2\)',
        ]);

        // Check whether container class is inserted iff math is in definition.
        $filtered = \filter_glossary\external\get_entry_by_id::execute($simple->id);
        $this->assertFalse(strpos($filtered['entry']->definition, 'class="filter_mathjaxloader_equation"'));
        $filtered = \filter_glossary\external\get_entry_by_id::execute($withmath->id);
        $this->assertNotEmpty(strpos($filtered['entry']->definition, 'class="filter_mathjaxloader_equation"'));
    }
}
