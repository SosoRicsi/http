<?php declare(strict_types=1);

namespace SosoRicsi\Http;

class Response
{
	protected int $status_code;

	protected array $headers = [];

	protected mixed $body;

	public function __construct(int $status_code = 200, mixed $body = null)
	{
		$this->status_code = $status_code;
		$this->body = $body;
	}

	public function setStatusCode(int $code): self
	{
		$this->status_code = $code;
		return $this;
	}

	public function getStatusCode(): int
	{
		return $this->status_code;
	}

	public function addHeader(string $key, string $value): self
	{
		$this->headers[$key] = $value;
		return $this;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setBody(mixed $body): self
	{
		$this->body = $body;
		return $this;
	}

	public function getBody(): mixed
	{
		return $this->body;
	}

	public function send(bool $reset = false): void
	{
		http_response_code($this->status_code);

		foreach ($this->headers as $key => $value) {
			header("{$key}: {$value}");
		}

		if (!empty($this->body)) {
			echo is_array($this->body) ? json_encode($this->body) : $this->body;
		}

		if ($reset) {
			$this->reset();
		}
	}

	protected function reset(): void
	{
		$this->status_code = 200;
		$this->headers = [];
		$this->body = null;
	}
}
