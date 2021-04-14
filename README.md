# Magento 2 extension

Magento 2 extension to import orders into the MyParcel.com platform.

## Installation

```shell
composer require myparcelcom/magento
```

If you see an exception regarding a PHP HTTP client, install one, for example:

```shell
composer require php-http/guzzle6-adapter
```

## Usage

- Connect to your MyParcel.com account via your admin panel: `Stores > Configuration > MyParcel.com > General`
- Export your Magento orders to MyParcel.com via your order overview: `Sales > Orders > Actions`
