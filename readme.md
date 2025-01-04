# Router Class Documentation

The `Router` class is a flexible and feature-rich implementation designed for managing HTTP routing in PHP applications. Below is a detailed explanation of its features and usage.

---

## Features

- Supports all common HTTP methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD`
- Grouping and versioning routes
- Middleware support
- Dependency injection for route handlers
- Dynamic path parameters with regex support
- Redirect functionality
- Custom error handling (e.g., 404 errors)
- Informational output for debugging routes and errors

---

## Installation

Add the `Router` class to your project and include the necessary namespaces:

```php
use SosoRicsi\Http\Router;
use SosoRicsi\Http\Request;
use SosoRicsi\Http\Response;
```

---

## Usage

### Basic Routing

Define routes using the provided methods:

```php
$router = new Router();

$router->get('/home', function () {
    echo 'Welcome to the homepage!';
});

$router->post('/submit', function (Request $request) {
    echo 'Form submitted';
});
```

### Grouping Routes

Group related routes with a common prefix and middleware:

```php
$router->group('/admin', function (Router $router) {
    $router->get('/dashboard', function () {
        echo 'Admin Dashboard';
    });
}, [AdminMiddleware::class]);
```

### Versioning

Define API versions easily:

```php
$router->version(function (Router $router) {
    $router->get('/users', function () {
        echo 'List of users';
    });
}, [], '/api/v1');
```

### Middleware

Apply middleware to routes:

```php
$router->get('/profile', function () {
    echo 'User profile';
}, [AuthMiddleware::class]);
```

### Redirects

Redirect one route to another:

```php
$router->redirect('/old-route', '/new-route');
```

### Error Handling

Define custom error handlers:

```php
$router->errors([
    [
        'error' => '404',
        'handler' => function () {
            echo 'Custom 404 Page Not Found';
        }
    ]
]);
```

### Running the Router

Execute the router based on the current request:

```php
$router->run();
```

Alternatively, specify the URI and method manually:

```php
$router->run('/test', 'GET');
```

---

## Advanced Features

### Dynamic Path Parameters

Use curly braces for dynamic parameters, optionally with regex:

```php
$router->get('/user/{id:\d+}', function ($id) {
    echo "User ID: $id";
});
```

### Dependency Injection

Automatically inject dependencies into route handlers:

```php
$router->get('/inject', function (Request $request, Response $response) {
    $response->setBody('Injected dependencies!');
});
```

### Debugging

View routes and error handlers for debugging:

```php
$router->info(true, true);
```

---

## Methods

### `get($path, $handler, $middleware = null)`
Defines a GET route.

### `post($path, $handler, $middleware = null)`
Defines a POST route.

### `put($path, $handler, $middleware = null)`
Defines a PUT route.

### `patch($path, $handler, $middleware = null)`
Defines a PATCH route.

### `delete($path, $handler, $middleware = null)`
Defines a DELETE route.

### `options($path, $handler, $middleware = null)`
Defines an OPTIONS route.

### `redirect($path, $redirectTo)`
Redirects a route to another location.

### `group($prefix, $callback, $middleware = [], $version = '')`
Groups related routes with a prefix and middleware.

### `version($callback, $middleware = [], $prefix = '', $version = '')`
Defines a versioned route group.

### `errors($errors)`
Adds custom error handlers.

### `run($uri = null, $method = null)`
Processes the current request.

### `info($showRoutes = false, $showErrorHandlers = false)`
Displays debugging information about routes and error handlers.

---

## Example

```php
$router = new Router();

$router->get('/', function () {
    echo 'Welcome!';
});

$router->group('/api', function (Router $router) {
    $router->get('/users', function () {
        echo 'List of users';
    });
});

$router->run();
```

