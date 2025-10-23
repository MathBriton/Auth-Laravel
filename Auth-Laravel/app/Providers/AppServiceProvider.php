<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validação do request customizada
        // Realiza a validação e retorna a primeira mensagem de erro

        Request::macro('validate', function (array $rules, array $messages = [], array $customAttributes = []) {
            $validator = Validator::make(request()->all(), $rules, $messages, $customAttributes);
            $validator->stopOnFirstFailure();

            if ($validator->fails()) {
                $response = response()->json([
                    'message' => $validator->errors()->first(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);

                throw new ValidationException($validator, $response);
            }

            return $validator->validate();
        });


        Gate::define('list', fn(User $user, string $permission) => $user->hasPermission($permission, 'list'));
        Gate::define('update', fn(User $user, string $permission) => $user->hasPermission($permission, 'update'));
        Gate::define('delete', fn(User $user, string $permission) => $user->hasPermission($permission, 'delete'));
        Gate::define('create', fn(User $user, string $permission) => $user->hasPermission($permission, 'create'));

        $this->defineUserGates('update');
    }

    protected function defineUserGates(string $action)
    {
        // Define gates de gerenciamento de usuários de acordo com nível
        Gate::define($action . ':user', function (User $user, User $targetedUser) use ($action) {
            switch ($targetedUser->type) {
                case UserType::MASTER:
                    return Gate::authorize($action, 'root');

                case UserType::ADMIN:
                    return Gate::authorize($action, 'admin');

                case UserType::MEMBER:
                    return Gate::authorize($action, 'member');

                case UserType::PRE_REGISTRATION:
                    return Gate::authorize($action, 'pre_registration');

                default:
                    throw new \Exception('TIPO DE USUÁRIO NÃO SUPORTADO');
            }
        });
    }
}
