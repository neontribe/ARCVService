<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('carers', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('family_id')->unsigned(); // FK Families
            $table->timestamps();

            $table->foreign('family_id')
                ->references('id')
                ->on('families');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('carers');
    }
}
