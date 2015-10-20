# Digitaleo-api-php

PHP wrapper to call Digitaleo APIs, two wrappers are available

 * Digitaleo.php => DEPRECATED
 * DigitaleoOauth.php

Sample code to use DigitaleoOauth.php :

```php
$httpClient = new \Deo\Rest\DigitaleoOauth();
$httpClient->setBaseUrl('api_base_url')
$httpClient->setOauthPasswordCredentials('client_id', 'client_secret', 'login', 'password');
$httpClient->callGet('my_resource_name');
```