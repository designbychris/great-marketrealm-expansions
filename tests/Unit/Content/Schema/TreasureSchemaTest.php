<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class TreasureSchemaTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    /** @return array<string,mixed> */
    private function treasureData(): array
    {
        return [
            'name' => 'Fixture Strongbox',
            'kind' => 'parcel',
            'currency' => [
                'fixture-coin' => 25,
                'half-fixture' => 2.5,
            ],
            'items' => [
                [
                    'ref' => 'first-almanac:magic-item:fixture-spoon',
                    'quantity' => 1,
                    'chance' => 75,
                    'weight' => 1,
                    'variant' => 'polished',
                    'rules' => [
                        ['kind' => 'grant', 'type' => 'item', 'key' => 'first-almanac:magic-item:fixture-spoon'],
                    ],
                ],
            ],
            'nested_treasure' => [
                'first-almanac:treasure:fixture-pouch',
            ],
            'tables' => [
                [
                    'key' => 'oddments',
                    'name' => 'Oddments',
                    'die' => '1d6',
                    'rolls' => 1,
                    'entries' => [
                        [
                            'min' => 1,
                            'max' => 3,
                            'reward' => [
                                'type' => 'item',
                                'ref' => 'first-almanac:equipment:fixture-ration',
                                'quantity' => 1,
                            ],
                        ],
                        [
                            'weight' => 2,
                            'reward' => [
                                'type' => 'currency',
                                'currency' => 'fixture-coin',
                                'quantity' => 5,
                            ],
                        ],
                    ],
                ],
            ],
            'selections' => [
                [
                    'key' => 'keeper-choice',
                    'name' => 'Keeper Choice',
                    'count' => 1,
                    'options' => [
                        'first-almanac:magic-item:fixture-spoon',
                        [
                            'type' => 'currency',
                            'currency' => 'fixture-coin',
                            'quantity' => 10,
                        ],
                    ],
                ],
            ],
            'grants' => [
                ['type' => 'currency', 'key' => 'fixture-coin', 'amount' => 5],
            ],
            'value' => [
                'amount' => 100,
                'currency' => 'fixture-coin',
            ],
            'distribution' => 'party',
            'references' => [
                'first-almanac:encounter:fixture-ambush',
            ],
            'keeper_notes' => [
                'The label says Definitely Not A Mimic.',
            ],
        ];
    }

    public function test_minimal_named_treasure_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('treasure', 'fixture-minimal', [
                'name' => 'Fixture Minimal Treasure',
            ]))->valid()
        );
    }

    public function test_complete_structured_treasure_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('treasure', 'fixture-strongbox', $this->treasureData()))->valid()
        );
    }

    public function test_treasure_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('treasure');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'currency', 'items', 'nested_treasure', 'tables', 'selections',
            'grants', 'value', 'distribution', 'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected treasure schema field: ' . $field);
        }
    }

    public function test_currency_amounts_must_be_non_negative_numbers(): void
    {
        $data = $this->treasureData();
        $data['currency'] = ['fixture-coin' => -1, 'bad' => 'many'];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-currency', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['currency.fixture-coin', 'currency.bad'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_items_require_canonical_reference(): void
    {
        $data = $this->treasureData();
        $data['items'] = [['quantity' => 1]];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-item', $data));

        self::assertFalse($result->valid());
        self::assertSame('items.0.ref', $result->errors()[0]->field());
    }

    public function test_item_quantity_weight_and_chance_are_validated(): void
    {
        $data = $this->treasureData();
        $data['items'][0]['quantity'] = 0;
        $data['items'][0]['weight'] = 0;
        $data['items'][0]['chance'] = 101;
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-item-numbers', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['items.0.quantity', 'items.0.weight', 'items.0.chance'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_item_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->treasureData();
        $data['items'][0]['rules'] = [
            ['kind' => 'modifier', 'target' => 'value'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-item-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['items.0.rules.0.operation', 'items.0.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_nested_treasure_references_are_non_empty_strings(): void
    {
        $data = $this->treasureData();
        $data['nested_treasure'] = ['first-almanac:treasure:fixture-pouch', ''];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-nested', $data));

        self::assertFalse($result->valid());
        self::assertSame('nested_treasure.1', $result->errors()[0]->field());
    }

    public function test_tables_require_unique_keys_names_and_entries(): void
    {
        $data = $this->treasureData();
        $data['tables'] = [
            ['key' => 'same', 'name' => 'First', 'entries' => [['weight' => 1, 'reward' => ['type' => 'currency']]]],
            ['key' => 'same', 'name' => 'Second', 'entries' => [['weight' => 1, 'reward' => ['type' => 'currency']]]],
            ['key' => 'empty', 'name' => 'Empty', 'entries' => []],
        ];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-tables', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['tables.1.key', 'tables.2.entries'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_table_entries_require_range_or_weight_and_reward(): void
    {
        $data = $this->treasureData();
        $data['tables'][0]['entries'] = [
            [],
            ['min' => 5, 'max' => 2, 'reward' => ['type' => 'item']],
            ['weight' => 0, 'reward' => ['type' => 'currency']],
        ];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-table-entries', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            [
                'tables.0.entries.0',
                'tables.0.entries.1.max',
                'tables.0.entries.2.weight',
            ],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_table_rolls_must_be_positive_integer(): void
    {
        $data = $this->treasureData();
        $data['tables'][0]['rolls'] = 0;
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-rolls', $data));

        self::assertFalse($result->valid());
        self::assertSame('tables.0.rolls', $result->errors()[0]->field());
    }

    public function test_selections_require_unique_keys_and_non_empty_options(): void
    {
        $data = $this->treasureData();
        $data['selections'] = [
            ['key' => 'pick', 'name' => 'Pick One', 'options' => ['first-almanac:equipment:fixture']],
            ['key' => 'pick', 'name' => 'Pick Again', 'options' => ['first-almanac:equipment:fixture-two']],
            ['key' => 'empty', 'name' => 'Empty', 'options' => []],
        ];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-selections', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['selections.1.key', 'selections.2.options'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_selection_count_must_be_positive_integer(): void
    {
        $data = $this->treasureData();
        $data['selections'][0]['count'] = 0;
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-selection-count', $data));

        self::assertFalse($result->valid());
        self::assertSame('selections.0.count', $result->errors()[0]->field());
    }

    public function test_structured_selection_rewards_require_type(): void
    {
        $data = $this->treasureData();
        $data['selections'][0]['options'] = [['quantity' => 2]];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-selection-reward', $data));

        self::assertFalse($result->valid());
        self::assertSame('selections.0.options.0.type', $result->errors()[0]->field());
    }

    public function test_top_level_grants_are_validated_by_rules_engine(): void
    {
        $data = $this->treasureData();
        $data['grants'] = [
            ['key' => 'fixture-coin'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-grant', $data));

        self::assertFalse($result->valid());
        self::assertSame('grants.0.type', $result->errors()[0]->field());
    }

    public function test_value_requires_non_negative_amount_and_currency_key(): void
    {
        $data = $this->treasureData();
        $data['value'] = ['amount' => -1, 'currency' => ''];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-value', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['value.amount', 'value.currency'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_references_and_keeper_notes_are_non_empty_string_lists(): void
    {
        $data = $this->treasureData();
        $data['references'] = [''];
        $data['keeper_notes'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('treasure', 'bad-notes', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_treasure_kinds_currency_keys_and_distribution_allow_marketrealm_oddities(): void
    {
        $data = $this->treasureData();
        $data['kind'] = 'definitely-not-a-mimic';
        $data['currency'] = ['coupon-of-questionable-validity' => 3];
        $data['distribution'] = 'whoever-is-brave-enough-to-open-it';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('treasure', 'future-oddity', $data))->valid()
        );
    }
}
