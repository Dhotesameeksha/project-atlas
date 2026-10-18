<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function save($sid, $client, $question, $res)
    {
        $payload = array(
            'citations'      => isset($res['citations']) ? $res['citations'] : array(),
            'conflicts'      => isset($res['conflicts']) ? $res['conflicts'] : array(),
            'derived_values' => isset($res['derived_values']) ? $res['derived_values'] : array(),
            'sql_query'      => isset($res['sql_query']) ? $res['sql_query'] : null,
        );
        $this->db->insert('chat_messages', array(   // query builder escapes values (no SQL injection)
            'session_id' => $sid,
            'client_id'  => $client,
            'question'   => $question,
            'answer'     => $res['answer'],
            'confidence' => isset($res['confidence']) ? $res['confidence'] : null,
            'escalated'  => !empty($res['escalated']) ? 1 : 0,
            'route'      => isset($res['route']) ? $res['route'] : null,
            'payload'    => json_encode($payload),
        ));
        return (int) $this->db->insert_id();
    }

    public function recent($sid, $limit = 20)
    {
        $rows = $this->db->select('m.id, m.client_id, m.question, m.answer, m.confidence, m.escalated, m.payload, f.rating')
            ->from('chat_messages m')->join('chat_feedback f', 'f.message_id = m.id', 'left')
            ->where('m.session_id', $sid)->order_by('m.id', 'DESC')->limit((int) $limit)->get()->result_array();
        return array_reverse($rows);
    }

    public function owns($message_id, $sid)
    {
        return $this->db->where('id', (int) $message_id)->where('session_id', $sid)->count_all_results('chat_messages') > 0;
    }

    public function save_feedback($message_id, $rating)
    {
        $exists = $this->db->where('message_id', (int) $message_id)->count_all_results('chat_feedback') > 0;
        if ($exists) {
            $this->db->where('message_id', (int) $message_id)->update('chat_feedback', array('rating' => (int) $rating));
        } else {
            $this->db->insert('chat_feedback', array('message_id' => (int) $message_id, 'rating' => (int) $rating));
        }
    }
}
