<?php declare(strict_types=1);

namespace SosoRicsi\Http;

class Request
{
	protected string $method;

	protected string $uri;

	protected array $headers;

	protected mixed $body;

	public array $validator_errors = [];

	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		$this->uri = $_SERVER['REQUEST_URI'] ?? '/';

		$this->headers = getallheaders();

		$this->body = file_get_contents('php://input');
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getHeader(string $key, mixed $default = null): mixed
	{
		return $this->headers[$key] ?? $default;
	}

	public function getBody(?string $key = null): mixed
	{
		$data = json_decode($this->body, true);

		return $key != null ? $data->$key : $data;
	}


	public function getJsonBody(?string $key = null): array
	{
		$data = json_decode($this->body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Invalid JSON format!");
		}


		if ($key != null) {
			return $data[$key];
		}

		return $data;
	}

	public function get(string $key = null): mixed
	{
		return $key != null ? $_GET[$key] : $_GET;
	}


	public function isContentType(string $type): bool
	{
		return $this->getHeader('Content-Type') === $type;
	}


	public function isJSON(): bool
	{
		return $this->isContentType('application/json');
	}

	public function isFormData(): bool
	{
		return $this->isContentType('multipart/form-data');
	}

}
