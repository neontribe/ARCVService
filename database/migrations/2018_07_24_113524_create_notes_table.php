<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('notes', static function (Blueprint $table) {
            $table->increments('id');
            $table->text('content'); // 64k
            $table->integer('family_id')->unsigned(); // FK Families
            $table->integer('user_id')->unsigned(); // FK Centre Users
            $table->timestamps();

            $table->foreign('family_id')
                ->references('id')
                ->on('families');

            $table->foreign('user_id')
                ->references('id')
                ->on('centre_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
}
