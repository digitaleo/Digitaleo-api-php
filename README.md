# Digitaleo-api-php

PHP wrapper to call Digitaleo APIs, two wrappers are available

 * v1/Digitaleo.php => DEPRECATED
 * v2/Digitaleo.php

Sample code to use DigitaleoOauth.php :

```php
$httpClient = new DigitaleoOauth();
$httpClient->setBaseUrl('<api_base_url>')
$httpClient->setOauthPasswordCredentials(
    'https://oauth.messengeo.net/token',
    '<client_id>',
    '<client_secret>',
    '<login>',
    '<password>');
$httpClient->callGet('my_resource_name');
```
