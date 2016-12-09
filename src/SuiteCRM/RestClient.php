<?php

namespace SuiteCRM;

class RestClient
{

    /**
     * SuiteCRM Session ID
     *
     * @var string
     */
    protected $sid = null;
    /**
     * Rest object
     *
     * @var string
     */
    private $rest_url = "";
    /**
     * SuiteCRM User
     *
     * @var string
     */
    private $rest_user = "";
    /**
     * SuiteCRM Pass
     *
     * @var string
     */
    private $rest_pass = "";

    /**
     * @param string $url Url to SuiteCRM's rest.php
     * @param $user
     * @param $pass
     * @param $host
     *
     * @return boolean
     */
    public function __construct($user, $pass, $host)
    {
        $this->rest_user = $user;
        $this->rest_pass = $pass;
        $this->base_url = 'http://' . preg_replace('~^http://~', '', $host);
        $this->rest_url = $host . "/service/v4_1/rest.php";
    }

    /**
     * Login with user credentials
     *
     * @param string $user
     * @param string $password_hash
     * @param boolean $admin_check
     *
     * @return boolean
     */
    public function login()
    {
        $login_params = array(
            'user_name' => $this->rest_user,
            'password'  => $this->rest_pass,
        );

        $result = $this->rest_request(
            'login',
            array(
                'user_auth'        => $login_params,
                "application_name" => "",
                'name_value_list'  => array(array('name' => 'notifyonsave', 'value' => 'true'))
            )
        );

        if (isset($result['id'])) {
            $this->sid = $result['id'];

            return $result['id'];
        } else {
            if (isset($result['name'])) {
                return false;
            }
        }

        return false;
    }

    /**
     * convert to rest request and return decoded array
     *
     * @return array
     */
    private function rest_request($call_name, $call_arguments)
    {

        ob_start();
        $ch = curl_init();

        $post_data = 'method=' . $call_name . '&input_type=JSON&response_type=JSON';
        $jsonEncodedData = json_encode($call_arguments);
        $post_data = $post_data . "&rest_data=" . $jsonEncodedData;

        curl_setopt($ch, CURLOPT_URL, $this->rest_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $output = curl_exec($ch);

        $response_data = json_decode($output, true);

        curl_close($ch);

        ob_end_flush();

        return $response_data;
    }

    /**
     * Logout
     */
    public function logout()
    {
        $result = $this->rest_request(
            'logout',
            array(
                'session' => $this->sid,
            )
        );

        $this->sid = null;
    }

    /**
     * Retrieves a list of entries
     *
     * @param string $module
     * @param query $query
     * @param string $order_by
     * @param integer $offset
     * @param array $select_fields
     * @param integer $max_results
     * @param boolean $deleted
     *
     * @return array
     */
    public function getEntryList(
        $module,
        $query = '',
        $order_by = '',
        $offset = 0,
        $select_fields = array(),
        $related_fields = array(),
        $max_results = '0',
        $deleted = false
    ) {
        if (!$this->sid) {
            return false;
        }

        $result = $this->rest_request(
            'get_entry_list',
            array(
                'session'                   => $this->sid,
                'module_name'               => $module,
                'query'                     => $query,
                'order_by'                  => $order_by,
                'offset'                    => $offset,
                'select_fields'             => $select_fields,
                'link_name_to_fields_array' => $related_fields,
                'max_results'               => $max_results,
                'deleted'                   => $deleted,
            )
        );

        if ($result['result_count'] > 0) {
            return $result;
        } else {
            return false;
        }
    }

    public function getEntry($module, $id, $select_fields = array(), $related_fields = array())
    {
        if (!$this->sid) {
            return false;
        }

        $result = $this->rest_request(
            'get_entry',
            array(
                'session'                   => $this->sid,
                'module_name'               => $module,
                'id'                        => $id,
                'select_fields'             => $select_fields,
                'link_name_to_fields_array' => $related_fields,
            )
        );

        if (!isset($result['result_count']) || $result['result_count'] > 0) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Adds or changes an entry
     *
     * @param string $module
     * @param array $data
     *
     * @return array
     */
    public function setEntry($module, $data)
    {
        if (!$this->sid) {
            return false;
        }

        $result = $this->rest_request(
            'set_entry',
            array(
                'session'         => $this->sid,
                'module_name'     => $module,
                'name_value_list' => convertArrayToNVL(str_replace("&", "%26", $data)),
            )
        );

        return $result;
    }

    /**
     * Creates a new relationship-entry
     *
     * @param string $module1
     * @param string $module1_id
     * @param string $module2
     * @param string $module2_id
     *
     * @return array
     */
    public function setRelationship($module1, $module1_id, $module2, $module2_id)
    {
        if (!$this->sid) {
            return false;
        }

        $data = array(
            'session'         => $this->sid,
            'module_name'     => $module1,
            'module_id'       => $module1_id,
            'link_field_name' => $module2,
            '$related_ids'    => array($module2_id),
        );

        $result = $this->rest_request('set_relationship', $data);

        return $result;
    }

    /**
     * Retrieves relationship data
     *
     * @param string $module_name
     * @param string $module_id
     * @param string $related_module
     *
     * @return array
     */
    public function getRelationships(
        $module_name,
        $module_id,
        $related_module,
        $related_module_query = '',
        $related_fields = array(),
        $related_module_link_name_to_fields_array = array(),
        $deleted = false,
        $order_by = '',
        $offset = 0,
        $limit = false
    ) {
        $result = $this->rest_request(
            'get_relationships',
            array(
                'session'                                  => $this->sid,
                'module_name'                              => $module_name,
                'module_id'                                => $module_id,
                'link_field_name'                          => $related_module,
                'related_module_query'                     => $related_module_query,
                'related_fields'                           => $related_fields,
                'related_module_link_name_to_fields_array' => $related_module_link_name_to_fields_array,
                'deleted'                                  => $deleted,
                'order_by'                                 => $order_by,
                'offset'                                   => $offset,
                'limit'                                    => $limit,
            )
        );

        if (!isset($result['error']['number']) || $result['error']['number'] == 0) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Retrieves a module field
     *
     * @param string $module
     * @param string $field
     *
     * @return field
     */
    public function getModuleFields($module, $field)
    {
        if (!$this->sid) {
            return false;
        }

        $result = $this->rest_request(
            'get_module_fields',
            array(
                'session'     => $this->sid,
                'module_name' => $module,
            )
        );

        if ($result > 0) {
            //return $result;
            return $result['module_fields'][$field];
        } else {
            return false;
        }
    }

    public function getAllModuleFields($module)
    {
        if (!$this->sid) {
            return false;
        }

        $result = $this->rest_request(
            'get_module_fields',
            array(
                'session'     => $this->sid,
                'module_name' => $module,
            )
        );

        if ($result > 0) {
            //return $result;
            return $result['module_fields'];
        } else {
            return false;
        }
    }

    public function get_note_attachment($note_id)
    {
        if (!$this->sid) {
            return false;
        }

        $call_arguments = array(
            'session' => $this->sid,
            'id'      => $note_id
        );

        $result = $this->rest_request(
            'get_note_attachment',
            $call_arguments
        );

        return $result;

        return false;
    }
}

?>
