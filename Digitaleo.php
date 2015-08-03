<?php

/**
 * Digitaleo.php
 *
 * LICENCE
 *
 * L'ensemble de ce code relève de la législation française et internationale
 * sur le droit d'auteur et la propriété intellectuelle. Tous les droits de
 * reproduction sont réservés, y compris pour les documents téléchargeables et
 * les représentations iconographiques et photographiques. La reproduction de
 * tout ou partie de ce code sur quelque support que ce soit est formellement
 * interdite sauf autorisation écrite émanant de la société DIGITALEO.
 *
 * PHP version 5.4
 *
 * @author   Digitaleo 2015
 * @license  http://www.digitaleo.net/licence.txt Digitaleo Licence
 * @link     http://www.digitaleo.net
 */

/**
 * Wrapper pour les APIs REST de Digitaleo
 *
 * @author   Digitaleo 2015
 * @license  http://www.digitaleo.net/licence.txt Digitaleo Licence
 * @link     http://www.digitaleo.net
 */
class Digitaleo
{
    protected static $_formatAllowed = array('json', 'xml', 'csv', 'js', 'bin');

    /**
     * Base URL to access the API
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * Token d'autorisation
     *
     * @var string
     */
    protected $_credential;

    /**
     * Timeout culr option
     *
     * @var string
     */
    protected $_timeout = null;

    /**
     * Format of the response
     *
     * @var string
     */
    protected $_format;

    /**
     * Flag for debugging
     *   - 0 : no debug
     *   - 1 : only errors
     *   - 2 : all times
     *
     * @var integer
     */
    public $debug = 0;

    /**
     * Code HTTP
     *
     * @var integer
     */
    protected $_responseCode;

    /**
     * Response API
     *
     * @var string
     */
    protected $_response = '';

    /**
     * REQUEST TYPE (GET, POST, PUT, DELETE)
     *
     * @var string
     */
    protected $_request;

    /**
     * URI called
     *
     * @var string
     */
    protected $_callUri;

    /**
     * URI called in GET mode
     *
     * @var string
     */
    protected $_callUriGET;


    /**
     * Version wrapper
     *
     * @var string
     */
    protected $_version = '0.1';

    /**
     * Informations de connexion de cURL
     *
     * @var array
     */
    public $curlInfos;

    /**
     * Sortie directe dans l'outputStream de php
     *
     * @var boolean
     */
    protected $_immediateOutput;

    /**
     * Headers HTTP utilisé pour la requete
     *
     * @var array
     */
    protected $_additionnalHeaders;

    /**
     * Constructor
     *
     * @param string $baseUrl Base URL to access the API
     * @param string $credential Authenticate API key
     * @param string $format [Optional] Format of the response
     *
     * @throws \Exception
     */
    public function __construct($baseUrl = null, $credential = null, $format = 'json', $immediateOutput = false, $additionalHeaders = array())
    {
        // Check extension cURL
        if (!extension_loaded('curl')) {
            throw new \Exception('Extension "curl" is not loaded.');
        }

        if (!empty($baseUrl)) {
            $this->setBaseUrl($baseUrl);
        }

        $this->_credential = $credential;
        $this->setFormat($format);
        $this->setImmediateOutput($immediateOutput);
        $this->setAdditionnalHeaders($additionalHeaders);
    }

    /**
     * Define the authorisation key
     *
     * @param string $credential Authorisation token
     *
     * @return Eo_Rest_WrapperOauth
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }

    /**
     * Define the curl timeout
     *
     * @param string $timeout Curl Timeout
     *
     * @return Eo_Rest_WrapperOauth
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
        return $this;
    }

    /**
     * Retourne la dernière requête effectuée
     *
     * @return string
     */
    public function getLastRequest()
    {
        return $this->_callUri;
    }

    /**
     * Retourne la dernière requête effectuée
     *
     * @return string
     */
    public function getLastRequestGET()
    {
        return $this->_callUriGET;
    }

    /**
     * Define format of the response
     *
     * @param type $format Format response
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setFormat($format)
    {
        $format = strtolower($format);
        if (!in_array($format, self::$_formatAllowed)) {
            $formats = implode(', ', self::$_formatAllowed);
            throw new \Exception('Only ' . $formats . ' are supported.');
        }
        $this->_format = $format;
        return $this;
    }

    /**
     * Define immediate ouput status
     *
     * @param boolean $active Active direct ouput or not
     */
    public function setImmediateOutput($active)
    {
        $this->_immediateOutput = $active;
    }

    /**
     * Set Additionnal Headers
     *
     * @param array $additionnalHeaders
     */
    public function setAdditionnalHeaders($additionnalHeaders)
    {
        $this->_additionnalHeaders = $additionnalHeaders;
    }

    /**
     * Overload magic methods
     *
     * @param string $name
     * @param array $arguments
     *
     * @return stdClass Objectlist
     */
    public function __call($name, $arguments)
    {
        // Check base URL to access the API is set
        if (empty($this->_baseUrl)) {
            throw new \InvalidArgumentException('Please set the base url to access the API.');
        }

        $this->_method = $name;
        $result = $this->_sendRequest($name, array_shift($arguments), array_shift($arguments), array_shift($arguments));

        # Return result
        $return = ($result === true) ? $this->_response : false;

        if ($this->debug == 2 || ($this->debug == 1 && $result == false)) {
            $this->_debug();
        }

        return $this->_response;
    }


    /**
     * Récupération d'un token
     * 
     * @param array  $datas Données POST à passer au serveur d'autorisation pour obtenir un token
     * 
     * @return array token d'autorisation
     */
    private function _getToken($datas = array())
    {
        $curlOauthPasswordGrantType = curl_init('https://oauth.messengeo.net');
        curl_setopt($curlOauthPasswordGrantType, CURLOPT_POST, true);
        curl_setopt($curlOauthPasswordGrantType, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlOauthPasswordGrantType, CURLOPT_POSTFIELDS, $datas);

        $auth = curl_exec($curlOauthPasswordGrantType);
        curl_close($curlOauthPasswordGrantType);
        if ($auth != false){
            $res = json_decode($auth);
            $token = $res->access_token;
        } else {
            $token = null;
        }

        return $token;
    }

    /**
     * Récupération d'un token pour le grant type "client_credentials"
     * 
     * @param string $clientId     Client ID
     * @param string $clientSecret Client Secret
     * 
     * @return string token d'autorisation
     */
    public function getTokenWithClientCredentialsGrant($clientId, $clientSecret)
    {
        $datas = array(
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        );
        return $this->_getToken($datas);
    }

    /**
     * Récupération d'un wrapper pour le grant type "client_credentials"
     * 
     * @param string $clientId     Client ID
     * @param string $clientSecret Client Secret
     * 
     * @return wrapper
     */
    public function setClientCredentialsGrant($clientId, $clientSecret)
    {
        $this->setCredential($this->getTokenWithClientCredentialsGrant($clientId, $clientSecret));
    }

    /**
     * Define base URL to access the API
     *
     * @param string $baseUrl Base URL
     *
     * @return void
     */
    public function setBaseUrl($baseUrl)
    {
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Send request HTTP to the API
     *
     * @param string $method
     * @param array $params
     * @param array $fileParams
     *
     * @return boolean
     */
    protected function _sendRequest($method, $params = array(), $fileParams = null, $additionnalsHeaders = array())
    {
        if (!is_array($params)) {
            $params = array();
        }

        $method = $this->_cutMethod($method);

        $handle = $this->_initCurl();
        if (!array_key_exists('action', $params)) {
            $params['action'] = $method['action'];
        }
        $this->_requestPost = $params;
        curl_setopt($handle, CURLOPT_POST, true);

        $postAsString = true;
        if (!is_null($fileParams) && is_array($fileParams)) {
            $postAsString = false;
            foreach ($fileParams as $key => $value) {
                $params[$key] = '@' . $value;
            }
        }

        $postData = ($postAsString == true) ? http_build_query($params, '', '&') : $params;
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
        $this->_request = 'POST';

        if (!empty($this->_timeout)) {
            curl_setopt($handle, CURLOPT_TIMEOUT, $this->_timeout);
        }

        $uri = $this->_requestUrlBuilder($method, $params);
        // Define URI
        curl_setopt($handle, CURLOPT_URL, $uri);

        if ($this->_immediateOutput) {
            // this will handle very large files too, whereas echo'ing one big string will not
            curl_setopt($handle, CURLOPT_BUFFERSIZE, 8192); // 8192 8k
            curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($handle, $str) {
                // called every CURLOPT_BUFFERSIZE
                echo $str;
                return strlen($str);
            });
            curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, $str) {
                if (preg_match('/^(?:HTTP\/1.+|(?:Content-Encoding|Content-Language|Content-Length|Content-Disposition|Content-Type):)/i', $str)) {
                    header($str);
                }
                return strlen($str);
            });
        }

        // Merge des headers passés par appel et ceux passé a l'instanciation du Wrapper
        $mergedHeaders = array_merge(
                array( 'Authorization: Bearer ' . $this->_credential ),
                (array)$additionnalsHeaders,
                (array)$this->_additionnalHeaders);

        if (!empty($mergedHeaders)) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $mergedHeaders);
        }

        $buffer = curl_exec($handle);

        $this->curlInfos = $curlInfos = curl_getinfo($handle);

        # Close curl process
        curl_close($handle);

        # Response code
        $this->_responseCode = $curlInfos['http_code'];

        # RESPONSE
        $this->_response =
            ($this->_format == 'json' && $curlInfos['content_type'] == 'application/json' && !$this->_immediateOutput) ?
                json_decode($buffer) :
                $buffer;

        return ($this->_responseCode >= 200 && $this->_responseCode <= 302) ? true : false;
    }

    public function getResponseCode()
    {
        return $this->_responseCode;
    }

    /**
     * Create URI for call
     *
     * @param array $method Resource & Action
     * @param array $params Parameters of request
     *
     * @return string
     */
    protected function _requestUrlBuilder($method, $params = array())
    {
        $uri = $this->_baseUrl . $method['resource'] . '.' . $this->_format;

        $paramsQuery = '';
        if (!empty($params) && is_array($params)) {
            if (isset($params['key'])) {
                $params['key'] = substr($params['key'], 0, 6) . 'XXXXXXXXX';
            }
            $paramsQuery = http_build_query($params, '', '&');
        }
        $this->_callUriGET = $uri . '?' . $paramsQuery;
        $this->_callUri = $uri . '?action=' . $params['action'];
        return $this->_callUri;
    }

    /**
     * Cut method to retrieve Resource & Action
     *
     * @param string $method Method magic
     *
     * @return array
     * @throws \Exception
     */
    protected function _cutMethod($method)
    {
        // Decomposition method name called
        $matches = array();
        if (!preg_match('/^([a-z0-9]*)([A-Z]+[a-z]*)$/', $method, $matches)) {
            throw new \Exception('method name is incorrect (Eg: mailingsRead)');
        }
        return array(
            'resource' => strtolower($matches[1]),
            'action' => strtolower($matches[2]),
        );
    }

    /**
     * Init curl
     *
     * @return resource
     */
    protected function _initCurl()
    {
        $handle = curl_init();
        $configCurl = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => !$this->_immediateOutput,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        );
        $componentsUrl = parse_url($this->_baseUrl);
        // Check secure URL
        if ($componentsUrl['scheme'] == 'https' || (array_key_exists('port', $componentsUrl) && $componentsUrl['port'] != 80)) {
            $configCurl[CURLOPT_SSL_VERIFYPEER] = false;
            $configCurl[CURLOPT_SSL_VERIFYHOST] = 2;
            $configCurl[CURLOPT_SSLVERSION] = 1;
        }
        curl_setopt_array($handle, $configCurl);

        return $handle;
    }

    /**
     * Method of debug
     *
     * @return void
     */
    protected function _debug()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->_debugCli();
        } else {
            $this->_debugHtml();
        }
    }

    /**
     * Method of debug for CLI
     *
     * @return void
     */
    protected function _debugCli()
    {
        echo PHP_EOL;
        echo '---------------------------------------------------' . PHP_EOL;
        if (isset($this->_responseCode)) {
            echo 'Status code: ' . $this->_responseCode . PHP_EOL;
            if ($this->_responseCode == 200) {
                if (isset($this->_response)) {
                    echo 'Response: ' . var_export($this->_response, 1) . PHP_EOL;
                }
            } elseif ($this->_response_code == 304) {
                echo 'Response Not Modified' . PHP_EOL;
            } else {
                if (isset($this->_response)) {
                    if (is_array($this->_response) || is_object($this->_response)) {
                        echo 'Response: ' . var_export($this->_response, true) . PHP_EOL;
                    } else {
                        echo 'Response: ' . $this->_response . PHP_EOL;
                    }
                }
            }
        }
        echo PHP_EOL;

        $callUri = parse_url($this->_callUri);

        echo 'API Config:' . PHP_EOL;
        echo "\tProtocol: " . $callUri['scheme'] . PHP_EOL;
        echo "\tHost: " . $callUri['host'] . PHP_EOL;
        echo "\tVersion wrapper: " . $this->_version . PHP_EOL . PHP_EOL;

        echo 'Call Info:' . PHP_EOL;
        echo "\tMethod: " . $this->_method . PHP_EOL;
        echo "\tRequest type: " . $this->_request . PHP_EOL;
        echo "\tGet Arguments: " . PHP_EOL;

        $args = explode("&", $callUri['query']);
        foreach ($args as $arg) {
            $arg = explode("=", $arg);
            echo "\t\t" . $arg[0] . ' = ' . $arg[1] . PHP_EOL;
        }

        if ($this->_requestPost) {
            echo "\n\tPost Arguments:";

            foreach ($this->_requestPost as $k => $v) {
                echo "\t\t" . $k . ' = ' . $v . PHP_EOL;
            }
        }
        echo PHP_EOL . 'Call url: ' . $this->_callUri . PHP_EOL;
        echo '---------------------------------------------------' . PHP_EOL . PHP_EOL;
    }

    /**
     * Method of debug for HTML
     *
     * @return void
     */
    protected function _debugHtml()
    {
        echo '<style type="text/css">';
        echo '

        #debugger {width: 100%; font-family: arial;}
        #debugger table {padding: 0; margin: 0 0 20px; width: 100%; font-size: 11px; text-align: left;border-collapse: collapse;}
        #debugger th, #debugger td {padding: 2px 4px;}
        #debugger tr.h {background: #999; color: #fff;}
        #debugger tr.Success {background:#90c306; color: #fff;}
        #debugger tr.Error {background:#c30029 ; color: #fff;}
        #debugger tr.Not-modified {background:orange ; color: #fff;}
        #debugger th {width: 20%; vertical-align:top; padding-bottom: 8px;}

        ';
        echo '</style>';

        echo '<div id="debugger">';

        if (isset($this->_responseCode)) {
            if ($this->_responseCode == 200) {
                echo '<table>';
                echo '<tr class="Success"><th>Success</th><td></td></tr>';
                echo '<tr><th>Status code</th><td>' . $this->_responseCode . '</td></tr>';
                if (isset($this->_response)) {
                    echo '<tr><th>Response</th><td><pre>' . utf8_decode(print_r($this->_response, 1)) . '</pre></td></tr>';
                }
                echo '</table>';
            } elseif ($this->_responseCode == 304) {
                echo '<table>';
                echo '<tr class="Not-modified"><th>Error</th><td></td></tr>';
                echo '<tr><th>Error no</th><td>' . $this->_responseCode . '</td></tr>';
                echo '<tr><th>Message</th><td>Not Modified</td></tr>';
                echo '</table>';
            } else {
                echo '<table>';
                echo '<tr class="Error"><th>Error</th><td></td></tr>';
                echo '<tr><th>Error no</th><td>' . $this->_responseCode . '</td></tr>';
                if (isset($this->_response)) {
                    if (is_array($this->_response) || is_object($this->_response)) {
                        echo '<tr><th>Status</th><td><pre>' . print_r($this->_response, true) . '</pre></td></tr>';
                    } else {
                        echo '<tr><th>Status</th><td><pre>' . $this->_response . '</pre></td></tr>';
                    }
                }
                echo '</table>';
            }
        }

        $callUri = parse_url($this->_callUri);

        echo '<table>';
        echo '<tr class="h"><th>API config</th><td></td></tr>';
        echo '<tr><th>Protocole</th><td>' . $callUri['scheme'] . '</td></tr>';
        echo '<tr><th>Host</th><td>' . $callUri['host'] . '</td></tr>';
        echo '<tr><th>Version</th><td>' . $this->_version . '</td></tr>';
        echo '</table>';

        echo '<table>';
        echo '<tr class="h"><th>Call infos</th><td></td></tr>';
        echo '<tr><th>Method</th><td>' . $this->_method . '</td></tr>';
        echo '<tr><th>Request type</th><td>' . $this->_request . '</td></tr>';
        echo '<tr><th>Get Arguments</th><td>';

        $args = explode("&", $callUri['query']);
        foreach ($args as $arg) {
            $arg = explode("=", $arg);
            echo '' . $arg[0] . ' = <span style="color:#ff6e56;">' . $arg[1] . '</span><br/>';
        }

        echo '</td></tr>';

        if ($this->_requestPost) {
            echo '<tr><th>Post Arguments</th><td>';

            foreach ($this->_requestPost as $k => $v) {
                echo $k . ' = <span style="color:#ff6e56;">' . $v . '</span><br/>';
            }
            echo '</td></tr>';
        }
        echo '<tr><th>Call url</th><td>' . $this->_callUri . '</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}
