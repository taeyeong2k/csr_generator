<?php

namespace App\Providers\CSRConfig;

class CSRConfig
{
    protected String $country;
    protected String $state;
    protected String $city;
    protected String $organization;
    protected String $email;
    protected array $domains;

    public function __construct(string $country, string $state, string $city, string $organization, string $email, array $domains) {
        $this->country = $country;
        $this->state = $state;
        $this->city = $city;
        $this->organization = $organization;
        $this->email = $email;
        $this->domains = $domains;
    }

    public function getConfig(): array
    {
        return [
            'Country' => $this->country,
            'State' => $this->state,
            'City' => $this->city,
            'Organization' => $this->organization,
            'Email' => $this->email,
            'Domains' => $this->domains,
        ];
    }
}
