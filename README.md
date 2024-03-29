# Magento 2 extension

Magento 2 extension to import orders into the MyParcel.com platform.

## Installation

```shell
composer require myparcelcom/magento
```

If you see an exception regarding a PHP HTTP client, install one, for example:

```shell
composer require php-http/guzzle7-adapter
```

## Usage

- Connect to your MyParcel.com account via your admin panel: `Stores > Configuration > MyParcel.com > General`
- Export your Magento orders to MyParcel.com via your order overview: `Sales > Orders > Actions`
- Print your MyParcel.com shipment labels via your order overview: `Sales > Orders > Actions`
- Automatically see status updates from MyParcel.com shipments in your order overview.
