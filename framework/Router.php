<?php declare(strict_types=1);

namespace SosoRicsi\Http;

use SosoRicsi\Http\Request;
use SosoRicsi\Http\Response;

class Router
{
	private array $routes = [];

	private array $errors = [];

	/**
	 * @var string $currentGroupPrefix Prefix for route groups, used for grouping related routes.
	 */
	private string $currentGroupPrefix = '';

	private array $currentGroupMiddleware = [];

	private string $version = "";

	private const METHOD_GET = 'GET';
	private const METHOD_HEAD = 'HEAD';
	private const METHOD_POST = 'POST';
	private const METHOD_PUT = 'PUT';
	private const METHOD_PATCH = 'PATCH';
	private const METHOD_DELETE = 'DELETE';
	private const METHOD_OPTIONS = 'OPTIONS';

	public function setVersion(?string $version = ""): void
	{
		$this->version = $version;
	}

	public function get(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_GET, $path, $handler, $middleware ?? []);
	}

	public function post(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_POST, $path, $handler, $middleware ?? []);
	}

	public function put(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PUT, $path, $handler, $middleware ?? []);
	}

	public function patch(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PATCH, $path, $handler, $middleware ?? []);
	}

	public function delete(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_DELETE, $path, $handler, $middleware ?? []);
	}

	public function options(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_OPTIONS, $path, $handler, $middleware ?? []);
	}

	public function redirect(string $path, string $redirectTo): void
	{
		$handler = function () use ($redirectTo) {
			header("Location: " . $redirectTo);
			exit();
		};
		$this->addRoute(self::METHOD_GET, $path, $handler);
	}

	public function group(string $prefix, \Closure $callback, array $middleware = [], ?string $version = ''): void
	{
		$previousPrefix = $this->currentGroupPrefix;
		$previousMiddleware = $this->currentGroupMiddleware;

		$this->currentGroupPrefix .= $prefix;
		$this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$callback($this);

		// Restore previous group settings after the callback is executed
		$this->currentGroupPrefix = $previousPrefix;
		$this->currentGroupMiddleware = $previousMiddleware;
	}

	public function version(\Closure $callback, array $middleware = [], ?string $prefix = '', ?string $version = ''): void
	{
		$previousPrefix = $this->currentGroupPrefix;
		$previousMiddleware = $this->currentGroupMiddleware;

		// Use provided prefix or default to '/api/v{version}'
		if (!empty($prefix)) {
			$this->currentGroupPrefix = $prefix;
		} else {
			$this->currentGroupPrefix = !empty($version) ? "/api/v{$version}" : "/api/v{$this->version}";
		}

		$this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$callback($this);

		// Restore previous group settings
		$this->currentGroupPrefix = $previousPrefix;
		$this->currentGroupMiddleware = $previousMiddleware;
	}

	/**
	 * Add a route to the router.
	 *
	 * @param string $method The HTTP method for the route (e.g., GET, POST).
	 * @param string $path The path for the route.
	 * @param mixed $handler The handler (callback or class method) for the route.
	 * @param array $middleware Optional middleware for the route.
	 * @return void
	 */
	private function addRoute(string $method, string $path, mixed $handler, array $middleware = []): void
	{
		$fullPath = $this->currentGroupPrefix . $path;
		$fullMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$this->routes[] = [
			'method' => $method,
			'path' => $fullPath,
			'handler' => $handler,
			'middleware' => $fullMiddleware,
		];
	}

	private function match(string $requestPath, string $path, array &$params): bool
	{
		$pathParts = explode('/', trim($path, '/'));
		$requestParts = explode('/', trim($requestPath, '/'));

		if (count($pathParts) !== count($requestParts)) {
			return false;
		}

		foreach ($pathParts as $index => $part) {
			if (preg_match('/\{(\w+)(?::(.+))?\}/', $part, $matches)) {
				$paramName = $matches[1];
				$pattern = $matches[2] ?? '.*';

				if (!preg_match('/^' . $pattern . '$/', $requestParts[$index])) {
					return false;
				}
				$params[$paramName] = $requestParts[$index];
			} elseif ($part !== $requestParts[$index]) {
				return false;
			}
		}
		return true;
	}

	private function resolveDependencies(array $params, mixed $handler): array
	{
		$dependencies = [];

		// If handler is a class method reference (array format)
		if (is_array($handler) && isset($handler[0], $handler[1])) {
			$reflection = new \ReflectionMethod($handler[0], $handler[1]);
		} elseif ($handler instanceof \Closure || is_string($handler)) {
			$reflection = new \ReflectionFunction($handler);
		} else {
			throw new \InvalidArgumentException('Invalid handler type.');
		}

		foreach ($reflection->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			$paramType = $parameter->getType();

			if (array_key_exists($paramName, $params)) {
				$dependencies[] = $params[$paramName];
			} elseif ($paramType && !$paramType->isBuiltin()) {
				$dependencies[] = (new ($paramType->getName())());
			} else {
				$dependencies[] = $parameter->isDefaultValueAvailable()
					? $parameter->getDefaultValue()
					: null;
			}
		}

		return $dependencies;
	}

	public function errors(array $errors): void
	{
		foreach ($errors as $error) {
			$this->errors[$error['error']] = [
				'handler' => $error['handler']
			];
		}
	}

	public function info(?bool $showRoutes = false, ?bool $showErrorHandlers = false): void
	{
		$has404Handler = array_key_exists('404', $this->errors) ? "true" : "false";
		$methodsCount = array_fill_keys(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], 0);
		$version = $this->version ?: "N/A";

		foreach ($this->routes as $route) {
			$methodsCount[$route['method']]++;
		}

		print "<pre>";
		print "Routes count: " . count($this->routes) . "\n";
		print "Has 404 handler: {$has404Handler}\n";
		print "Current app version: {$version}\n";
		print "Counted methods: ";
		print_r($methodsCount);
		if ($showRoutes) {
			print "Routes: ";
			print_r($this->routes);
		}
		if ($showErrorHandlers) {
			print "Error handlers: ";
			print_r($this->errors);
		}
		print "</pre>";
	}

	public function run(string $uri = null, string $method = null): void
	{
		$requestUri = parse_url($uri ?? $_SERVER['REQUEST_URI']);
		$requestPath = $requestUri['path'];
		$method = $method ?? $_SERVER['REQUEST_METHOD'];

		$params = [];

		foreach ($this->routes as $route) {
			if ($route['method'] === $method && $this->match($requestPath, $route['path'], $params)) {
				// Execute any middleware for the route
				foreach ($route['middleware'] as $middleware) {
					$middlewareInstance = new $middleware;
					if (!$middlewareInstance->handle(new Request, new Response)) {
						return;
					}
				}

				$callback = $route['handler'];
				$dependencies = $this->resolveDependencies($params, $callback);

				if (is_array($callback)) {
					[$controller, $method] = $callback;
					if (class_exists($controller) && method_exists($controller, $method)) {
						call_user_func_array([new $controller(), $method], $dependencies);
						return;
					}
				} else {
					call_user_func_array($callback, $dependencies);
					return;
				}
			}
		}

		header("HTTP/1.0 404 Not Found");
		if (array_key_exists('404', $this->errors)) {
			call_user_func($this->errors['404']['handler']);
		} else {
			print "404 - Page Not Found!";
		}
	}
}
