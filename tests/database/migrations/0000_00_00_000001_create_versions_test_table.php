<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVersionsTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approvable_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status');
            $table->datetime('status_at')->useCurrent();
            $table->boolean('is_deleting')->default(0);
            $table->boolean('is_created')->default(0);

            $table->integer('approvable_id')->unsigned()->nullable();
            $table->string('approvable_type')->nullable();

            $table->integer('approvable_parent_id')->unsigned()->nullable();
            $table->string('approvable_parent_type')->nullable();

            $table->integer('user_id')->unsigned()->nullable();
            $table->string('user_type')->nullable();

            $table->text('values')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['approvable_id', 'approvable_type'], 'approvable');
            $table->index(['approvable_parent_id', 'approvable_parent_type'], 'approvable_parent');
            $table->index(['user_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('approvable_versions');
    }
}
