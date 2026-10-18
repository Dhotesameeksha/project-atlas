<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Calls the Python Report Copilot API. Never throws: returns a friendly fallback on any failure. */
class Atlas_client
{
    const FALLBACK = 'The Report Copilot is temporarily unavailable. Please try again in a minute, or contact your analyst directly.';
    private $base;
    private $key;

    public function __construct()
    {
        $this->base = rtrim(getenv('ATLAS_API_URL') ?: 'http://api:8000', '/');
        $this->key  = getenv('API_KEY') ?: '';
    }

    public function chat($client_id, $question)
    {
        $ch = curl_init($this->base . '/chat');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'X-API-Key: ' . $this->key),
            CURLOPT_POSTFIELDS     => json_encode(array('client_id' => $client_id, 'question' => $question)),
        ));
        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = ($errno === 0 && $status === 200) ? json_decode($body, true) : null;
        if (!is_array($data) || !isset($data['answer'])) {
            log_message('error', 'Atlas API failure: errno=' . $errno . ' status=' . $status);
            return array('ok' => false, 'answer' => self::FALLBACK, 'citations' => array(), 'confidence' => null,
                         'conflicts' => array(), 'derived_values' => array(), 'escalated' => false);
        }
        $data['ok'] = true;
        return $data;
    }
}
