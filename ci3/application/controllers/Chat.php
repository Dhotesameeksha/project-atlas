<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller
{
    // Demo client switcher. In the real portal the client comes from the logged-in user.
    private $clients = array('C-01' => 'Northwind Utilities (C-01)', 'C-02' => 'Blue Delta Infra (C-02)');

    public function __construct()
    {
        parent::__construct();
        $this->load->library('atlas_client');
        $this->load->model('chat_model');
    }

    public function index()
    {
        $client = $this->session->userdata('client_id');
        if (!isset($this->clients[$client])) {
            $client = 'C-01';
        }
        $this->load->view('chat', array(
            'client_id' => $client,
            'clients'   => $this->clients,
            'history'   => $this->chat_model->recent($this->session->session_id, 20),
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ));
    }

    public function send()
    {
        if ($this->input->method() !== 'post') {
            return $this->_json(array('error' => 'Method not allowed'), 405);
        }
        $client   = (string) $this->input->post('client_id', true);
        $question = trim((string) $this->input->post('question', true));
        if (!isset($this->clients[$client]) || mb_strlen($question) < 3 || mb_strlen($question) > 1000) {
            return $this->_json(array('error' => 'Please enter a question of 3 to 1000 characters.'), 400);
        }
        $this->session->set_userdata('client_id', $client);
        $result = $this->atlas_client->chat($client, $question);
        $id = $this->chat_model->save($this->session->session_id, $client, $question, $result);
        $result['message_id'] = $id;
        return $this->_json($result, 200);
    }

    public function feedback()
    {
        $id     = (int) $this->input->post('message_id');
        $rating = (int) $this->input->post('rating');
        if ($this->input->method() !== 'post' || !in_array($rating, array(1, -1), true)
            || !$this->chat_model->owns($id, $this->session->session_id)) {
            return $this->_json(array('error' => 'Invalid feedback'), 400);
        }
        $this->chat_model->save_feedback($id, $rating);
        return $this->_json(array('ok' => true), 200);
    }

    private function _json($data, $status)
    {
        $this->output->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output(json_encode($data));
    }
}
