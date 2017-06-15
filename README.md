# suitecrm-rest-client
SuiteCRM Rest API Client

Install using composer:
<pre>composer require daniel-samson/suitecrm-rest-client</pre>

Example:
<pre>
$SuiteAPI = new \SuiteCRM\RestClient('username', 'password', 'http://crm.example.com');
if($SuiteCRM->login() === false) {
  throw Exception("Unable to login");
}
</pre>
