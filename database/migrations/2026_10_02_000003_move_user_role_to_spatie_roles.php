<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Memindahkan kolom users.role ke role Spatie, lalu menghapus kolom tersebut
return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'admin' => Role::findOrCreate('admin', 'web'),
            'customer' => Role::findOrCreate('customer', 'web'),
        ];

        foreach (DB::table('users')->select('id', 'role')->get() as $user) {
            if (isset($roles[$user->role])) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roles[$user->role]->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('password'); // admin | customer
        });

        $adminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'admin')
            ->where('model_has_roles.model_type', User::class)
            ->pluck('model_has_roles.model_id');

        DB::table('users')->whereIn('id', $adminIds)->update(['role' => 'admin']);
    }
};
