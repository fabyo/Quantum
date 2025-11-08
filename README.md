# Quantum Monorepo – Laravel + Vue + Hexagonal Architecture

<img src="quantum.png" alt="Golang" width="200" />

This repository brings together a **Laravel backend** and a **Vue frontend** in a single monorepo, with a strong focus on:

- Hexagonal / Clean Architecture
- Well-structured automated tests (Feature + Unit)
- SPA authentication with Laravel Sanctum
- Smooth integration between the API and a Vue 3 frontend

---

## 🏗️ Project Structure: Monorepo

The first key decision was the **project structure**.

- **Monorepo:** instead of two separate repositories, we use a single GitHub repository (`Quantum`).
- **Folder layout:**

  ```text
  /backend   # Laravel project (API)
  /frontend  # Vue 3 project (SPA)
  ```

- **Single `.gitignore` at the root:** one `.gitignore` file controls what should be ignored for **both** projects:

  - `.env`
  - `vendor/`
  - `node_modules/`

This keeps version control simple and centralizes the configuration of files that must not be committed.

---

## 🚀 Backend – Laravel + Hexagonal Architecture

Most of the heavy lifting is on the backend side, focused on **architecture** and **testing**.

### High-Level Architecture

We adopted **Hexagonal Architecture (Clean Architecture)** to isolate **business logic** from the framework.

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

#### `app/Domain` – The Core (Pure PHP)

- **Entities:** simple classes (POPOs) representing domain data, for example:

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

  No `extends Model`, no Eloquent, no Laravel dependencies.

- **Interfaces:** contracts describing what the “outside world” must provide:

  ```php
  // app/Domain/Interfaces/ProductRepositoryInterface.php
  interface ProductRepositoryInterface
  {
      public function create(Product $product): Product;
  }
  ```

#### `app/Application` – Use Cases

- **Use Cases:** orchestrate business logic and depend only on domain interfaces:

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

No direct dependencies on Eloquent, HTTP requests, responses, or any infrastructure detail.

#### `app/Infrastructure` – The Real World

Here is where Laravel comes in.

- **Eloquent Repositories:** concrete implementations of domain interfaces using Eloquent:

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

- **Eloquent Models:** live under `App\Models` (for example, `App\Models\Product`).

- **Controllers:** thin adapters that receive the request, call the use case, and return the response:

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

### Authentication – Laravel Sanctum

SPA authentication is handled by **Laravel Sanctum**, using **sessions and httpOnly cookies**, instead of storing tokens in `localStorage`.

- Stronger protection against XSS.
- Fully compatible with Axios calls using `withCredentials: true` in the frontend.

### Route Registration (The Classic 404)

Modern, more “minimal” Laravel installations do **not** automatically register `routes/api.php`.

- Result: calls to `/api/...` return `404`, even though the routes exist in `routes/api.php`.

**Fix:** ensure the routes are registered in `bootstrap/app.php` using `withRouting()`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <- THIS LINE IS CRITICAL
        // ...
    )
    ->create();
```

From there on, routes defined in `routes/api.php` will respond correctly under the `/api/...` prefix.

---

## 🧪 Testing Strategy (PHPUnit)

Backend quality is enforced through two main testing layers:

- **Feature Tests** – end-to-end flow (HTTP → Database)
- **Unit Tests** – isolated business logic (Use Cases)

### Feature Tests – `tests/Feature`

Goal: validate the flow **“from the outside in”**.

Key tools:

- `use RefreshDatabase;` – clean database for each test (including `:memory:` for `.env.testing`).
- `Sanctum::actingAs($user);` – simulate an authenticated user.
- `$this->postJson(...)` – simulate real HTTP requests.
- `assertStatus(201)` – assert HTTP status codes.
- `assertDatabaseHas(...)` – assert that data was actually persisted.

Example:

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

During development, feature tests helped catch issues such as:

- `404` – API routes not registered in `bootstrap/app.php`.
- `500` – missing classes (`CreateProductRequest`, `Product` model, etc.).
- `401` – missing or misconfigured authentication middleware.

### Unit Tests – `tests/Unit`

Goal: test **business logic** (Use Cases) without touching the database or framework.

Tools:

- **Mockery** – to create mocks for `ProductRepositoryInterface`.
- **Full isolation** – the use case believes it talks to a repository, but it’s just a mock.

Example:

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

This is where we caught issues such as type mismatches (`float 100.0` vs integer `100`) triggering `NoMatchingExpectationException` in Mockery.

---

## 🎨 Frontend – Vue 3 + Vite

The frontend is built on **Vue 3**, with build and dev server provided by **Vite**.

### Stack Overview

- **Framework:** Vue 3 (Composition API)
- **Build / Dev:** Vite (`npm run dev`)
- **Global State:** Pinia
- **Routing:** Vue Router
- **HTTP Client:** Axios

### State Management – Pinia

We use an `authStore` responsible for:

- Keeping the authenticated user (`user`).
- Exposing a `isLoggedIn` flag.
- Handling login, logout and session loading.

Example concept:

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

### Routing – Vue Router + Navigation Guards

We protect private routes using **navigation guards** (`beforeEach`):

```ts
router.beforeEach((to, from, next) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return next({ name: 'login' });
  }

  next();
});
```

Routes such as `/dashboard` are only accessible when the user is authenticated.

### API Communication – Axios + Sanctum Cookies

To make Sanctum work properly, Axios is configured as follows:

```ts
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true, // send session cookies
});
```

We also define **interceptors** to:

- Catch `401` errors returned by the API.
- Automatically trigger Pinia’s logout action.
- Redirect to the login page when the session expires.

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

## ▶️ Getting Started

### Backend (Laravel)

```bash
cd backend

cp .env.example .env
# configure database, APP_URL, etc.

composer install
php artisan key:generate
php artisan migrate

php artisan serve
```

### Frontend (Vue 3)

```bash
cd frontend

cp .env.example .env
# configure VITE_API_URL pointing to the backend

npm install
npm run dev
```

---

## ✅ Project Goals

This monorepo is meant to be a **modern, practical example** of:

- How to structure a **Laravel + Vue** monorepo.
- How to apply **Hexagonal Architecture** in Laravel without hacks.
- How to organize a healthy test suite (Feature + Unit).
- How to integrate a Vue 3 SPA with a Laravel backend using **Sanctum + secure cookies**.

Feel free to clone, explore, adapt, and evolve this codebase for your own projects.
