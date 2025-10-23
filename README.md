# Guia de Autenticação e Controle de Acesso - Laravel Sanctum

## Funcionalidades Implementadas

1ª **Laravel Sanctum** para autenticação via tokens
2ª **Sistema de Permissões** com controle de acesso via Gates
3ª **Enums** para UserType e UserStatus
4ª **Modelo User** atualizado com todos os campos necessários
5ª **Modelo Permission** com estrutura action/resource/name
6ª **Controllers** para autenticação e gestão de permissões
7ª **Migrations** e **Seeders** configurados

## Estrutura do Banco de Dados

### Tabela Users

-   `id`, `cpf`, `email`, `full_name`, `type`, `status`
-   `phone`, `birth_date`, `zip_code`, `address`, `address_number`
-   `complement`, `neighborhood`, `city`, `state`, `last_access_at`

### Tabela Permissions

-   `id`, `action`, `resource`, `name`, `created_at`, `updated_at`

### Tabela User_Permissions (pivot)

-   `user_id`, `permission_id`

## Usuário de Teste

**Email:** `admin@example.com`
**Senha:** `password`
**CPF:** `12345678901`
**Tipo:** `ADMIN`
**Status:** `ACTIVE`

Este usuário já possui **todas as permissões** atribuídas.

## Endpoints da API

### Autenticação

#### 1. Login

```bash
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "web_browser"
}
```

#### 2. Logout

```bash
POST /api/auth/logout
Authorization: Bearer {seu_token}
```

#### 3. Dados do Usuário

```bash
GET /api/auth/me
Authorization: Bearer {seu_token}
```

#### 4. Atualizar Perfil

```bash
PUT /api/auth/profile
Authorization: Bearer {seu_token}
Content-Type: application/json

{
    "full_name": "Novo Nome",
    "phone": "(11) 99999-9999"
}
```

### Permissões

#### 1. Listar Permissões

```bash
GET /api/permissions
Authorization: Bearer {seu_token}
```

#### 2. Criar Permissão

```bash
POST /api/permissions
Authorization: Bearer {seu_token}
Content-Type: application/json

{
    "action": "view",
    "resource": "reports",
    "name": "Visualizar Relatórios"
}
```

#### 3. Atribuir Permissão a Usuário

```bash
POST /api/permissions/{permission_id}/assign
Authorization: Bearer {seu_token}
Content-Type: application/json

{
    "user_id": 1
}
```

## Teste das Gates

#### Endpoint de Teste

```bash
GET /api/test-gate
Authorization: Bearer {seu_token}
```

Este endpoint testa a autorização via Gate para `Gate::authorize('list', 'activity_log')`.

## Como Testar

### 1. Iniciar o Servidor

```bash
php artisan serve
```

### 2. Fazer Login

Use o endpoint de login para obter um token de acesso.

### 3. Testar Permissões

Use o token obtido para acessar os endpoints protegidos.

### 4. Verificar Gates

Acesse o endpoint `/api/test-gate` para verificar se as Gates estão funcionando.

## Comandos Artisan Úteis

### Atribuir Permissões a um Usuário

```bash
php artisan user:assign-permissions {email}
```

### Recriar o Banco com Dados de Teste

```bash
php artisan migrate:fresh --seed
```

## Estrutura de Permissões Criadas

O sistema já vem com as seguintes permissões:

### Activity Log

-   `list` - Listar Logs de Atividade
-   `create` - Criar Log de Atividade
-   `update` - Atualizar Log de Atividade
-   `delete` - Deletar Log de Atividade

### User

-   `list` - Listar Usuários
-   `create` - Criar Usuário
-   `update` - Atualizar Usuário
-   `delete` - Deletar Usuário

### Permission

-   `list` - Listar Permissões
-   `create` - Criar Permissão
-   `update` - Atualizar Permissão
-   `delete` - Deletar Permissão

## Verificação via Gates

Para verificar permissões em seus controllers, use:

```php
use Illuminate\Support\Facades\Gate;

// Exemplo: verificar se pode listar activity_log
Gate::authorize('list', 'activity_log');

// Exemplo: verificar se pode criar usuário
Gate::authorize('create', 'user');
```

## Configuração das Gates no AppServiceProvider

As Gates já estão configuradas no `AppServiceProvider::boot()`:

```php
Gate::define('list', fn(User $user, string $permission) => $user->hasPermission($permission, 'list'));
Gate::define('update', fn(User $user, string $permission) => $user->hasPermission($permission, 'update'));
Gate::define('delete', fn(User $user, string $permission) => $user->hasPermission($permission, 'delete'));
Gate::define('create', fn(User $user, string $permission) => $user->hasPermission($permission, 'create'));
```

## Enums Disponíveis

### UserType

-   `PRE_REGISTRATION` - Pré-cadastro
-   `MEMBER` - Membro
-   `MASTER` - Master
-   `ADMIN` - Administrador

### UserStatus

-   `ACTIVE` - Ativo
-   `SUSPENDED` - Suspenso
-   `EXCLUDED` - Excluído
-   `PENDING` - Pendente

---
