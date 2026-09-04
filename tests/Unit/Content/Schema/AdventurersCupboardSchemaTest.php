<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class AdventurersCupboardSchemaTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    public function test_complete_weapon_is_valid(): void
    {
        $definition = new ContentDefinition('weapon', 'fixture-fork', [
            'name' => 'Fixture Fork',
            'category' => 'martial-melee',
            'damage' => ['dice' => '1d6', 'type' => 'piercing'],
            'properties' => ['finesse', ['key' => 'versatile', 'dice' => '1d8']],
            'range' => ['normal' => 20, 'long' => 60],
            'proficiency' => 'forks',
            'weight' => 2.5,
            'cost' => ['amount' => 5, 'currency' => 'gp'],
            'effects' => [['type' => 'fixture-effect']],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_weapon_requires_category_and_damage(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('weapon', 'empty-fork', ['name' => 'Empty Fork']));
        self::assertFalse($result->valid());
        self::assertSame(['category', 'damage'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_weapon_damage_requires_dice_and_type(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('weapon', 'bad-damage', [
            'name' => 'Bad Damage',
            'category' => 'martial',
            'damage' => ['dice' => ''],
        ]));
        self::assertFalse($result->valid());
        self::assertSame(['damage.dice', 'damage.type'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_weapon_range_must_be_sensible(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('weapon', 'bad-range', [
            'name' => 'Bad Range',
            'category' => 'ranged',
            'damage' => ['dice' => '1d4', 'type' => 'fixture'],
            'range' => ['normal' => 60, 'long' => 20],
        ]));
        self::assertFalse($result->valid());
        self::assertSame('range.long', $result->errors()[0]->field());
    }

    public function test_complete_armour_is_valid(): void
    {
        $definition = new ContentDefinition('armour', 'fixture-tin', [
            'name' => 'Fixture Tin',
            'category' => 'medium',
            'armour_class' => ['base' => 14, 'ability_modifier' => 'dexterity', 'modifier_cap' => 2],
            'strength_requirement' => 0,
            'stealth_disadvantage' => false,
            'properties' => ['fixture-lined'],
            'weight' => 20,
            'cost' => ['amount' => 50, 'currency' => 'gp'],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_armour_class_requires_non_negative_base(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('armour', 'bad-armour', [
            'name' => 'Bad Armour',
            'category' => 'heavy',
            'armour_class' => ['base' => -1],
        ]));
        self::assertFalse($result->valid());
        self::assertSame('armour_class.base', $result->errors()[0]->field());
    }

    public function test_complete_equipment_is_valid(): void
    {
        $definition = new ContentDefinition('equipment', 'fixture-rope', [
            'name' => 'Fixture Rope',
            'category' => 'adventuring-gear',
            'quantity' => 1,
            'consumable' => false,
            'properties' => ['utility'],
            'weight' => 10,
            'cost' => ['amount' => 1, 'currency' => 'gp'],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_equipment_quantity_and_weight_cannot_be_invalid(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('equipment', 'bad-gear', [
            'name' => 'Bad Gear',
            'category' => 'gear',
            'quantity' => 0,
            'weight' => -1,
        ]));
        self::assertFalse($result->valid());
        self::assertSame(['weight', 'quantity'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_cost_requires_amount_and_currency(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('equipment', 'bad-cost', [
            'name' => 'Bad Cost',
            'category' => 'gear',
            'cost' => ['amount' => -1],
        ]));
        self::assertFalse($result->valid());
        self::assertSame(['cost.amount', 'cost.currency'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_complete_magic_item_is_valid(): void
    {
        $definition = new ContentDefinition('magic-item', 'fixture-spoon', [
            'name' => 'Fixture Spoon of Testing',
            'category' => 'wondrous-item',
            'rarity' => 'rare',
            'attunement' => ['required' => true, 'requirements' => [['type' => 'class', 'key' => 'fixture-calling']]],
            'charges' => ['maximum' => 3, 'recharge' => 'dawn'],
            'properties' => ['fixture'],
            'effects' => [['type' => 'fixture-effect']],
            'modifiers' => [['target' => 'fixture-value', 'operation' => 'add', 'value' => 1]],
            'choices' => [['key' => 'fixture-choice']],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_magic_item_requires_category_and_rarity(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('magic-item', 'empty-magic', ['name' => 'Empty Magic']));
        self::assertFalse($result->valid());
        self::assertSame(['category', 'rarity'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_charges_require_positive_maximum(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('magic-item', 'bad-charges', [
            'name' => 'Bad Charges',
            'category' => 'wondrous',
            'rarity' => 'rare',
            'charges' => ['maximum' => 0],
        ]));
        self::assertFalse($result->valid());
        self::assertSame('charges.maximum', $result->errors()[0]->field());
    }

    public function test_attunement_requires_boolean_required_flag_and_map_requirements(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('magic-item', 'bad-attunement', [
            'name' => 'Bad Attunement',
            'category' => 'wondrous',
            'rarity' => 'rare',
            'attunement' => ['required' => 'yes', 'requirements' => ['wizard']],
        ]));
        self::assertFalse($result->valid());
        self::assertSame(
            ['attunement.required', 'attunement.requirements.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_properties_accept_keys_or_parameter_maps(): void
    {
        $valid = new ContentDefinition('weapon', 'property-weapon', [
            'name' => 'Property Weapon',
            'category' => 'martial',
            'damage' => ['dice' => '1d8', 'type' => 'fixture'],
            'properties' => ['finesse', ['key' => 'versatile', 'dice' => '1d10']],
        ]);
        self::assertTrue($this->validator()->validate($valid)->valid());

        $bad = new ContentDefinition('weapon', 'bad-property-weapon', [
            'name' => 'Bad Property Weapon',
            'category' => 'martial',
            'damage' => ['dice' => '1d8', 'type' => 'fixture'],
            'properties' => ['', []],
        ]);
        $result = $this->validator()->validate($bad);
        self::assertFalse($result->valid());
        self::assertSame(['properties.0', 'properties.1'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_effect_modifier_and_choice_payloads_are_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('magic-item', 'bad-payloads', [
            'name' => 'Bad Payloads',
            'category' => 'wondrous',
            'rarity' => 'rare',
            'effects' => ['bad'],
            'modifiers' => [[]],
            'choices' => ['bad'],
        ]));
        self::assertFalse($result->valid());
        self::assertSame(['effects.0', 'modifiers.0', 'choices.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_custom_categories_and_rarities_are_not_hard_coded(): void
    {
        $definition = new ContentDefinition('magic-item', 'future-nonsense', [
            'name' => 'Future Nonsense',
            'category' => 'enchanted-condiment-vessel',
            'rarity' => 'questionably-legendary',
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }
}
