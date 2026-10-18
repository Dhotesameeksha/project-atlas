
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Calls the Python Report Copilot API.
 * Logs connection/API errors and returns a safe fallback.
 */
class Atlas_client
{
    const FALLBACK = 'The Report Copilot is temporarily unavailable. Please try again in a minute, or contact your analyst directly.';

    private $base;
    private $key;

    public function __construct()
    {
        $this->base = rtrim(
            getenv('ATLAS_API_URL') ?: 'http://api:8000',
            '/'
        );

        $this->key = getenv('API_KEY') ?: '';
    }

    public function chat($client_id, $question)
    {
        $payload = json_encode(array(
            'client_id' => $client_id,
            'question'  => $question
        ));

        if ($payload === false) {
            log_message('error', 'Atlas API: Failed to encode request JSON.');

            return $this->fallback();
        }

        $ch = curl_init($this->base . '/chat');

        if ($ch === false) {
            log_message('error', 'Atlas API: Failed to initialize cURL.');

            return $this->fallback();
        }

        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . $this->key
            ),
            CURLOPT_POSTFIELDS     => $payload
        ));

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno !== 0 || $status !== 200) {
            log_message(
                'error',
                'Atlas API failure: errno=' . $errno .
                ' status=' . $status .
                ' error=' . $curl_error .
                ' response=' . substr((string) $body, 0, 1000)
            );

            return $this->fallback();
        }

        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['answer'])) {
            log_message(
                'error',
                'Atlas API returned invalid JSON or missing answer. ' .
                'JSON error: ' . json_last_error_msg()
            );

            return $this->fallback();
        }

        $data['ok'] = true;

        // Keep optional response fields consistent for the frontend.
        $defaults = array(
            'citations'      => array(),
            'confidence'     => null,
            'conflicts'      => array(),
            'derived_values' => array(),
            'escalated'      => false
        );

        foreach ($defaults as $field => $default) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = $default;
            }
        }

        return $data;
    }

    private function fallback()
    {
        return array(
            'ok'             => false,
            'answer'         => self::FALLBACK,
            'citations'      => array(),
            'confidence'     => null,
            'conflicts'      => array(),
            'derived_values' => array(),
            'escalated'      => false
        );
    }
}
