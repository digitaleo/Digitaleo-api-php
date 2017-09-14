# Digitaleo-api-php

PHP wrapper to call Digitaleo APIs, two wrappers are available

 * v1/Digitaleo.php => DEPRECATED
 * v2/Digitaleo.php

## Sample code to use v2/Digitaleo.php

**Init Digitaleo as client credentials**

```php
$httpClient = new \Digitaleo();
$httpClient->setBaseUrl('<api_base_url>')
$httpClient->setOauthClientCredentials(
    'https://oauth.messengeo.net/token',
    '<client_id>',
    '<client_secret>');
```

**Read one or several resource**

```php
$params = [
    'properties' => '<properties>',
    'limit' => '<limit>',
    'offset' => '<limit>',
    'sort' => '<sort>',
    'total' => '<total>',
    '<property_name1' => '<property_value1>',
    '<property_name2' => '<property_value2>'
];
$httpClient->callGet('my_resource_name', $params);
```

**Create one resource**

```php
$body = [
    '<property_name1>' => '<property_value1>',
    '<property_name2>' => '<property_value2>'
];
$httpClient->callPost('my_resource_name', $body);
```

**Update one resource**

```php
$params = [
    'id' => '<id_value>',
];
$dataToUpdate = [
    '<property_name1>' => '<property_value1>',
    '<property_name2>' => '<property_value2>'
];
$body = [
    'metaData' => json_encode($dataToUpdate),
];
$httpClient->callPut('my_resource_name', $body, $params);
```

**Delete one resource**

```php
$params = [
    'id' => '<id_value>'
];
$httpClient->callDelete('my_resource_name', $params);
```
