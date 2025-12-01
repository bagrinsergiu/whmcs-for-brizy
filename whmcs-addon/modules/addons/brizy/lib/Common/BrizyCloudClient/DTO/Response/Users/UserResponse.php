<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Users;

class UserResponse
{
    private $id;
    private $email;
    private $nameL;
    private $nameF;
    private $products;
    private $token;

    public function __construct(
        $id,
        $email,
        $nameL,
        $nameF,
        array $products,
        $token = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->nameL = $nameL;
        $this->nameF = $nameF;
        $this->products = $products;
        $this->token = $token;
    }

    public static function fromArray(array $data): self
    {
        $item = $data['data'] ?? $data;

        return new self(
            (int) $item['id'],
            $item['email'],
            $item['name_l'],
            $item['name_f'],
            $item['products'],
            $item['token'] ?? null
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getNameL(): string
    {
        return $this->nameL;
    }

    public function getNameF(): string
    {
        return $this->nameF;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name_l' => $this->nameL,
            'name_f' => $this->nameF,
            'products' => $this->products,
            'token' => $this->token
        ];
    }
}
