
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Report Copilot</title>
<style>
:root{--bg:#f6f7f9;--card:#fff;--text:#1c2230;--muted:#667085;--accent:#2457d6;--line:#e3e6ec;--warn:#b54708}
@media(prefers-color-scheme:dark){:root{--bg:#10131a;--card:#181c26;--text:#e8ebf2;--muted:#98a2b3;--accent:#6f93ff;--line:#2a3040;--warn:#f79009}}
*{box-sizing:border-box}
body{margin:0;font:15px/1.5 system-ui,sans-serif;background:var(--bg);color:var(--text)}
header{padding:14px 20px;border-bottom:1px solid var(--line);background:var(--card);display:flex;gap:12px;align-items:center;flex-wrap:wrap}
header h1{font-size:18px;margin:0;flex:1}
select,textarea,button{font:inherit;color:inherit}
select{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:6px 10px}
main{max-width:860px;margin:0 auto;padding:16px}
#log{display:flex;flex-direction:column;gap:12px;min-height:50vh}
.msg{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px 14px;white-space:pre-wrap;overflow-wrap:anywhere}
.msg.user{align-self:flex-end;background:var(--accent);color:#fff;border:none;max-width:85%}
.meta{margin-top:8px;font-size:13px;color:var(--muted);white-space:normal}
.badge{display:inline-block;padding:1px 8px;border-radius:99px;border:1px solid var(--line);margin-right:6px}
.warn{color:var(--warn)}
.box{margin-top:8px;padding:8px 10px;border:1px dashed var(--line);border-radius:8px;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}
.fb button{background:none;border:1px solid var(--line);border-radius:8px;cursor:pointer;padding:2px 8px;margin-right:4px}
.fb button.on{border-color:var(--accent)}
form{display:flex;gap:8px;margin-top:16px}
textarea{flex:1;min-width:0;background:var(--card);border:1px solid var(--line);border-radius:10px;padding:10px;resize:vertical;min-height:48px}
form button{background:var(--accent);color:#fff;border:none;border-radius:10px;padding:0 18px;cursor:pointer}
form button:disabled{opacity:.6;cursor:wait}
#status{margin-top:8px;color:var(--warn);font-size:13px}
@media(max-width:560px){main{padding:12px}form button{padding:0 12px}}
</style>
</head>
<body>
<header>
  <h1>Report Copilot</h1>
  <label for="client">Client</label>
  <select id="client">
    <?php foreach ($clients as $id => $name): ?>
      <option value="<?= html_escape($id) ?>" <?= $id === $client_id ? 'selected' : '' ?>><?= html_escape($name) ?></option>
    <?php endforeach; ?>
  </select>
</header>

<main>
  <div id="log" aria-live="polite"></div>
  <form id="form">
    <textarea id="q" maxlength="1000" placeholder="Ask about a market report..." required></textarea>
    <button id="send" type="submit">Send</button>
  </form>
  <div id="status" role="status" aria-live="polite"></div>
</main>

<script type="application/json" id="hist"><?= json_encode($history, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
const CSRF_NAME = <?= json_encode($csrf_name) ?>;
const CSRF_HASH = <?= json_encode($csrf_hash) ?>;
const log = document.getElementById('log');
const statusBox = document.getElementById('status');

function el(tag, cls, text) {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text !== undefined && text !== null) {
    node.textContent = String(text);
  }
  return node;
}

async function post(url, params) {
  const body = new URLSearchParams(params);
  body.append(CSRF_NAME, CSRF_HASH);

  const response = await fetch(url, {
    method: 'POST',
    body,
    credentials: 'same-origin',
    headers: {'X-Requested-With': 'XMLHttpRequest'}
  });

  const raw = await response.text();
  let data;

  try {
    data = JSON.parse(raw);
  } catch (_) {
    throw new Error(
      `Server returned invalid JSON (HTTP ${response.status}). Check server logs.`
    );
  }

  if (!response.ok) {
    throw new Error(data.error || `Request failed (HTTP ${response.status}).`);
  }

  return data;
}

function addUser(text) {
  log.appendChild(el('div', 'msg user', text));
}

function addBot(d) {
  const box = el('div', 'msg');
  box.appendChild(el(
    'div', '', d.answer || d.error || 'No answer was returned by the API.'
  ));

  const meta = el('div', 'meta');

  if (d.confidence !== null && d.confidence !== undefined &&
      Number.isFinite(Number(d.confidence))) {
    meta.appendChild(el(
      'span', 'badge', 'confidence ' + Number(d.confidence).toFixed(2)
    ));
  }

  if (d.escalated) {
    meta.appendChild(el('span', 'badge warn', 'escalated to analyst'));
  }

  if (d.cached) {
    meta.appendChild(el('span', 'badge', 'cached'));
  }

  if (meta.childNodes.length) box.appendChild(meta);

  (Array.isArray(d.conflicts) ? d.conflicts : []).forEach(c => {
    const b = el(
      'div', 'box warn',
      `Sources disagree: ${c.metric || 'metric'} (${c.region || 'region'}, ${c.period || 'period'})`
    );

    (Array.isArray(c.values) ? c.values : []).forEach(v => {
      b.appendChild(el(
        'div', '',
        `${v.report_id || 'Report'} p.${v.page ?? '?'}: ${v.value ?? ''} ${v.unit || ''}${v.scope ? ' - ' + v.scope : ''}`
      ));
    });

    box.appendChild(b);
  });

  (Array.isArray(d.derived_values) ? d.derived_values : []).forEach(v => {
    const b = el(
      'div', 'box',
      `${v.label || 'Derived value'} - ${v.report_id || 'Report'}: ${v.result ?? ''} ${v.unit || ''}`
    );

    if (v.formula) b.appendChild(el('div', '', 'Formula: ' + v.formula));

    (Array.isArray(v.assumptions) ? v.assumptions : []).forEach(a => {
      b.appendChild(el('div', '', '- ' + a));
    });

    box.appendChild(b);
  });

  if (Array.isArray(d.citations) && d.citations.length) {
    const c = el('div', 'box');
    c.appendChild(el('div', '', 'Sources'));

    d.citations.forEach(x => {
      c.appendChild(el(
        'div', '',
        `${x.report_id || 'Report'} p.${x.page ?? '?'} - ${x.title || 'Untitled source'}`
      ));
    });

    box.appendChild(c);
  }

  if (d.message_id) {
    const fb = el('div', 'meta fb');

    [['👍', 1], ['👎', -1]].forEach(([label, value]) => {
      const button = el('button', '', label);
      button.type = 'button';

      if (Number(d.rating) === value) button.className = 'on';

      button.addEventListener('click', async () => {
        button.disabled = true;

        try {
          const result = await post('feedback', {
            message_id: d.message_id,
            rating: value
          });

          if (result.ok) {
            fb.querySelectorAll('button').forEach(item => {
              item.className = item === button ? 'on' : '';
            });
            statusBox.textContent = '';
          }
        } catch (error) {
          statusBox.textContent = error.message || 'Could not submit feedback.';
        } finally {
          button.disabled = false;
        }
      });

      fb.appendChild(button);
    });

    box.appendChild(fb);
  }

  log.appendChild(box);
  box.scrollIntoView({block: 'end', behavior: 'smooth'});
}

// Load previous conversations.
try {
  const history = JSON.parse(
    document.getElementById('hist').textContent || '[]'
  );

  history.forEach(h => {
    let payload = {};

    try {
      payload = typeof h.payload === 'string'
        ? JSON.parse(h.payload || '{}')
        : (h.payload || {});
    } catch (_) {}

    addUser(h.question || '');

    addBot({
      answer: h.answer,
      confidence: h.confidence === null ? null : Number(h.confidence),
      escalated: h.escalated == 1,
      citations: payload.citations || [],
      conflicts: payload.conflicts || [],
      derived_values: payload.derived_values || [],
      message_id: Number(h.id),
      rating: h.rating === null ? null : Number(h.rating)
    });
  });
} catch (_) {
  statusBox.textContent =
    'Could not load chat history. You can still send a new question.';
}

// Send a question to the Report Copilot.
document.getElementById('form').addEventListener('submit', async event => {
  event.preventDefault();

  const questionBox = document.getElementById('q');
  const button = document.getElementById('send');
  const question = questionBox.value.trim();

  if (!question) return;

  addUser(question);
  questionBox.value = '';
  button.disabled = true;
  button.textContent = 'Thinking...';
  statusBox.textContent = '';

  try {
    const data = await post('send', {
      client_id: document.getElementById('client').value,
      question: question
    });

    addBot(data);

    if (data.ok === false) {
      statusBox.textContent =
        'The API returned a fallback response. Check API logs and model configuration.';
    }
  } catch (error) {
    statusBox.textContent =
      error.message || 'Something went wrong. Please try again.';

    addBot({answer: statusBox.textContent});
  } finally {
    button.disabled = false;
    button.textContent = 'Send';
    questionBox.focus();
  }
});
</script>
</body>
</html>
