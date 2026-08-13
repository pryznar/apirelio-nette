# Apirelio quickstart

This is the smallest integration path for the package. It uses the synthetic customer `customer_42`; replace that resolver with your authenticated account lookup before production.

```bash
export APIRELIO_API_KEY=apr_live_your_project_key
composer create-project nette/web-project demo && cd demo && composer require apirelio/nette
```

Merge `ApirelioExtension.neon` into `config/common.neon` and copy `ApiPresenter.php` into your presentation layer.

Generate one request to the example endpoint, wait for the asynchronous batch to flush, then open the [live demo](https://apirelio.com/demo?utm_source=github&utm_medium=example&utm_campaign=nette) to understand the resulting customer-aware views.

The SDK never captures request or response payloads. Do not put secrets or personal data into customer identity or custom metadata.
