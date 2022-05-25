<?php

namespace Tests\Unit\Traits;

use App\Traits\Aliasable;
use Config;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class AliasableTraitTest extends TestCase
{
    private $UnaliasedTraitClassObject;
    private $AliasedTraitClassObject;

    public function setUp(): void
    {
        parent::setUp();

        $this->UnaliasedTraitClassObject = new class extends Model {
            use Aliasable;

            /**
             * Trait overrides for names
             * @var string[]
             */
            public const PROGRAMME_ALIASES = [];
        };


        $this->AliasedTraitClassObject = new class extends Model {
            use Aliasable;

            /**
             * Trait overrides for names
             * @var string[]
             */
            public const PROGRAMME_ALIASES = ['first', 'second'];
        };

    }

    /** @test */
    public function itCanSupplyAnAlias()
    {
        // function exists and returns a string
        $entity = $this->UnaliasedTraitClassObject;
        $this->assertTrue(method_exists($entity, "getAlias"));
        $this->assertIsString($entity::getAlias());

        // oops, we forgot to configure the arc.programmes!
        Config::set('arc.programmes', []);
        // fall back on the classname
        $this->assertEquals(class_basename(get_class($entity)), $entity::getAlias());

        // now set it up som programmes
        Config::set('arc.programmes', ['programme 1', 'programme 2']);

        // get the properly configured thing
        $entity = $this->AliasedTraitClassObject;

        // when we don't pass a var, we get array 0;
        $this->assertEquals($entity::PROGRAMME_ALIASES[0], $entity::getAlias());
        // otherwise we can pass it an int and get the right alias
        $this->assertEquals($entity::PROGRAMME_ALIASES[1], $entity::getAlias(1));
        // and if we go out of bounds it gives us the classname again
        $this->assertEquals(class_basename(get_class($entity)), $entity::getAlias(2));
    }
}
