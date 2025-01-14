<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('deliveries', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('centre_id')->unsigned();
            $table->string('range');
            $table->dateTime('dispatched_at');
            $table->timestamps();

            $table->foreign('centre_id')
                ->references('id')
                ->on('centres');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
}
