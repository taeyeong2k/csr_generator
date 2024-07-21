## CSR Generator
Laravel site to generate CSRs and add the associated private key and csr files to vault.

### Usage
```
#!/bin/bash

# Clone the repository
git clone git@bitbucket.org:sidecloud/onelink-csr.git
cd onelink-csr

# Install dependencies
# You need the pdftk package, install using your package manager
sudo yum install pdftk
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Prompt the user for Vault details
read -p "Enter Vault Address: " vault_address
read -p "Enter Vault Username: " vault_username
read -sp "Enter Vault Password: " vault_password
read -p "Enter Slack Bot Token: " slack_token
read -p "Enter Slack Channel ID to send messages to: " slack_channel

# Update the .env file with Vault details
echo "VAULT_ADDRESS=$vault_address" >> .env
echo "VAULT_USERNAME=$vault_username" >> .env
echo "VAULT_PASSWORD=$vault_password" >> .env
echo "SLACK_CSR_CHANNEL=$slack_channel" >> .env
echo "SLACK_BOT_TOKEN=$slack_token" >> .env

# Run the site
php artisan serve
```
