
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ATLAS — AI Market Research Assistant</title>

<style>
:root {
  color-scheme: light;
  --bg:#f5f7fb;
  --panel:#ffffff;
  --text:#182230;
  --muted:#667085;
  --line:#e5e9f0;
  --accent:#4338ca;
  --accent-light:#eef0ff;
  --danger:#b42318;
  --success:#067647;
}
html[data-theme="dark"] {
  color-scheme: dark;
  --bg:#101522;
  --panel:#1a2232;
  --text:#edf1f7;
  --muted:#a5b0c3;
  --line:#303b50;
  --accent:#8b9cff;
  --accent-light:#293452;
  --danger:#ff9696;
  --success:#75ddb0;
}
* { box-sizing:border-box }
body {
  margin:0;
  font:14px/1.55 Inter,system-ui,sans-serif;
  color:var(--text);
  background:var(--bg);
}
button,input,textarea,select { font:inherit }
button { cursor:pointer }
button:disabled { opacity:.55;cursor:wait }
button:focus-visible,input:focus-visible,textarea:focus-visible,select:focus-visible {
  outline:2px solid var(--accent);
  outline-offset:2px;
}
.app { display:flex;min-height:100vh }
.sidebar {
  width:280px;flex-shrink:0;background:var(--panel);
  border-right:1px solid var(--line);padding:18px 14px;
  display:flex;flex-direction:column;gap:16px;
}
.brand { display:flex;align-items:center;gap:10px;padding:4px 5px }
.brand-icon {
  width:42px;height:42px;border-radius:13px;
  background:var(--accent);color:white;
  display:grid;place-items:center;font-size:22px;font-weight:800;
}
.brand h2 { font-size:19px;letter-spacing:1px;margin:0 }
.brand small { color:var(--muted);font-size:11px }
.primary,.secondary {
  border-radius:10px;padding:10px 12px;font-weight:600;
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
}
.primary { background:var(--accent);color:white;border:1px solid var(--accent) }
.secondary { background:var(--panel);color:var(--text);border:1px solid var(--line) }
.primary:hover { filter:brightness(.95) }
.search {
  width:100%;padding:10px 12px;border:1px solid var(--line);
  background:var(--bg);border-radius:10px;outline:none;color:var(--text);
}
.section-label {
  color:var(--muted);font-size:11px;font-weight:700;
  letter-spacing:.09em;text-transform:uppercase;margin:0 5px 7px;
}
.history-list {
  display:flex;flex-direction:column;gap:4px;
  overflow-y:auto;max-height:48vh;
}
.history-item {
  display:flex;align-items:center;gap:8px;width:100%;min-width:0;
  padding:9px 10px;text-align:left;border:1px solid transparent;
  background:transparent;border-radius:9px;color:var(--text);
}
.history-item:hover,.history-item.active {
  background:var(--accent-light);border-color:var(--line);
}
.history-title { overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1 }
.history-icon { color:var(--muted) }
.sidebar-bottom {
  margin-top:auto;border-top:1px solid var(--line);padding-top:14px;
}
.client-label { display:block;color:var(--muted);font-size:12px;margin-bottom:6px }
select {
  width:100%;padding:10px;border:1px solid var(--line);
  background:var(--panel);color:var(--text);border-radius:9px;
}
.main { min-width:0;flex:1;display:flex;flex-direction:column }
.topbar {
  min-height:66px;background:var(--panel);border-bottom:1px solid var(--line);
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;padding:10px 24px;
}
.topbar-title { font-size:15px;font-weight:700 }
.topbar-subtitle { color:var(--muted);font-size:12px }
.online { color:var(--success);font-size:12px;white-space:nowrap }
.top-actions { display:flex;align-items:center;gap:8px }
.content {
  width:100%;max-width:900px;margin:0 auto;
  padding:26px 24px 18px;flex:1;
}
.welcome { text-align:center;padding:38px 10px 26px }
.welcome-icon {
  width:58px;height:58px;margin:0 auto 14px;border-radius:18px;
  display:grid;place-items:center;background:var(--accent-light);
  color:var(--accent);font-size:27px;font-weight:800;
}
.welcome h1 { font-size:26px;letter-spacing:-.6px;margin:0 0 8px }
.welcome p { color:var(--muted);margin:0 }
.suggestions {
  display:flex;flex-wrap:wrap;justify-content:center;
  gap:8px;margin-top:19px;
}
.suggestion {
  border:1px solid var(--line);background:var(--panel);color:var(--text);
  border-radius:20px;padding:8px 12px;font-size:12px;
}
.suggestion:hover { border-color:var(--accent);color:var(--accent) }
#log { display:flex;flex-direction:column;gap:20px;padding-bottom:22px }
.message { display:flex;gap:11px;align-items:flex-start;min-width:0 }
.avatar {
  width:35px;height:35px;flex-shrink:0;border-radius:11px;
  display:grid;place-items:center;font-weight:800;
  background:var(--accent-light);color:var(--accent);
}
.message.user .avatar { background:#e8f7ee;color:#16794b }
html[data-theme="dark"] .message.user .avatar {
  background:#173c32;color:#8ce0b6;
}
.message-body { flex:1;min-width:0 }
.message-head {
  display:flex;align-items:center;justify-content:space-between;
  gap:10px;margin-bottom:7px;
}
.message-name { font-size:12px;font-weight:700 }
.message-tools { display:flex;flex-wrap:wrap;gap:5px }
.small-btn {
  border:1px solid var(--line);background:var(--panel);color:var(--muted);
  padding:4px 8px;border-radius:7px;font-size:12px;
}
.small-btn:hover { color:var(--accent);border-color:var(--accent) }
.message-text { white-space:pre-wrap;overflow-wrap:anywhere }
.bot-card {
  border:1px solid var(--line);background:var(--panel);
  border-radius:12px;padding:14px 15px;
}
.meta { display:flex;flex-wrap:wrap;gap:6px;margin-top:10px }
.badge {
  display:inline-block;padding:3px 8px;border:1px solid var(--line);
  border-radius:20px;font-size:11px;color:var(--muted);
}
.badge.warn { color:#b54708;background:#fffaeb;border-color:#fedf89 }
.detail-box {
  margin-top:10px;padding:10px 12px;background:var(--bg);
  border:1px solid var(--line);border-radius:9px;font-size:12px;
  white-space:pre-wrap;overflow-wrap:anywhere;
}
.detail-title { font-weight:700;margin-bottom:5px }
.feedback { display:flex;gap:6px;margin-top:12px }
.feedback button {
  border:1px solid var(--line);background:var(--panel);
  color:var(--text);border-radius:8px;padding:5px 10px;
}
.feedback button.on { border-color:var(--accent);background:var(--accent-light) }
.composer-wrap {
  background:linear-gradient(transparent,var(--bg) 20%);
  padding:12px 24px 18px;
}
.composer { max-width:850px;margin:auto }
.composer-card {
  background:var(--panel);border:1px solid var(--line);
  border-radius:15px;padding:10px;
  box-shadow:0 5px 20px #18223008;
}
#q {
  display:block;width:100%;resize:vertical;min-height:55px;max-height:180px;
  border:0;outline:none;padding:8px;background:transparent;color:var(--text);
}
.composer-actions {
  display:flex;justify-content:space-between;align-items:center;
  gap:10px;padding:4px 2px 0;
}
.composer-left { display:flex;align-items:center;gap:8px;flex-wrap:wrap }
.hint { color:var(--muted);font-size:11px }
.send-btn {
  border:0;background:var(--accent);color:white;
  border-radius:9px;padding:9px 15px;font-weight:600;
}
#status { max-width:850px;margin:7px auto 0;color:var(--danger);font-size:12px }
#edit-notice { display:none;margin-bottom:8px;color:var(--accent);font-size:12px }
.mobile-menu { display:none }
.empty-history { color:var(--muted);font-size:12px;padding:10px }
.upload-label {
  display:inline-flex;align-items:center;gap:6px;
  border:1px solid var(--line);border-radius:9px;
  background:var(--panel);color:var(--text);
  padding:7px 10px;font-size:12px;cursor:pointer;
}
.upload-label:hover { border-color:var(--accent);color:var(--accent) }
#document-file { display:none }
[hidden] { display:none !important }

@media(max-width:720px) {
  .sidebar {
    position:fixed;z-index:10;top:0;bottom:0;left:0;
    transform:translateX(-105%);transition:transform .2s;
    box-shadow:8px 0 30px #0001;
  }
  .sidebar.open { transform:translateX(0) }
  .mobile-menu { display:inline-flex }
  .topbar { padding:10px 12px;min-height:60px }
  .topbar-subtitle,.online { display:none }
  .content { padding:15px 14px }
  .welcome { padding:25px 4px 20px }
  .welcome h1 { font-size:22px }
  .composer-wrap { padding:10px 12px 14px }
  .hint { display:none }
}
</style>
</head>

<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-icon">A</div>
      <div>
        <h2>ATLAS</h2>
        <small>AI Market Research Assistant</small>
      </div>
    </div>

    <button type="button" class="primary" id="new-chat">＋ New Chat</button>

    <input id="history-search" class="search" type="search"
      placeholder="⌕ Search questions..."
      aria-label="Search chat history">

    <section>
      <p class="section-label">Recent questions</p>
      <div id="history-list" class="history-list"></div>
      <div id="history-empty" class="empty-history" hidden>
        No matching questions.
      </div>
    </section>

    <div class="sidebar-bottom">
      <label class="client-label" for="client">Current client</label>
      <select id="client">
        <?php foreach ($clients as $id => $name): ?>
          <option value="<?= html_escape($id) ?>"
            <?= $id === $client_id ? 'selected' : '' ?>>
            <?= html_escape($name) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="hint" style="margin:10px 2px 0">
        Answers are based on authorized report sources.
      </p>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:10px">
        <button class="secondary mobile-menu" id="menu-toggle"
          
        <div>
          <div class="topbar-title">ATLAS Workspace</div>
          <div class="topbar-subtitle">AI-powered market research</div>
        </div>
      </div>

      <div class="top-actions">
        <span class="online">● Secure workspace</span>
        <button class="secondary" id="theme-toggle" type="button">
          🌙 Dark
        </button>
      </div>
    </header>

    <section class="content">
      <div class="welcome" id="welcome">
        <div class="welcome-icon">A</div>
        <h1>Welcome to ATLAS</h1>
        <p>Ask questions and explore your authorized market reports.</p>
        <div class="suggestions">
          <button class="suggestion" type="button">Summarize the market outlook</button>
          <button class="suggestion" type="button">Compare reported market figures</button>
          <button class="suggestion" type="button">Explain the growth assumptions</button>
        </div>
      </div>
      <div id="log" aria-live="polite"></div>
    </section>

    <div class="composer-wrap">
      <div class="composer">
        <div id="edit-notice">
          Editing a question — update it and press Send.
        </div>

        <form id="form" class="composer-card">
          <textarea id="q" maxlength="1000"
            placeholder="Ask ATLAS about a market report..."
            required></textarea>

          <div class="composer-actions">
            <div class="composer-left">
              <label class="upload-label" for="document-file">
                ＋ Upload PDF
              </label>
              <input id="document-file" type="file"
                accept=".pdf,application/pdf">
              <span class="hint">Ctrl+V to paste text</span>
            </div>
            <button id="send" class="send-btn" type="submit">↑ Send</button>
          </div>
        </form>
        <div id="status" role="status" aria-live="polite"></div>
      </div>
    </div>
  </main>
</div>

<script type="application/json" id="hist"><?= json_encode($history, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script>
const CSRF_NAME = <?= json_encode($csrf_name) ?>;
const CSRF_HASH = <?= json_encode($csrf_hash) ?>;

const log = document.getElementById('log');
const statusBox = document.getElementById('status');
const questionBox = document.getElementById('q');
const sendButton = document.getElementById('send');
const historyList = document.getElementById('history-list');
const historyEmpty = document.getElementById('history-empty');
const welcome = document.getElementById('welcome');
const editNotice = document.getElementById('edit-notice');
const sidebar = document.getElementById('sidebar');

let historyItems = [];
let editing = false;
let activeHistoryItem = null;

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
      `Server returned invalid JSON (HTTP ${response.status}).`
    );
  }

  if (!response.ok) {
    throw new Error(data.error || `Request failed (HTTP ${response.status}).`);
  }

  return data;
}

// Theme
const themeButton = document.getElementById('theme-toggle');

function applyTheme(theme) {
  document.documentElement.dataset.theme = theme;
  themeButton.textContent = theme === 'dark' ? '☀️ Light' : '🌙 Dark';
}

let savedTheme = 'light';

try {
  savedTheme = localStorage.getItem('atlas-theme') || 'light';
} catch (_) {}

applyTheme(savedTheme);

themeButton.addEventListener('click', () => {
  const nextTheme =
    document.documentElement.dataset.theme === 'dark'
      ? 'light'
      : 'dark';

  applyTheme(nextTheme);

  try {
    localStorage.setItem('atlas-theme', nextTheme);
  } catch (_) {}
});

function updateWelcome() {
  welcome.hidden = log.childElementCount > 0;
}

function addUser(text, opts = {}) {
  const wrap = el('article', 'message user');

  if (opts.id !== undefined && opts.id !== null) {
    wrap.dataset.messageId = String(opts.id);
  }

  wrap.appendChild(el('div', 'avatar', 'Y'));

  const body = el('div', 'message-body');
  const head = el('div', 'message-head');
  head.appendChild(el('span', 'message-name', 'You'));

  const tools = el('div', 'message-tools');
  const edit = el('button', 'small-btn', '✎ Edit');
  edit.type = 'button';

  edit.addEventListener('click', () => {
    questionBox.value = text;
    editing = true;
    editNotice.style.display = 'block';
    sendButton.textContent = '↻ Resend';
    questionBox.focus();
  });

  tools.appendChild(edit);
  head.appendChild(tools);
  body.appendChild(head);
  body.appendChild(el('div', 'message-text', text));

  wrap.appendChild(body);
  log.appendChild(wrap);
  updateWelcome();

  return wrap;
}

function addBot(d) {
  const wrap = el('article', 'message assistant');
  wrap.appendChild(el('div', 'avatar', 'A'));

  const body = el('div', 'message-body');
  const head = el('div', 'message-head');
  head.appendChild(el('span', 'message-name', 'ATLAS AI'));

  const tools = el('div', 'message-tools');
  const copyButton = el('button', 'small-btn', 'Copy');
  copyButton.type = 'button';

  copyButton.addEventListener('click', async () => {
    const answer = d.answer || d.error || '';

    try {
      await navigator.clipboard.writeText(answer);
      copyButton.textContent = 'Copied ✓';
    } catch (_) {
      const temp = document.createElement('textarea');
      temp.value = answer;
      temp.style.position = 'fixed';
      temp.style.opacity = '0';
      document.body.appendChild(temp);
      temp.select();

      const copied = document.execCommand('copy');
      temp.remove();
      copyButton.textContent = copied ? 'Copied ✓' : 'Copy failed';
    }

    setTimeout(() => {
      copyButton.textContent = 'Copy';
    }, 1600);
  });

  tools.appendChild(copyButton);
  head.appendChild(tools);
  body.appendChild(head);

  const card = el('div', 'bot-card');
  card.appendChild(el('div', 'message-text',
    d.answer || d.error || 'No answer was returned by the API.'));

  const meta = el('div', 'meta');

  if (d.confidence !== null && d.confidence !== undefined &&
      Number.isFinite(Number(d.confidence))) {
    meta.appendChild(el('span', 'badge',
      'Confidence ' + Number(d.confidence).toFixed(2)));
  }

  if (d.escalated) {
    meta.appendChild(el('span', 'badge warn', 'Escalated to analyst'));
  }

  if (d.cached) {
    meta.appendChild(el('span', 'badge', 'Cached response'));
  }

  if (meta.childNodes.length) card.appendChild(meta);

  (Array.isArray(d.conflicts) ? d.conflicts : []).forEach(c => {
    const box = el('div', 'detail-box');
    box.appendChild(el('div', 'detail-title', '⚠ Sources disagree'));

    box.appendChild(el('div', '',
      `${c.metric || 'Metric'} · ${c.region || 'Region'} · ${c.period || 'Period'}`));

    (Array.isArray(c.values) ? c.values : []).forEach(v => {
      box.appendChild(el('div', '',
        `${v.report_id || 'Report'} p.${v.page ?? '?'}: ${v.value ?? ''} ${v.unit || ''}${v.scope ? ' — ' + v.scope : ''}`));
    });

    card.appendChild(box);
  });

  (Array.isArray(d.derived_values) ? d.derived_values : []).forEach(v => {
    const box = el('div', 'detail-box');
    box.appendChild(el('div', 'detail-title', v.label || 'Derived value'));

    box.appendChild(el('div', '',
      `${v.report_id || 'Report'}: ${v.result ?? ''} ${v.unit || ''}`));

    if (v.formula) {
      box.appendChild(el('div', '', 'Formula: ' + v.formula));
    }

    (Array.isArray(v.assumptions) ? v.assumptions : []).forEach(a => {
      box.appendChild(el('div', '', '• ' + a));
    });

    card.appendChild(box);
  });

  if (Array.isArray(d.citations) && d.citations.length) {
    const box = el('div', 'detail-box');
    box.appendChild(el('div', 'detail-title', '▤ Sources'));

    d.citations.forEach(x => {
      box.appendChild(el('div', '',
        `${x.report_id || 'Report'} p.${x.page ?? '?'} — ${x.title || 'Untitled source'}`));
    });

    card.appendChild(box);
  }

  if (d.message_id) {
    const fb = el('div', 'feedback');

    [['👍 Helpful', 1], ['👎 Not helpful', -1]].forEach(([label, value]) => {
      const button = el(
        'button',
        Number(d.rating) === value ? 'on' : '',
        label
      );

      button.type = 'button';

      button.addEventListener('click', async () => {
        button.disabled = true;

        try {
          const result = await post('feedback', {
            message_id: d.message_id,
            rating: value
          });

          if (result.ok) {
            fb.querySelectorAll('button').forEach(item => {
              item.classList.toggle('on', item === button);
            });
            statusBox.textContent = '';
          }
        } catch (error) {
          statusBox.textContent =
            error.message || 'Could not submit feedback.';
        } finally {
          button.disabled = false;
        }
      });

      fb.appendChild(button);
    });

    card.appendChild(fb);
  }

  body.appendChild(card);
  wrap.appendChild(body);
  log.appendChild(wrap);
  updateWelcome();
  wrap.scrollIntoView({block:'end',behavior:'smooth'});

  return wrap;
}

function rememberHistory(question, domId, data = null) {
  const item = {question, domId, data};
  historyItems.unshift(item);
  renderHistory();
  return item;
}

function renderHistory() {
  const term = document.getElementById('history-search')
    .value.trim().toLowerCase();

  historyList.replaceChildren();

  const filtered = historyItems.filter(item =>
    item.question.toLowerCase().includes(term)
  );

  filtered.forEach(item => {
    const button = el('button', 'history-item');
    button.type = 'button';
    button.appendChild(el('span', 'history-icon', '▤'));
    button.appendChild(el(
      'span',
      'history-title',
      item.question || 'Untitled question'
    ));

    button.title = item.question;

    button.addEventListener('click', () => {
      const target = item.domId
        ? document.getElementById(item.domId)
        : null;

      if (target) {
        target.scrollIntoView({behavior:'smooth',block:'center'});
      } else {
        // Restore this question in the current view.
        log.replaceChildren();

        const userMessage = addUser(item.question);
        userMessage.id = 'history-question-' + Date.now();
        item.domId = userMessage.id;

        if (item.data) {
          addBot(item.data);
        } else {
          addBot({
            answer: 'The saved answer is not available in this session. You can ask this question again.'
          });
        }
      }

      document.querySelectorAll('.history-item').forEach(b =>
        b.classList.remove('active')
      );
      button.classList.add('active');
      sidebar.classList.remove('open');
    });

    historyList.appendChild(button);
  });

  historyEmpty.hidden = filtered.length > 0;
}

function newChat() {
  log.replaceChildren();

  // Preserve the sidebar question list.
  activeHistoryItem = null;
  questionBox.value = '';
  editing = false;
  editNotice.style.display = 'none';
  sendButton.textContent = '↑ Send';
  statusBox.textContent = '';

  document.querySelectorAll('.history-item').forEach(b =>
    b.classList.remove('active')
  );

  updateWelcome();
  questionBox.focus();
  sidebar.classList.remove('open');
}

// Load server-provided history.
try {
  const history = JSON.parse(
    document.getElementById('hist').textContent || '[]'
  );

  history.forEach((h, index) => {
    let payload = {};

    try {
      payload = typeof h.payload === 'string'
        ? JSON.parse(h.payload || '{}')
        : (h.payload || {});
    } catch (_) {}

    const question = h.question || '';
    const data = {
      answer: h.answer,
      confidence: h.confidence == null ? null : Number(h.confidence),
      escalated: h.escalated == 1,
      citations: payload.citations || [],
      conflicts: payload.conflicts || [],
      derived_values: payload.derived_values || [],
      message_id: Number(h.id),
      rating: h.rating == null ? null : Number(h.rating)
    };

    const userMessage = addUser(question, {id:h.id});
    userMessage.id = 'history-question-' + index;

    addBot(data);
    rememberHistory(question, userMessage.id, data);
  });
} catch (_) {
  statusBox.textContent =
    'Could not load chat history. You can still ask a question.';
}

// Search history
document.getElementById('history-search')
  .addEventListener('input', renderHistory);

document.getElementById('new-chat')
  .addEventListener('click', newChat);

document.getElementById('menu-toggle')
  .addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });

// Back navigation
document.getElementById('back-btn').addEventListener('click', () => {
  if (sidebar.classList.contains('open')) {
    sidebar.classList.remove('open');
    return;
  }

  if (log.childElementCount > 0) {
    newChat();
  } else if (window.history.length > 1) {
    window.history.back();
  }
});

// Suggested questions
document.querySelectorAll('.suggestion').forEach(button => {
  button.addEventListener('click', () => {
    questionBox.value = button.textContent.trim();
    questionBox.focus();
  });
});

// Client selector
document.getElementById('client').addEventListener('change', () => {
  statusBox.textContent =
    'Client changed. Start a new chat to clear the current workspace.';
});

// Document picker: UI is ready, but actual upload requires the backend route.
document.getElementById('document-file').addEventListener('change', event => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    statusBox.textContent = 'Please select a PDF file.';
    event.target.value = '';
    return;
  }

  statusBox.textContent =
    'Selected: ' + file.name +
    '. PDF ingestion must be connected to the configured backend upload endpoint.';
});

// Submit question to the existing CI3 send endpoint.
document.getElementById('form').addEventListener('submit', async event => {
  event.preventDefault();

  const question = questionBox.value.trim();
  if (!question) return;

  const userMessage = addUser(question);
  userMessage.id = 'question-' + Date.now();

  const item = rememberHistory(question, userMessage.id);

  questionBox.value = '';
  sendButton.disabled = true;
  sendButton.textContent = 'Thinking...';
  statusBox.textContent = '';

  editing = false;
  editNotice.style.display = 'none';

  try {
    const data = await post('send', {
      client_id: document.getElementById('client').value,
      question
    });

    addBot(data);
    item.data = data;

    if (data.ok === false) {
      statusBox.textContent =
        'The API returned a fallback response. Check the API logs and model configuration.';
    }
  } catch (error) {
    statusBox.textContent =
      error.message || 'Something went wrong. Please try again.';

    item.data = {answer: statusBox.textContent};
    addBot(item.data);
  } finally {
    sendButton.disabled = false;
    sendButton.textContent = '↑ Send';
    questionBox.focus();
  }
});
</script>
</body>
</html>
