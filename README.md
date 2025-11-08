# Quantum Monorepo – Laravel + Vue + Hexagonal

Este repositório reúne **backend (Laravel)** e **frontend (Vue)** em um único monorepo, com foco em:

- Arquitetura Hexagonal / Clean Architecture
- Testes bem estruturados (Feature + Unit)
- Autenticação SPA com Laravel Sanctum
- Integração suave entre API e frontend Vue 3

---

## 🏗️ Estrutura: Monorepo

O primeiro conceito definido foi a **estrutura do projeto**.

- **Monorepo:** em vez de dois repositórios separados, utilizamos um único repositório no GitHub (`Quantum`).
- **Organização de pastas:**

  ```text
  /backend   # Projeto Laravel (API)
  /frontend  # Projeto Vue 3 (SPA)
  ```

- **.gitignore único na raiz:** um único arquivo `.gitignore` gerencia o que deve ser ignorado para **ambos** os projetos:

  - `.env`
  - `vendor/`
  - `node_modules/`

Isso simplifica o versionamento e mantém o controle centralizado dos artefatos que não devem subir para o Git.

---

## 🚀 Backend – Laravel + Arquitetura Hexagonal

A parte mais pesada do trabalho ficou no backend, com foco em **arquitetura** e **testes**.

### Visão Geral da Arquitetura

Adotamos **Arquitetura Hexagonal (Clean Architecture)** para isolar a **lógica de negócio** do framework.

```text
backend/
└── app/
    ├── Domain/
    │   ├── Entities/
    │   └── Interfaces/
    ├── Application/
    │   └── UseCases/
    └── Infrastructure/
        ├── Repositories/
        └── Http/Controllers/
```

#### `app/Domain` – O Núcleo (PHP puro)

- **Entidades:** classes simples (POPOs) que representam os dados de domínio, por exemplo:

  ```php
  // app/Domain/Entities/Product.php
  class Product
  {
      public function __construct(
          public string $name,
          public float $price,
      ) {}
  }
  ```

  Sem `extends Model`, sem dependência de Eloquent ou Laravel.

- **Interfaces:** contratos que definem o que o “mundo exterior” deve fazer:

  ```php
  // app/Domain/Interfaces/ProductRepositoryInterface.php
  interface ProductRepositoryInterface
  {
      public function create(Product $product): Product;
  }
  ```

#### `app/Application` – Casos de Uso

- **Use Cases:** orquestram a lógica de negócio, dependendo apenas das Interfaces do domínio:

  ```php
  // app/Application/UseCases/CreateProductUseCase.php
  class CreateProductUseCase
  {
      public function __construct(
          private ProductRepositoryInterface $products,
      ) {}

      public function execute(string $name, float $price): Product
      {
          $product = new Product($name, $price);

          return $this->products->create($product);
      }
  }
  ```

Nenhuma dependência direta de Eloquent, Request, Response ou qualquer detalhe de infraestrutura.

#### `app/Infrastructure` – O “Mundo Real”

Aqui o Laravel aparece de verdade.

- **Repositórios Eloquent:** implementam as interfaces do domínio usando Eloquent:

  ```php
  // app/Infrastructure/Repositories/EloquentProductRepository.php
  class EloquentProductRepository implements ProductRepositoryInterface
  {
      public function create(Product $product): Product
      {
          $model = ProductModel::create([
              'name'  => $product->name,
              'price' => $product->price,
          ]);

          return new Product($model->name, $model->price);
      }
  }
  ```

- **Models Eloquent:** vivem em `App\Models` (por exemplo, `App\Models\Product`).

- **Controllers:** atuam como adaptadores finos, recebendo o Request, chamando o Use Case e devolvendo um Response:

  ```php
  // app/Http/Controllers/ProductController.php
  class ProductController extends Controller
  {
      public function store(CreateProductRequest $request, CreateProductUseCase $useCase)
      {
          $product = $useCase->execute(
              $request->validated('name'),
              $request->validated('price'),
          );

          return response()->json($product, 201);
      }
  }
  ```

### Autenticação – Laravel Sanctum

A autenticação da SPA é feita com **Laravel Sanctum**, usando **sessões e cookies httpOnly**, sem guardar token em `localStorage`.

- Segurança maior contra XSS.
- Compatível com chamadas via `Axios` usando `withCredentials: true` no frontend.

### Registro de Rotas (Erro 404 Clássico)

Instalações modernas e mais “minimalistas” do Laravel **não registram** `routes/api.php` automaticamente.

- Resultado: chamadas para `/api/...` retornando `404`, mesmo com rotas definidas no arquivo.

**Solução:** garantir o registro das rotas no `bootstrap/app.php` via `withRouting()`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <- ESSA LINHA É FUNDAMENTAL
        // ...
    )
    ->create();
```

A partir daí, rotas definidas em `routes/api.php` passam a responder corretamente em `/api/...`.

---

## 🧪 Estratégia de Testes (PHPUnit)

A qualidade do backend foi garantida com uma abordagem em duas camadas:

- **Testes de Feature** – fluxo de ponta a ponta (HTTP → Banco)
- **Testes Unitários** – lógica de negócio isolada (Use Cases)

### Testes de Feature – `tests/Feature`

Objetivo: validar o fluxo **“de fora para dentro”**.

Ferramentas principais:

- `use RefreshDatabase;` – banco limpo a cada teste (incluindo uso de `:memory:` no `.env.testing`).
- `Sanctum::actingAs($user);` – para simular usuário autenticado.
- `$this->postJson(...)` – simulação de requisições HTTP reais.
- `assertStatus(201)` – checagem do status HTTP.
- `assertDatabaseHas(...)` – verificação se o dado realmente foi salvo.

Exemplo simplificado:

```php
public function test_an_authenticated_user_can_create_a_product(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/products', [
        'name'  => 'New Product',
        'price' => 199.99,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', [
        'name' => 'New Product',
    ]);
}
```

Durante o desenvolvimento, os testes ajudaram a encontrar erros como:

- `404` – rota de API não registrada no `bootstrap/app.php`.
- `500` – classes faltando (`CreateProductRequest`, `Product` Model etc.).
- `401` – ausência ou configuração incorreta de middleware de autenticação.

### Testes Unitários – `tests/Unit`

Objetivo: testar **lógica de negócio** (Use Cases) sem tocar em banco ou framework.

Ferramentas:

- **Mockery** – para criar mocks de `ProductRepositoryInterface`.
- **Isolamento total** – o use case acha que está falando com um repositório real, mas é apenas um mock.

Exemplo:

```php
public function test_create_product_use_case_uses_repository(): void
{
    $repository = Mockery::mock(ProductRepositoryInterface::class);
    $repository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::type(Product::class))
        ->andReturn(new Product('Test', 100.0));

    $useCase = new CreateProductUseCase($repository);

    $product = $useCase->execute('Test', 100.0);

    $this->assertSame('Test', $product->name);
}
```

Aqui detectamos, por exemplo, problemas de diferença de tipos (`float 100.0` vs inteiro `100`) gerando `NoMatchingExpectationException` no Mockery.

---

## 🎨 Frontend – Vue 3 + Vite

O frontend foi construído em **Vue 3**, com build e dev server fornecidos pelo **Vite**.

### Stack Principal

- **Framework:** Vue 3 (Composition API)
- **Build / Dev:** Vite (`npm run dev`)
- **Estado Global:** Pinia
- **Roteamento:** Vue Router
- **HTTP Client:** Axios

### Gerenciamento de Estado – Pinia

Criamos um `authStore` responsável por:

- Guardar o usuário autenticado (`user`).
- Expor um flag `isLoggedIn`.
- Lidar com login, logout e carregamento de sessão.

Exemplo de ideia geral:

```ts
// frontend/src/stores/auth.ts
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
  }),
  getters: {
    isLoggedIn: (state) => !!state.user,
  },
  actions: {
    setUser(user: User | null) {
      this.user = user;
    },
  },
});
```

### Roteamento – Vue Router + Navigation Guards

Utilizamos **navigation guards** (`beforeEach`) para proteger rotas autenticadas:

```ts
router.beforeEach((to, from, next) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return next({ name: 'login' });
  }

  next();
});
```

Rotas como `/dashboard` só são acessíveis se o usuário estiver autenticado.

### Comunicação com a API – Axios + Cookies Sanctum

Para que o Sanctum funcione corretamente, configuramos o Axios com:

```ts
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true, // envia cookies de sessão
});
```

Também configuramos **interceptors** para:

- Capturar erros `401` vindos da API.
- Disparar automaticamente o logout no Pinia.
- Redirecionar o usuário para a tela de login quando a sessão expira.

```ts
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const auth = useAuthStore();

    if (error.response?.status === 401) {
      auth.setUser(null);
      router.push({ name: 'login' });
    }

    return Promise.reject(error);
  },
);
```

---

## ▶️ Como Rodar o Projeto

### Backend (Laravel)

```bash
cd backend

cp .env.example .env
# configurar banco, APP_URL etc.

composer install
php artisan key:generate
php artisan migrate

php artisan serve
```

### Frontend (Vue 3)

```bash
cd frontend

cp .env.example .env
# configurar VITE_API_URL apontando para o backend

npm install
npm run dev
```

---

## ✅ Objetivo Final

Este monorepo foi pensado para ser um **exemplo prático e moderno** de:

- Como organizar um monorepo **Laravel + Vue**.
- Como aplicar **Arquitetura Hexagonal** em Laravel sem gambiarras.
- Como estruturar testes de forma saudável (Feature + Unit).
- Como integrar uma SPA Vue 3 com backend Laravel usando **Sanctum + cookies seguros**.

Sinta-se livre para clonar, estudar, adaptar e evoluir esta base para seus próprios projetos.
