<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserType;
use App\Enums\UserStatus;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Modificar o campo name para full_name
            $table->renameColumn('name', 'full_name');

            // Adicionar novos campos
            $table->string('cpf')->unique()->after('id');
            $table->string('type')->after('email');
            $table->string('status')->after('type');
            $table->string('phone')->nullable()->after('status');
            $table->string('recovery_token')->nullable()->after('phone');
            $table->boolean('password_reset_required')->default(false)->after('phone');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('zip_code')->nullable()->after('birth_date');
            $table->string('address')->nullable()->after('zip_code');
            $table->string('address_number')->nullable()->after('address');
            $table->string('complement')->nullable()->after('address_number');
            $table->string('neighborhood')->nullable()->after('complement');
            $table->string('city')->nullable()->after('neighborhood');
            $table->string('state')->nullable()->after('city');
            $table->timestamp('last_access_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverter alterações
            $table->renameColumn('full_name', 'name');
            $table->dropColumn([
                'cpf',
                'type',
                'status',
                'phone',
                'birth_date',
                'zip_code',
                'address',
                'address_number',
                'complement',
                'neighborhood',
                'city',
                'state',
                'last_access_at'
            ]);
        });
    }
};
