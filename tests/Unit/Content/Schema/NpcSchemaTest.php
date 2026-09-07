<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class NpcSchemaTest extends TestCase
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
    private function npcData(): array
    {
        return [
            'name' => 'Fixture Shopkeeper',
            'identity' => [
                'aliases' => ['The Fixture'],
                'titles' => ['Keeper of Test Shelves'],
                'pronouns' => 'they/them',
                'species' => 'fixture-folk',
                'age' => 'adult',
            ],
            'roles' => ['shopkeeper', 'rumour-source'],
            'affiliations' => [
                ['target' => 'first-almanac:faction:fixture-guild', 'role' => 'member'],
            ],
            'locations' => ['first-almanac:location:fixture-market'],
            'relationships' => [
                ['target' => 'first-almanac:npc:fixture-rival', 'type' => 'rival', 'label' => 'Professional rival'],
            ],
            'appearance' => 'Synthetic appearance used only by PHPUnit.',
            'personality' => ['cheerful', 'meticulous'],
            'goals' => ['Keep the shelves tidy.'],
            'secrets' => ['Knows where the spare fixtures are kept.'],
            'mannerisms' => ['Counts every shelf twice.'],
            'dialogue' => [
                ['key' => 'greeting', 'text' => 'Welcome to the fixture counter.', 'context' => 'first meeting'],
            ],
            'lore_hooks' => [
                [
                    'key' => 'missing-crate',
                    'name' => 'The Missing Crate',
                    'description' => 'A synthetic lore hook.',
                    'trigger' => 'The party asks about deliveries.',
                    'references' => ['first-almanac:adventure:fixture-delivery'],
                ],
            ],
            'combat' => [
                'monster' => 'first-almanac:monster:fixture-guard',
                'variant' => 'shopkeeper',
                'rules' => [
                    ['kind' => 'modifier', 'target' => 'armour-class', 'operation' => 'add', 'value' => 1],
                ],
            ],
        ];
    }

    public function test_minimal_named_npc_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('npc', 'fixture-minimal', [
                'name' => 'Fixture Minimal',
            ]))->valid()
        );
    }

    public function test_complete_structured_npc_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('npc', 'fixture-shopkeeper', $this->npcData()))->valid()
        );
    }

    public function test_npc_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('npc');

        self::assertNotNull($schema);
        foreach ([
            'identity', 'roles', 'affiliations', 'locations', 'relationships', 'appearance',
            'personality', 'goals', 'secrets', 'mannerisms', 'dialogue', 'lore_hooks', 'combat',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected NPC schema field: ' . $field);
        }
    }

    public function test_identity_aliases_and_titles_must_be_string_lists(): void
    {
        $data = $this->npcData();
        $data['identity']['aliases'] = ['Good Alias', ''];
        $data['identity']['titles'] = 'Keeper';
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-identity-lists', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['identity.aliases.1', 'identity.titles'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_identity_rejects_unknown_fields(): void
    {
        $data = $this->npcData();
        $data['identity']['favourite_spoon'] = 'silver';
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-identity-key', $data));

        self::assertFalse($result->valid());
        self::assertSame('identity.favourite_spoon', $result->errors()[0]->field());
    }

    public function test_roles_locations_and_character_notes_are_string_lists(): void
    {
        $data = $this->npcData();
        $data['roles'] = ['shopkeeper', ''];
        $data['locations'] = [123];
        $data['goals'] = [''];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-lists', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['roles.1', 'locations.0', 'goals.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_affiliations_require_target_reference(): void
    {
        $data = $this->npcData();
        $data['affiliations'] = [['role' => 'member']];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-affiliation', $data));

        self::assertFalse($result->valid());
        self::assertSame('affiliations.0.target', $result->errors()[0]->field());
    }

    public function test_relationships_require_target_and_type(): void
    {
        $data = $this->npcData();
        $data['relationships'] = [['label' => 'Mysterious acquaintance']];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-relationship', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['relationships.0.target', 'relationships.0.type'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_dialogue_requires_unique_key_and_text(): void
    {
        $data = $this->npcData();
        $data['dialogue'] = [
            ['key' => 'greeting', 'text' => 'Hello.'],
            ['key' => 'greeting', 'text' => 'Hello again.'],
            ['key' => 'missing-text'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-dialogue', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['dialogue.1.key', 'dialogue.2.text'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_lore_hooks_require_unique_key_and_name(): void
    {
        $data = $this->npcData();
        $data['lore_hooks'] = [
            ['key' => 'rumour', 'name' => 'First Rumour'],
            ['key' => 'rumour', 'name' => 'Second Rumour'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-hooks', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['lore_hooks.1.key', 'lore_hooks.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_lore_hook_references_are_canonical_string_lists(): void
    {
        $data = $this->npcData();
        $data['lore_hooks'][0]['references'] = ['first-almanac:adventure:fixture', ''];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-hook-reference', $data));

        self::assertFalse($result->valid());
        self::assertSame('lore_hooks.0.references.1', $result->errors()[0]->field());
    }

    public function test_combat_requires_monster_reference_when_supplied(): void
    {
        $data = $this->npcData();
        $data['combat'] = ['variant' => 'shopkeeper'];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-combat', $data));

        self::assertFalse($result->valid());
        self::assertSame('combat.monster', $result->errors()[0]->field());
    }

    public function test_combat_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->npcData();
        $data['combat']['rules'] = [
            ['kind' => 'modifier', 'target' => 'armour-class'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-combat-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['combat.rules.0.operation', 'combat.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_roles_relationship_types_and_species_allow_marketrealm_oddities(): void
    {
        $data = $this->npcData();
        $data['identity']['species'] = 'possibly-sentient-soup';
        $data['roles'] = ['assistant-deputy-night-manager-of-the-gravy-aisle'];
        $data['relationships'] = [[
            'target' => 'first-almanac:npc:fixture-rival',
            'type' => 'mutually-suspicious-condiment-acquaintance',
        ]];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('npc', 'future-oddity', $data))->valid()
        );
    }

    public function test_dialogue_and_lore_hooks_must_be_structured_maps(): void
    {
        $data = $this->npcData();
        $data['dialogue'] = ['hello'];
        $data['lore_hooks'] = [[]];
        $result = $this->validator()->validate(new ContentDefinition('npc', 'bad-structured-content', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['dialogue.0', 'lore_hooks.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }
}
