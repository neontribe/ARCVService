<?php

namespace Tests\Unit\Traits;

use App\Traits\Aliasable;
use Config;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class AliasableTraitTest extends TestCase
{
    private $TraitClassObject;

    public function setUp(): void
    {
        parent::setUp();

        // let's add Aliasable to a plain model class and stash it as an object
        $this->TraitClassObject = new class extends Model {
            use Aliasable;

            /**
             * Trait overrides for names
             * @var string[]
             */
            public $programmeAliases = [];
        };
    }

    /** @test */
    public function itCanSupplyAnAlias()
    {
        // get the thing
        $entity = $this->TraitClassObject;

        // function exists and returns a string
        $this->assertTrue(method_exists($entity, "getAlias"));
        $this->assertIsString($entity->getAlias());

        // oops, we forgot to configure the arc.programmes or any!
        Config::set('arc.programmes', []);
        // fall back on the classname
        $this->assertEquals(class_basename(get_class($entity)), $entity->getAlias());

        // now set it up properly!
        $entity->programmeAliases = ['First', 'Second'];
        Config::set('arc.programmes', ['programme 1', 'programme 2']);

        // when we don't pass a var, we get array 0;
        $this->assertEquals($entity->programmeAliases[0], $entity->getAlias());
        // otherwise we can pass it an int and get the right alias
        $this->assertEquals($entity->programmeAliases[1], $entity->getAlias(1));
        // and if we go out of bounds it gives us the classname again
        $this->assertEquals(class_basename(get_class($entity)), $entity->getAlias(2));
    }
}
